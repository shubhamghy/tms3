<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? '';
// IMPORTANT: Only admins can manage users
if ($user_role !== 'admin') {
    header("location: dashboard.php");
    exit;
}

// --- Helper function for file uploads ---
function upload_user_file($file_input_name, $user_id, $existing_path = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/users/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        
        if (!empty($existing_path) && file_exists($existing_path)) {
            @unlink($existing_path);
        }
        
        $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
        $new_file_name = "user_{$user_id}_{$file_input_name}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $target_file;
        }
    }
    return $existing_path;
}

// --- Helper for Document Thumbnails ---
function getDocumentThumbnail($path, $label, $icon_class = 'fa-file-alt') {
    if (!empty($path) && file_exists($path)) {
        $file_info = pathinfo($path);
        $is_image = in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png', 'gif']);
        $html = '<div><p class="text-sm font-medium text-gray-500 mb-1">'.$label.'</p>';
        if ($is_image) {
            $html .= '<a href="'.htmlspecialchars($path).'" target="_blank"><img src="'.htmlspecialchars($path).'" alt="'.$label.'" class="h-24 w-24 rounded-lg object-cover border hover:opacity-80 transition-opacity"></a>';
        } else {
            $html .= '<a href="'.htmlspecialchars($path).'" target="_blank" class="block h-24 w-24 rounded-lg border bg-gray-50 flex items-center justify-center hover:bg-gray-100 transition-colors"><i class="fas '.$icon_class.' fa-2x text-gray-400"></i></a>';
        }
        $html .= '</div>';
        return $html;
    }
    return '<div><p class="text-sm font-medium text-gray-500 mb-1">'.$label.'</p><div class="h-24 w-24 rounded-lg border bg-gray-100 flex items-center justify-center"><span class="text-xs text-gray-400">Not Uploaded</span></div></div>';
}

$form_message = "";
$edit_mode = false;
$view_mode = false;
$add_mode = false;
$user_data = ['id' => '', 'username' => '', 'email' => '', 'role' => 'staff', 'branch_id' => '', 'address' => '', 'pan_no' => '', 'aadhaar_no' => '', 'is_active' => 1, 'photo_path' => '', 'pan_doc_path' => '', 'aadhaar_doc_path' => ''];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // Server-side validation for duplicates
    $is_duplicate = false;
    $duplicate_error_message = '';

    $stmt_check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $existing_user = $result_check->fetch_assoc();
        if ($existing_user['id'] != $id) {
            $is_duplicate = true;
            $duplicate_error_message = "This username is already taken.";
        }
    }
    $stmt_check->close();

    if (!$is_duplicate) {
        $stmt_check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $existing_user = $result_check->fetch_assoc();
            if ($existing_user['id'] != $id) {
                $is_duplicate = true;
                $duplicate_error_message = "This email address is already registered.";
            }
        }
        $stmt_check->close();
    }

    if ($is_duplicate) {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $duplicate_error_message . '</div>';
        $user_data = $_POST;
        $user_data['id'] = $id;
        if ($id > 0) { $edit_mode = true; } else { $add_mode = true; }
    } else {
        // Proceed with saving
        $role = trim($_POST['role']);
        $branch_id = intval($_POST['branch_id']);
        $password = $_POST['password'];
        $address = trim($_POST['address']);
        $pan_no = trim($_POST['pan_no']);
        $aadhaar_no = trim($_POST['aadhaar_no']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id > 0) { // Update
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username=?, email=?, role=?, branch_id=?, password=?, address=?, pan_no=?, aadhaar_no=?, is_active=? WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssissssii", $username, $email, $role, $branch_id, $hashed_password, $address, $pan_no, $aadhaar_no, $is_active, $id);
            } else {
                $sql = "UPDATE users SET username=?, email=?, role=?, branch_id=?, address=?, pan_no=?, aadhaar_no=?, is_active=? WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssisssii", $username, $email, $role, $branch_id, $address, $pan_no, $aadhaar_no, $is_active, $id);
            }
        } else { // Insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, email, role, branch_id, address, pan_no, aadhaar_no, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssssisssi", $username, $hashed_password, $email, $role, $branch_id, $address, $pan_no, $aadhaar_no, $is_active);
        }

        if ($stmt->execute()) {
            $user_id = ($id > 0) ? $id : $stmt->insert_id;
            
            $photo_path = upload_user_file('photo', $user_id, $_POST['existing_photo_path'] ?? '');
            $pan_doc_path = upload_user_file('pan_doc', $user_id, $_POST['existing_pan_doc_path'] ?? '');
            $aadhaar_doc_path = upload_user_file('aadhaar_doc', $user_id, $_POST['existing_aadhaar_doc_path'] ?? '');

            $update_paths_sql = "UPDATE users SET photo_path=?, pan_doc_path=?, aadhaar_doc_path=? WHERE id=?";
            $update_stmt = $mysqli->prepare($update_paths_sql);
            $update_stmt->bind_param("sssi", $photo_path, $pan_doc_path, $aadhaar_doc_path, $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">User saved successfully!</div>';
            $add_mode = $edit_mode = false;
        } else {
            $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}


// Handle GET requests
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] == 'add') { $add_mode = true; }
    elseif ($_GET['action'] == 'view' && $id > 0) {
        $view_mode = true;
        $stmt = $mysqli->prepare("SELECT u.*, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) { $user_data = $result->fetch_assoc(); }
        $stmt->close();
    }
    elseif ($_GET['action'] == 'edit' && $id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
            $edit_mode = true;
        }
        $stmt->close();
    } elseif (($_GET['action'] == 'delete' || $_GET['action'] == 'reactivate') && $id > 0) {
        if ($id == 1) {
            $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Cannot change the status of the main admin user.</div>";
        } else {
            $new_status = ($_GET['action'] == 'delete') ? 0 : 1;
            $action_word = ($new_status == 0) ? 'deactivated' : 'reactivated';
            $stmt = $mysqli->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $id);
            if($stmt->execute()){ $form_message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>User {$action_word} successfully.</div>"; }
            else { $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error updating user status.</div>"; }
            $stmt->close();
        }
    }
}

// Fetch users for the list view
$users_list = [];
if (!$edit_mode && !$add_mode && !$view_mode) {
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;
    
    // --- SEARCH LOGIC ---
    $search_term = trim($_GET['search'] ?? '');
    $where_clauses = [];
    $params = [];
    $types = "";
    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ? OR b.name LIKE ?)";
        array_push($params, $like_term, $like_term, $like_term);
        $types .= "sss";
    }
    $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

    // Get total records with filtering
    $total_records_sql = "SELECT COUNT(u.id) FROM users u LEFT JOIN branches b ON u.branch_id = b.id" . $where_sql;
    $stmt_count = $mysqli->prepare($total_records_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch users for the current page
    $sql = "SELECT u.id, u.username, u.email, u.role, u.is_active, u.photo_path, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id" . $where_sql . " ORDER BY u.username ASC LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $types .= "i";
    $params[] = $offset;
    $types .= "i";
    
    $stmt_list = $mysqli->prepare($sql);
    $stmt_list->bind_param($types, ...$params);
    $stmt_list->execute();
    $users_list = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
}
$branches = $mysqli->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style> body { font-family: 'Inter', sans-serif; } [x-cloak] { display: none; } </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Manage Users</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($view_mode): ?>
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">User Profile</h2>
                        <a href="manage_users.php" class="py-2 px-4 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1">
                            <div class="bg-white p-6 rounded-xl shadow-md text-center">
                                <img src="<?php echo htmlspecialchars($user_data['photo_path'] ?: 'https://placehold.co/200x200/e2e8f0/e2e8f0'); ?>" alt="User Photo" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover ring-4 ring-indigo-100">
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                <div class="mt-4 flex justify-center gap-x-2">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></span>
                                    <?php echo $user_data['is_active'] ? '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>' : '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>'; ?>
                                </div>
                                <div class="mt-4 border-t pt-4">
                                    <p class="text-sm text-gray-500">Branch</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($user_data['branch_name'] ?? 'N/A'); ?></p>
                                </div>
                                <a href="manage_users.php?action=edit&id=<?php echo $user_data['id']; ?>" class="mt-6 w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-pencil-alt mr-2"></i>Edit Profile
                                </a>
                            </div>
                        </div>
                        <div class="lg:col-span-2" x-data="{ activeTab: 'details' }">
                            <div class="bg-white rounded-xl shadow-md">
                                <div class="border-b border-gray-200">
                                    <nav class="-mb-px flex space-x-6 px-6">
                                        <a href="#" @click.prevent="activeTab = 'details'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'details', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'details'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Details</a>
                                        <a href="#" @click.prevent="activeTab = 'documents'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'documents', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'documents'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Documents</a>
                                    </nav>
                                </div>
                                <div class="p-6">
                                    <div x-show="activeTab === 'details'" x-cloak>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6 text-sm">
                                            <div class="sm:col-span-2"><dt class="font-medium text-gray-500">Address</dt><dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($user_data['address'] ?: 'N/A'); ?></dd></div>
                                            <div><dt class="font-medium text-gray-500">PAN Number</dt><dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($user_data['pan_no'] ?: 'N/A'); ?></dd></div>
                                            <div><dt class="font-medium text-gray-500">Aadhaar Number</dt><dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($user_data['aadhaar_no'] ?: 'N/A'); ?></dd></div>
                                        </dl>
                                    </div>
                                    <div x-show="activeTab === 'documents'" x-cloak>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                            <?php echo getDocumentThumbnail($user_data['pan_doc_path'] ?? null, 'PAN Card', 'fa-id-card'); ?>
                                            <?php echo getDocumentThumbnail($user_data['aadhaar_doc_path'] ?? null, 'Aadhaar Card', 'fa-address-card'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php elseif ($edit_mode || $add_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?></h2>
                        <a href="manage_users.php" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</a>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="id" value="<?php echo $user_data['id']; ?>">
                        <input type="hidden" name="existing_photo_path" value="<?php echo htmlspecialchars($user_data['photo_path']); ?>">
                        <input type="hidden" name="existing_pan_doc_path" value="<?php echo htmlspecialchars($user_data['pan_doc_path']); ?>">
                        <input type="hidden" name="existing_aadhaar_doc_path" value="<?php echo htmlspecialchars($user_data['aadhaar_doc_path']); ?>">
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Login Credentials</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2"><div><label class="block text-sm font-medium">Username <span class="text-red-500">*</span></label><input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div><div><label class="block text-sm font-medium">Email <span class="text-red-500">*</span></label><input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div><div><label class="block text-sm font-medium">Password <?php if($edit_mode) echo '<span class="text-gray-500">(leave blank)</span>'; else echo '<span class="text-red-500">*</span>'; ?></label><input type="password" name="password" class="mt-1 block w-full px-3 py-2 border rounded-md" <?php if(!$edit_mode) echo 'required'; ?>></div></div></fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Role & Branch</legend><div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2"><div><label class="block text-sm font-medium">Role <span class="text-red-500">*</span></label><select name="role" class="mt-1 block w-full px-3 py-2 border rounded-md bg-white" required><option value="staff" <?php if($user_data['role'] == 'staff') echo 'selected'; ?>>Operation Staff</option><option value="manager" <?php if($user_data['role'] == 'manager') echo 'selected'; ?>>Manager</option><option value="admin" <?php if($user_data['role'] == 'admin') echo 'selected'; ?>>Super Admin</option></select></div><div><label class="block text-sm font-medium">Branch <span class="text-red-500">*</span></label><select name="branch_id" class="mt-1 block w-full px-3 py-2 border rounded-md bg-white" required><option value="">Select Branch</option><?php foreach($branches as $branch): ?><option value="<?php echo $branch['id']; ?>" <?php if($user_data['branch_id'] == $branch['id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['name']); ?></option><?php endforeach; ?></select></div></div></fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Personal Details</legend><div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2"><div class="md:col-span-2"><label class="block text-sm font-medium">Address</label><textarea name="address" rows="2" class="mt-1 block w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($user_data['address']); ?></textarea></div><div><label class="block text-sm font-medium">PAN Number</label><input type="text" name="pan_no" value="<?php echo htmlspecialchars($user_data['pan_no']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">Aadhaar Number</label><input type="text" name="aadhaar_no" value="<?php echo htmlspecialchars($user_data['aadhaar_no']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div></div></fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Documents & Photo</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2"><div><label class="block text-sm font-medium">Photo</label><input type="file" name="photo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-50"></div><div><label class="block text-sm font-medium">PAN Document</label><input type="file" name="pan_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-50"></div><div><label class="block text-sm font-medium">Aadhaar Document</label><input type="file" name="aadhaar_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-50"></div></div></fieldset>
                        <div class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php if($user_data['is_active']) echo 'checked'; ?> class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                        <div class="mt-6 flex justify-end"><button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><?php echo $edit_mode ? 'Update User' : 'Save User'; ?></button></div>
                    </form>
                </div>
                
                <?php else: ?>
                <div class="space-y-6">
                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <form method="GET" action="manage_users.php">
                            <div class="flex items-center space-x-2">
                                <input type="text" name="search" placeholder="Search by username, email, branch..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <button type="submit" class="py-2 px-4 border rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-search"></i></button>
                                <a href="manage_users.php" class="py-2 px-4 border rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">Existing Users</h2>
                        <a href="manage_users.php?action=add" class="inline-flex items-center justify-center sm:w-auto w-full py-2 px-4 border rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New User</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($users_list as $user): ?>
                        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                            <div class="flex-grow">
                                <div class="flex items-start space-x-4">
                                    <img class="h-16 w-16 rounded-full object-cover" src="<?php echo htmlspecialchars($user['photo_path'] ?: 'https://placehold.co/100x100/e2e8f0/e2e8f0'); ?>" alt="User photo">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-lg text-gray-800 truncate"><?php echo htmlspecialchars($user['username']); ?></h3>
                                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($user['branch_name'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t flex flex-wrap gap-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                                    <?php echo $user['is_active'] ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>'; ?>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t flex justify-end space-x-3 text-sm font-medium">
                                <a href="manage_users.php?action=view&id=<?php echo $user['id']; ?>" class="text-gray-600 hover:text-indigo-800">Details</a>
                                <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                                <?php if ($user['id'] != 1): ?>
                                    <?php if ($user['is_active']): ?>
                                        <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to deactivate this user?');">Deactivate</a>
                                    <?php else: ?>
                                        <a href="manage_users.php?action=reactivate&id=<?php echo $user['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Are you sure you want to reactivate this user?');">Reactivate</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-between items-center">
                        <span class="text-sm text-gray-700">Showing <?php echo $total_records > 0 ? ($offset + 1) : 0; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results</span>
                        <div class="flex">
                            <?php 
                                $query_params = [];
                                if (!empty($search_term)) { $query_params['search'] = $search_term; }
                            ?>
                            <?php if ($page > 1): ?>
                                <?php $query_params['page'] = $page - 1; ?>
                                <a href="?<?php echo http_build_query($query_params); ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Previous</a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <?php $query_params['page'] = $page + 1; ?>
                                <a href="?<?php echo http_build_query($query_params); ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    <script>
        // --- Mobile sidebar toggle ---
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarClose = document.getElementById('sidebar-close');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
        if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
    </script>
</body>
</html>