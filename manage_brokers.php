<?php
// --- For Debugging: Temporarily add these lines to see detailed errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -----------------------------------------------------------------------

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "config.php";

// --- Role-based access control ---
$user_role = $_SESSION['role'] ?? '';
$is_admin = ($user_role === 'admin');
$can_manage = in_array($user_role, ['admin', 'manager']);

// --- Helper function for file uploads ---
function upload_broker_file($file_input_name, $broker_id, $existing_path = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/brokers/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        
        $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
        $new_file_name = "broker_{$broker_id}_{$file_input_name}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            if (!empty($existing_path) && file_exists($existing_path)) {
                @unlink($existing_path);
            }
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
    return '';
}

// --- Page State Management ---
$form_message = "";
$edit_mode = false;
$view_mode = false;
$add_mode = false;
$broker_data = [
    'id' => '', 'name' => '', 'address' => '', 'city' => '', 'state' => '', 'contact_person' => '', 'contact_number' => '',
    'gst_no' => '', 'pan_no' => '', 'aadhaar_no' => '', 'bank_account_no' => '', 'bank_ifsc_code' => '', 'is_active' => 1,
    'pan_doc_path' => '', 'gst_doc_path' => '', 'bank_doc_path' => '', 'aadhaar_doc_path' => '',
    'visibility_type' => 'global', 'branch_id' => null
];

// --- Form Processing for Add/Edit ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $visibility_type = $_POST['visibility_type'] ?? 'global';
    $branch_id = null;
    if ($visibility_type === 'local') {
        $branch_id = $is_admin ? (intval($_POST['branch_id']) ?: null) : $_SESSION['branch_id'];
    }

    if ($_POST['action'] == 'edit' && $can_manage && $id > 0) {
        $sql = "UPDATE brokers SET name=?, address=?, city=?, state=?, contact_person=?, contact_number=?, gst_no=?, pan_no=?, aadhaar_no=?, bank_account_no=?, bank_ifsc_code=?, is_active=?, pan_doc_path=?, gst_doc_path=?, bank_doc_path=?, aadhaar_doc_path=?, visibility_type=?, branch_id=? WHERE id=?";
        if ($stmt = $mysqli->prepare($sql)) {
            $pan_path = upload_broker_file('pan_doc', $id, $_POST['existing_pan_doc_path']);
            $gst_path = upload_broker_file('gst_doc', $id, $_POST['existing_gst_doc_path']);
            $bank_path = upload_broker_file('bank_doc', $id, $_POST['existing_bank_doc_path']);
            $aadhaar_path = upload_broker_file('aadhaar_doc', $id, $_POST['existing_aadhaar_doc_path']);
            
            $stmt->bind_param("sssssssssssisssssii", $name, $_POST['address'], $_POST['city'], $_POST['state'], $_POST['contact_person'], $_POST['contact_number'], $_POST['gst_no'], $_POST['pan_no'], $_POST['aadhaar_no'], $_POST['bank_account_no'], $_POST['bank_ifsc_code'], $is_active, $pan_path, $gst_path, $bank_path, $aadhaar_path, $visibility_type, $branch_id, $id);
            if ($stmt->execute()) {
                $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Broker updated successfully!</div>';
            } else {
                $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error updating broker.</div>';
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] == 'add') {
        $sql = "INSERT INTO brokers (name, address, city, state, contact_person, contact_number, gst_no, pan_no, aadhaar_no, bank_account_no, bank_ifsc_code, is_active, visibility_type, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sssssssssssisi", $name, $_POST['address'], $_POST['city'], $_POST['state'], $_POST['contact_person'], $_POST['contact_number'], $_POST['gst_no'], $_POST['pan_no'], $_POST['aadhaar_no'], $_POST['bank_account_no'], $_POST['bank_ifsc_code'], $is_active, $visibility_type, $branch_id);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $pan_path = upload_broker_file('pan_doc', $new_id);
                $gst_path = upload_broker_file('gst_doc', $new_id);
                $bank_path = upload_broker_file('bank_doc', $new_id);
                $aadhaar_path = upload_broker_file('aadhaar_doc', $new_id);
                
                $update_sql = "UPDATE brokers SET pan_doc_path=?, gst_doc_path=?, bank_doc_path=?, aadhaar_doc_path=? WHERE id=?";
                $update_stmt = $mysqli->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $pan_path, $gst_path, $bank_path, $aadhaar_path, $new_id);
                $update_stmt->execute();
                $update_stmt->close();
                $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">New broker added successfully!</div>';
            } else {
                $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error adding broker: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
}

// Handle GET requests for actions like delete/reactivate
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);
    if ($action == 'add') { $add_mode = true; }
    elseif (($action == 'view' || $action == 'edit') && $id > 0) {
        if ($action == 'edit' && !$can_manage) { header("location: manage_brokers.php?message=denied"); exit; }
        $stmt = $mysqli->prepare("SELECT * FROM brokers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $broker_data = $result->fetch_assoc();
            if ($action == 'view') $view_mode = true;
            if ($action == 'edit') $edit_mode = true;
        }
        $stmt->close();
    } elseif ($action == 'delete' && $id > 0 && $is_admin) {
        $stmt = $mysqli->prepare("UPDATE brokers SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){ $form_message = "<div class='p-4 mb-4 text-sm text-yellow-800 bg-yellow-50 rounded-lg'>Broker deactivated successfully.</div>"; }
        else { $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error deactivating broker.</div>"; }
        $stmt->close();
    } elseif ($action == 'reactivate' && $id > 0 && $is_admin) {
        $stmt = $mysqli->prepare("UPDATE brokers SET is_active = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){ $form_message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Broker reactivated successfully.</div>"; }
        else { $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error reactivating broker.</div>"; }
        $stmt->close();
    }
}

// Fetch data for lists / dropdowns
$brokers_list = [];
$branches = [];
if ($edit_mode || $add_mode) {
    if ($is_admin) {
        $branches = $mysqli->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
    }
} elseif (!$view_mode) {
    // --- CORRECTED DATA FETCHING FOR LIST VIEW ---
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;

    $base_sql = "FROM brokers b LEFT JOIN branches br ON b.branch_id = br.id";
    $where_sql = "";
    $params = [];
    $types = "";

    if (!$is_admin) {
        $user_branch_id = $_SESSION['branch_id'] ?? 0;
        $where_sql = " WHERE (b.visibility_type = 'global' OR (b.visibility_type = 'local' AND b.branch_id = ?))";
        $params[] = $user_branch_id;
        $types .= "i";
    }

    // Get total records with filtering
    $total_records_sql = "SELECT COUNT(b.id) " . $base_sql . $where_sql;
    $stmt_count = $mysqli->prepare($total_records_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch brokers for the current page with filtering
    $list_sql = "SELECT b.*, br.name as branch_name " . $base_sql . $where_sql . " ORDER BY b.name ASC LIMIT ? OFFSET ?";
    $stmt_list = $mysqli->prepare($list_sql);
    
    // Append pagination params to the existing params and types
    $params[] = $records_per_page;
    $types .= "i";
    $params[] = $offset;
    $types .= "i";

    $stmt_list->bind_param($types, ...$params);
    $stmt_list->execute();
    $result = $stmt_list->get_result();
    if ($result) {
        $brokers_list = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_list->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brokers - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        @media print {
            body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <header class="bg-white shadow-sm border-b border-gray-200 no-print">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Manage Brokers</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($view_mode): ?>
                 <div class="bg-white p-8 rounded-lg shadow-md print-area">
                    <div class="flex justify-between items-center mb-6 no-print">
                        <h2 class="text-2xl font-bold text-gray-800">Broker Details</h2>
                        <a href="manage_brokers.php" class="inline-flex items-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"><i class="fas fa-arrow-left mr-2"></i> Back to List</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div><p class="font-medium text-gray-500">Name:</p><p><?php echo htmlspecialchars($broker_data['name'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">Contact Person:</p><p><?php echo htmlspecialchars($broker_data['contact_person'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">Contact Number:</p><p><?php echo htmlspecialchars($broker_data['contact_number'] ?? ''); ?></p></div>
                        <div class="md:col-span-3"><p class="font-medium text-gray-500">Address:</p><p><?php echo nl2br(htmlspecialchars($broker_data['address'] ?? '')); ?></p></div>
                        <div><p class="font-medium text-gray-500">Location:</p><p><?php echo htmlspecialchars($broker_data['city'] . ', ' . $broker_data['state']); ?></p></div>
                        <div><p class="font-medium text-gray-500">GST No:</p><p><?php echo htmlspecialchars($broker_data['gst_no'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">PAN No:</p><p><?php echo htmlspecialchars($broker_data['pan_no'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">Aadhaar No:</p><p><?php echo htmlspecialchars($broker_data['aadhaar_no'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">Bank Account:</p><p><?php echo htmlspecialchars($broker_data['bank_account_no'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">IFSC Code:</p><p><?php echo htmlspecialchars($broker_data['bank_ifsc_code'] ?? ''); ?></p></div>
                        <div><p class="font-medium text-gray-500">PAN Doc:</p><?php if(!empty($broker_data['pan_doc_path'])): ?><a href="<?php echo htmlspecialchars($broker_data['pan_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View</a><?php else: echo 'N/A'; endif; ?></div>
                        <div><p class="font-medium text-gray-500">GST Doc:</p><?php if(!empty($broker_data['gst_doc_path'])): ?><a href="<?php echo htmlspecialchars($broker_data['gst_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View</a><?php else: echo 'N/A'; endif; ?></div>
                        <div><p class="font-medium text-gray-500">Bank Doc:</p><?php if(!empty($broker_data['bank_doc_path'])): ?><a href="<?php echo htmlspecialchars($broker_data['bank_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View</a><?php else: echo 'N/A'; endif; ?></div>
                        <div><p class="font-medium text-gray-500">Aadhaar Doc:</p><?php if(!empty($broker_data['aadhaar_doc_path'])): ?><a href="<?php echo htmlspecialchars($broker_data['aadhaar_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View</a><?php else: echo 'N/A'; endif; ?></div>
                    </div>
                </div>
                <?php elseif ($edit_mode || $add_mode): ?>
                 <div class="bg-white p-8 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6"><?php echo $edit_mode ? 'Edit Broker' : 'Add New Broker'; ?></h2>
                    <form action="manage_brokers.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $broker_data['id']; ?>">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium">Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($broker_data['name'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                            <div><label class="block text-sm font-medium">Contact Person</label><input type="text" name="contact_person" value="<?php echo htmlspecialchars($broker_data['contact_person'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Contact Number</label><input type="text" name="contact_number" value="<?php echo htmlspecialchars($broker_data['contact_number'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div class="md:col-span-3"><label class="block text-sm font-medium">Address</label><textarea name="address" rows="2" class="mt-1 block w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($broker_data['address'] ?? ''); ?></textarea></div>
                            <div><label class="block text-sm font-medium">City</label><input type="text" name="city" value="<?php echo htmlspecialchars($broker_data['city'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">State</label><input type="text" name="state" value="<?php echo htmlspecialchars($broker_data['state'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">GST No.</label><input type="text" name="gst_no" value="<?php echo htmlspecialchars($broker_data['gst_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">PAN No.</label><input type="text" name="pan_no" value="<?php echo htmlspecialchars($broker_data['pan_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Aadhaar No.</label><input type="text" name="aadhaar_no" value="<?php echo htmlspecialchars($broker_data['aadhaar_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Bank Account No.</label><input type="text" name="bank_account_no" value="<?php echo htmlspecialchars($broker_data['bank_account_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Bank IFSC Code</label><input type="text" name="bank_ifsc_code" value="<?php echo htmlspecialchars($broker_data['bank_ifsc_code'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">PAN Document</label><input type="file" name="pan_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0"></div>
                            <div><label class="block text-sm font-medium">GST Document</label><input type="file" name="gst_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0"></div>
                            <div><label class="block text-sm font-medium">Bank Document</label><input type="file" name="bank_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0"></div>
                             <div><label class="block text-sm font-medium">Aadhaar Document</label><input type="file" name="aadhaar_doc" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0"></div>
                            <div class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php if($broker_data['is_active']) echo 'checked'; ?> class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                        </div>
                        <div class="mt-6 text-right">
                            <a href="manage_brokers.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">Cancel</a>
                            <button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><?php echo $edit_mode ? 'Update Broker' : 'Save Broker'; ?></button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4"><h2 class="text-2xl font-bold text-gray-800">Existing Brokers</h2><a href="manage_brokers.php?action=add" class="inline-flex items-center py-2 px-4 border rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Broker</a></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                         <?php foreach ($brokers_list as $broker): ?>
                        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($broker['name']); ?></h3>
                                    <div class="flex items-center gap-x-2 flex-shrink-0">
                                        <?php if ($broker['visibility_type'] == 'local'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" title="Branch: <?php echo htmlspecialchars($broker['branch_name']); ?>">Local</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Global</span>
                                        <?php endif; ?>
                                        <?php echo $broker['is_active'] ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>'; ?>
                                    </div>
                                </div>
                                <div class="mt-2 border-t pt-2 text-sm text-gray-600 space-y-2"><p><i class="fas fa-map-marker-alt w-5 mr-2 text-gray-400"></i><?php echo htmlspecialchars($broker['city'] . ', ' . $broker['state']); ?></p><p><i class="fas fa-user w-5 mr-2 text-gray-400"></i><?php echo htmlspecialchars($broker['contact_person'] ?: 'N/A'); ?></p></div>
                            </div>
                            <div class="mt-4 flex justify-end space-x-3 text-sm font-medium">
                                <a href="manage_brokers.php?action=view&id=<?php echo $broker['id']; ?>" class="text-indigo-600 hover:text-indigo-800">Details</a>
                                <?php if ($can_manage): ?>
                                    <a href="manage_brokers.php?action=edit&id=<?php echo $broker['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                                    <?php if($is_admin): ?>
                                        <?php if ($broker['is_active']): ?>
                                            <a href="manage_brokers.php?action=delete&id=<?php echo $broker['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to deactivate this broker?');">Deactivate</a>
                                        <?php else: ?>
                                            <a href="manage_brokers.php?action=reactivate&id=<?php echo $broker['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Are you sure you want to reactivate this broker?');">Reactivate</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-between items-center no-print"><span class="text-sm text-gray-700">Showing <?php echo $total_records > 0 ? ($offset + 1) : 0; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results</span><div class="flex"><?php if ($page > 1): ?><a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Previous</a><?php endif; ?><?php if ($page < $total_pages): ?><a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Next</a><?php endif; ?></div></div>
                </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    </body>
</html>