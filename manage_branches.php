<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? '';
if ($user_role !== 'admin') {
    header("location: dashboard.php");
    exit;
}

// Helper functions (upload_branch_file, getDocumentThumbnail) remain the same...
function upload_branch_file($file_input_name, $branch_id, $existing_path = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/branches/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        if (!empty($existing_path) && file_exists($existing_path)) { @unlink($existing_path); }
        $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
        $new_file_name = "branch_{$branch_id}_{$file_input_name}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $target_file;
        }
    }
    return $existing_path;
}

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
    return '';
}

$form_message = "";
$edit_mode = false;
$view_mode = false;
$add_mode = false;
$branch_data = ['id' => '', 'name' => '', 'address' => '', 'city' => '', 'state' => '', 'country' => '', 'contact_number' => '', 'contact_number_2' => '', 'email' => '', 'website' => '', 'gst_no' => '', 'food_license_no' => '', 'trade_license_path' => '', 'is_active' => 1];
$associated_users = [];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $country_id = intval($_POST['country'] ?? 0);
    $state_id = intval($_POST['state'] ?? 0);
    $city_id = intval($_POST['city'] ?? 0);
    
    $country_name = '';
    if ($country_id > 0) { $country_name = $mysqli->query("SELECT name FROM countries WHERE id = $country_id")->fetch_assoc()['name'] ?? ''; }
    $state_name = '';
    if ($state_id > 0) { $state_name = $mysqli->query("SELECT name FROM states WHERE id = $state_id")->fetch_assoc()['name'] ?? ''; }
    $city_name = '';
    if ($city_id > 0) { $city_name = $mysqli->query("SELECT name FROM cities WHERE id = $city_id")->fetch_assoc()['name'] ?? ''; }

    if ($id > 0) { // Update
        $trade_license_path = upload_branch_file('trade_license_doc', $id, $_POST['existing_trade_license_path']);
        $sql = "UPDATE branches SET name=?, address=?, city=?, state=?, country=?, contact_number=?, contact_number_2=?, email=?, website=?, gst_no=?, food_license_no=?, trade_license_path=?, is_active=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssssssssii", $name, $_POST['address'], $city_name, $state_name, $country_name, $_POST['contact_number'], $_POST['contact_number_2'], $_POST['email'], $_POST['website'], $_POST['gst_no'], $_POST['food_license_no'], $trade_license_path, $is_active, $id);
    } else { // Insert
        $sql = "INSERT INTO branches (name, address, city, state, country, contact_number, contact_number_2, email, website, gst_no, food_license_no, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssssssi", $name, $_POST['address'], $city_name, $state_name, $country_name, $_POST['contact_number'], $_POST['contact_number_2'], $_POST['email'], $_POST['website'], $_POST['gst_no'], $_POST['food_license_no'], $is_active);
    }

    if ($stmt->execute()) {
        $branch_id = ($id > 0) ? $id : $stmt->insert_id;
        if ($id == 0) {
            $trade_license_path = upload_branch_file('trade_license_doc', $branch_id);
            if ($trade_license_path) {
                $mysqli->query("UPDATE branches SET trade_license_path = '{$mysqli->real_escape_string($trade_license_path)}' WHERE id = $branch_id");
            }
        }
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Branch saved successfully!</div>';
        $add_mode = $edit_mode = false;
    } else {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error saving branch.</div>';
    }
    $stmt->close();
}

// Handle GET requests
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] == 'add') { $add_mode = true; }
    elseif ($_GET['action'] == 'view' && $id > 0) {
        $view_mode = true;
        $stmt = $mysqli->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) { $branch_data = $result->fetch_assoc(); }
        $stmt->close();
        
        $stmt_users = $mysqli->prepare("SELECT username, email, role FROM users WHERE branch_id = ?");
        $stmt_users->bind_param("i", $id);
        $stmt_users->execute();
        $associated_users = $stmt_users->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_users->close();
    } elseif ($_GET['action'] == 'edit' && $id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $branch_data = $result->fetch_assoc();
            $edit_mode = true;
        }
        $stmt->close();
    } elseif (($_GET['action'] == 'delete' || $_GET['action'] == 'reactivate') && $id > 0) {
        $new_status = ($_GET['action'] == 'delete') ? 0 : 1;
        $action_word = ($new_status == 0) ? 'deactivated' : 'reactivated';
        $stmt = $mysqli->prepare("UPDATE branches SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        if($stmt->execute()){ $form_message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Branch {$action_word} successfully.</div>"; }
        else { $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error: Cannot deactivate a branch with active users. Please reassign users first.</div>"; }
        $stmt->close();
    }
}

// Data fetching for lists/dropdowns
$branches_list = [];
$countries = [];
if ($add_mode || $edit_mode) {
    $countries = $mysqli->query("SELECT * FROM countries ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
} elseif (!$view_mode) {
    // --- CORRECTED DATA FETCHING LOGIC ---
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;
    
    $search_term = trim($_GET['search'] ?? '');
    $where_sql = "";
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        $where_sql = " WHERE (name LIKE ? OR city LIKE ? OR state LIKE ? OR gst_no LIKE ?)";
        $params = [$like_term, $like_term, $like_term, $like_term];
        $types = "ssss";
    }

    // Get total records with filtering
    $total_records_sql = "SELECT COUNT(*) FROM branches" . $where_sql;
    $stmt_count = $mysqli->prepare($total_records_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);
    
    // Fetch branches for the current page
    $list_sql = "SELECT * FROM branches" . $where_sql . " ORDER BY name ASC LIMIT ? OFFSET ?";
    
    $params[] = $records_per_page;
    $types .= "i";
    $params[] = $offset;
    $types .= "i";
    
    $stmt_list = $mysqli->prepare($list_sql);
    
    // Use call_user_func_array for robust binding
    if (!empty($types)) {
        $bind_params = [];
        $bind_params[] = $types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt_list, 'bind_param'], $bind_params);
    }
    
    $stmt_list->execute();
    $result = $stmt_list->get_result();
    if ($result) {
        $branches_list = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_list->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single { height: 42px; border: 1px solid #d1d5db; border-radius: 0.375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 0.75rem; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="page-loader" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Manage Branches</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($view_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($branch_data['name']); ?></h2>
                        <a href="manage_branches.php" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
                    </div>
                    <div class="space-y-6">
                        <div class="p-4 border rounded-lg"><h3 class="font-semibold text-gray-700 mb-2">Location & Address</h3><div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm"><div><p class="font-medium text-gray-500">Location:</p><p><?php echo htmlspecialchars($branch_data['city'] . ', ' . $branch_data['state'] . ', ' . $branch_data['country']); ?></p></div><div class="sm:col-span-2"><p class="font-medium text-gray-500">Address:</p><p><?php echo nl2br(htmlspecialchars($branch_data['address'])); ?></p></div></div></div>
                        <div class="p-4 border rounded-lg"><h3 class="font-semibold text-gray-700 mb-2">Contact Information</h3><div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm"><div><p class="font-medium text-gray-500">Contact 1:</p><p><?php echo htmlspecialchars($branch_data['contact_number'] ?: 'N/A'); ?></p></div><div><p class="font-medium text-gray-500">Contact 2:</p><p><?php echo htmlspecialchars($branch_data['contact_number_2'] ?: 'N/A'); ?></p></div><div><p class="font-medium text-gray-500">Email:</p><p><?php echo htmlspecialchars($branch_data['email'] ?: 'N/A'); ?></p></div><div><p class="font-medium text-gray-500">Website:</p><p><?php echo htmlspecialchars($branch_data['website'] ?: 'N/A'); ?></p></div></div></div>
                        <div class="p-4 border rounded-lg"><h3 class="font-semibold text-gray-700 mb-2">Licenses & Documents</h3><div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm"><div><p class="font-medium text-gray-500">GST No:</p><p><?php echo htmlspecialchars($branch_data['gst_no'] ?: 'N/A'); ?></p></div><div><p class="font-medium text-gray-500">FSSAI Number:</p><p><?php echo htmlspecialchars($branch_data['food_license_no'] ?: 'N/A'); ?></p></div><div><?php echo getDocumentThumbnail($branch_data['trade_license_path'], 'Trade License', 'fa-file-contract'); ?></div></div></div>
                        <div><h3 class="text-xl font-bold text-gray-800 mb-4">Associated Users</h3><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">Username</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Email</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Role</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><?php foreach ($associated_users as $user): ?><tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td></tr><?php endforeach; if(empty($associated_users)): ?><tr><td colspan="3" class="text-center py-4">No users are assigned to this branch.</td></tr><?php endif; ?></tbody></table></div></div>
                    </div>
                </div>
                <?php elseif ($add_mode || $edit_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $edit_mode ? 'Edit Branch' : 'Add New Branch'; ?></h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="id" value="<?php echo $branch_data['id']; ?>">
                        <input type="hidden" name="existing_trade_license_path" value="<?php echo htmlspecialchars($branch_data['trade_license_path'] ?? ''); ?>">
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Basic Information</legend><div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-2"><div class="md:col-span-2"><label class="block text-sm font-medium">Branch Name <span class="text-red-500">*</span></label><input type="text" name="name" value="<?php echo htmlspecialchars($branch_data['name']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div><div class="md:col-span-2"><label for="country" class="block text-sm font-medium">Country</label><select name="country" id="country" class="searchable-select mt-1 block w-full"><option value="">Select Country</option><?php foreach($countries as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select></div><div><label for="state" class="block text-sm font-medium">State</label><select name="state" id="state" class="searchable-select mt-1 block w-full"></select></div><div><label for="city" class="block text-sm font-medium">City</label><select name="city" id="city" class="searchable-select mt-1 block w-full"></select></div><div class="md:col-span-4"><label class="block text-sm font-medium">Address</label><textarea name="address" rows="2" class="mt-1 block w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($branch_data['address']); ?></textarea></div></div></fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Contact Details</legend><div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2"><div><label class="block text-sm font-medium">Contact Number 1</label><input type="text" name="contact_number" value="<?php echo htmlspecialchars($branch_data['contact_number']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">Contact Number 2</label><input type="text" name="contact_number_2" value="<?php echo htmlspecialchars($branch_data['contact_number_2']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($branch_data['email']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">Website</label><input type="text" name="website" value="<?php echo htmlspecialchars($branch_data['website']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div></div></fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Licenses & Documents</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2"><div><label class="block text-sm font-medium">GST Number</label><input type="text" name="gst_no" value="<?php echo htmlspecialchars($branch_data['gst_no']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">FSSAI Number</label><input type="text" name="food_license_no" value="<?php echo htmlspecialchars($branch_data['food_license_no']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div><div><label class="block text-sm font-medium">Trade License</label><input type="file" name="trade_license_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-50"></div></div></fieldset>
                        <div class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php if($branch_data['is_active']) echo 'checked'; ?> class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                        <div class="mt-6 flex justify-end space-x-3"><a href="manage_branches.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">Cancel</a><button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><?php echo $edit_mode ? 'Update Branch' : 'Save Branch'; ?></button></div>
                    </form>
                </div>

                <?php else: ?>
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">Existing Branches</h2>
                        <a href="manage_branches.php?action=add" class="inline-flex items-center justify-center sm:w-auto w-full py-2 px-4 border rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Branch</a>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <form method="GET" action="manage_branches.php">
                            <div class="flex items-center space-x-2">
                                <input type="text" name="search" placeholder="Search by name, city, state, GST..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <button type="submit" class="py-2 px-4 border rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-search"></i></button>
                                <a href="manage_branches.php" class="py-2 px-4 border rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>
                            </div>
                        </form>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php if (empty($branches_list)): ?>
                            <div class="md:col-span-2 xl:col-span-3 text-center py-10">
                                <i class="fas fa-search fa-3x text-gray-300"></i>
                                <p class="mt-4 text-gray-500">No branches found. Try adjusting your search.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($branches_list as $branch): ?>
                            <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($branch['name']); ?></h3>
                                        <?php echo $branch['is_active'] ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>'; ?>
                                    </div>
                                    <div class="mt-2 border-t pt-2 text-sm text-gray-600 space-y-2">
                                        <p><i class="fas fa-map-marker-alt w-5 mr-2 text-gray-400"></i><?php echo htmlspecialchars($branch['city'] . ', ' . $branch['state']); ?></p>
                                        <p><i class="fas fa-phone w-5 mr-2 text-gray-400"></i><?php echo htmlspecialchars($branch['contact_number'] ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t flex justify-end space-x-3 text-sm font-medium">
                                    <a href="manage_branches.php?action=view&id=<?php echo $branch['id']; ?>" class="text-gray-600 hover:text-indigo-800">Details</a>
                                    <a href="manage_branches.php?action=edit&id=<?php echo $branch['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                                    <?php if ($branch['is_active']): ?>
                                        <a href="manage_branches.php?action=delete&id=<?php echo $branch['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure? This may affect assigned users.');">Deactivate</a>
                                    <?php else: ?>
                                        <a href="manage_branches.php?action=reactivate&id=<?php echo $branch['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Are you sure you want to reactivate this branch?');">Reactivate</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
    // Page Loader
    window.addEventListener('load', function() {
        document.getElementById('page-loader').style.display = 'none';
    });

    $(document).ready(function() {
        // --- Sidebar Toggle ---
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarClose = document.getElementById('close-sidebar-btn');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }
        if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
        if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
        
        // --- Select2 and Location Logic ---
        $('.searchable-select').select2({ width: '100%' });
        const branchData = <?php echo json_encode($branch_data); ?>;

        async function fetchStates(countryId, selectedStateName = '') {
            $('#state').html('<option value="">Loading...</option>').prop('disabled', true);
            $('#city').html('<option value="">Select State First</option>').prop('disabled', true).trigger('change.select2');
            if (!countryId) {
                $('#state').html('<option value="">Select Country First</option>').prop('disabled',false).trigger('change.select2');
                return;
            }
            try {
                const response = await fetch(`get_locations.php?get=states&country_id=${countryId}`);
                const states = await response.json();
                $('#state').html('<option value="">Select State</option>').prop('disabled', false);
                let selectedStateId = null;
                states.forEach(state => {
                    const option = new Option(state.name, state.id);
                    $('#state').append(option);
                    if (state.name === selectedStateName) {
                        selectedStateId = state.id;
                    }
                });
                if(selectedStateId) {
                    $('#state').val(selectedStateId).trigger('change');
                } else {
                    $('#state').trigger('change.select2');
                }
            } catch (error) { console.error("Error fetching states:", error); }
        }

        async function fetchCities(stateId, selectedCityName = '') {
            $('#city').html('<option value="">Loading...</option>').prop('disabled', true);
            if (!stateId) {
                $('#city').html('<option value="">Select State First</option>').prop('disabled',false).trigger('change.select2');
                return;
            }
            try {
                const response = await fetch(`get_locations.php?get=cities&state_id=${stateId}`);
                const cities = await response.json();
                $('#city').html('<option value="">Select City</option>').prop('disabled', false);
                let selectedCityId = null;
                cities.forEach(city => {
                    const option = new Option(city.name, city.id);
                    $('#city').append(option);
                     if (city.name === selectedCityName) {
                        selectedCityId = city.id;
                    }
                });
                if(selectedCityId) {
                    $('#city').val(selectedCityId).trigger('change.select2');
                } else {
                    $('#city').trigger('change.select2');
                }
            } catch (error) { console.error("Error fetching cities:", error); }
        }

        $('#country').on('change', function() { fetchStates($(this).val()); });
        $('#state').on('change', function() {
            const targetCity = (branchData.state === $('#state option:selected').text()) ? branchData.city : '';
            fetchCities($(this).val(), targetCity);
        });
        
        if (branchData && branchData.id && branchData.country) {
            let initialCountryId = Array.from(document.getElementById('country').options).find(opt => opt.text === branchData.country)?.value;
            if (initialCountryId) {
                 $('#country').val(initialCountryId).trigger('change.select2');
                 fetchStates(initialCountryId, branchData.state);
            }
        } else {
            $('#country').val('1').trigger('change');
        }
    });
    </script>
</body>
</html>