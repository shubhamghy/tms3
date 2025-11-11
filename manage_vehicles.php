<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? '';
$can_manage = in_array($user_role, ['admin', 'manager']);
$is_admin = ($user_role === 'admin');

// --- Helper function for file uploads ---
function upload_vehicle_file($file_input_name, $vehicle_id, $existing_path = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/vehicles/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        
        if (!empty($existing_path) && file_exists($existing_path)) {
            @unlink($existing_path);
        }
        
        $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
        $new_file_name = "vehicle_{$vehicle_id}_{$file_input_name}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $target_file;
        }
    }
    return $existing_path;
}

$form_message = "";
$edit_mode = false;
$add_mode = false;
$view_mode = false;
$vehicle_data = [];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Use null coalescing for optional fields
    $registration_date = !empty($_POST['registration_date']) ? $_POST['registration_date'] : null;
    $fitness_expiry = !empty($_POST['fitness_expiry']) ? $_POST['fitness_expiry'] : null;
    $insurance_expiry = !empty($_POST['insurance_expiry']) ? $_POST['insurance_expiry'] : null;
    $tax_expiry = !empty($_POST['tax_expiry']) ? $_POST['tax_expiry'] : null;
    $puc_expiry = !empty($_POST['puc_expiry']) ? $_POST['puc_expiry'] : null;
    $permit_expiry = !empty($_POST['permit_expiry']) ? $_POST['permit_expiry'] : null;
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;

    if ($id > 0) { // Update
        $rc_doc_path = upload_vehicle_file('rc_doc_path', $id, $_POST['existing_rc_doc_path'] ?? '');
        $insurance_doc_path = upload_vehicle_file('insurance_doc_path', $id, $_POST['existing_insurance_doc_path'] ?? '');
        $permit_doc_path = upload_vehicle_file('permit_doc_path', $id, $_POST['existing_permit_doc_path'] ?? '');
        
        $sql = "UPDATE vehicles SET vehicle_number=?, vehicle_type=?, ownership_type=?, owner_name=?, owner_contact=?, driver_id=?, registration_date=?, fitness_expiry=?, insurance_expiry=?, tax_expiry=?, puc_expiry=?, permit_expiry=?, permit_details=?, rc_doc_path=?, insurance_doc_path=?, permit_doc_path=?, is_active=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssissssssssssii", $_POST['vehicle_number'], $_POST['vehicle_type'], $_POST['ownership_type'], $_POST['owner_name'], $_POST['owner_contact'], $driver_id, $registration_date, $fitness_expiry, $insurance_expiry, $tax_expiry, $puc_expiry, $permit_expiry, $_POST['permit_details'], $rc_doc_path, $insurance_doc_path, $permit_doc_path, $is_active, $id);
    } else { // Insert
        $sql = "INSERT INTO vehicles (vehicle_number, vehicle_type, ownership_type, owner_name, owner_contact, driver_id, registration_date, fitness_expiry, insurance_expiry, tax_expiry, puc_expiry, permit_expiry, permit_details, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssisssssssi", $_POST['vehicle_number'], $_POST['vehicle_type'], $_POST['ownership_type'], $_POST['owner_name'], $_POST['owner_contact'], $driver_id, $registration_date, $fitness_expiry, $insurance_expiry, $tax_expiry, $puc_expiry, $permit_expiry, $_POST['permit_details'], $is_active);
    }

    if ($stmt->execute()) {
        $vehicle_id = ($id > 0) ? $id : $stmt->insert_id;
        // Handle file uploads for new vehicle
        if ($id == 0) {
            $rc_doc_path = upload_vehicle_file('rc_doc_path', $vehicle_id);
            $insurance_doc_path = upload_vehicle_file('insurance_doc_path', $vehicle_id);
            $permit_doc_path = upload_vehicle_file('permit_doc_path', $vehicle_id);
            $mysqli->query("UPDATE vehicles SET rc_doc_path = '{$mysqli->real_escape_string($rc_doc_path)}', insurance_doc_path = '{$mysqli->real_escape_string($insurance_doc_path)}', permit_doc_path = '{$mysqli->real_escape_string($permit_doc_path)}' WHERE id = $vehicle_id");
        }
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Vehicle saved successfully!</div>';
        $add_mode = $edit_mode = false;
    } else {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}


// Handle GET requests
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] == 'add') { $add_mode = true; }
    elseif (($_GET['action'] == 'view' || $_GET['action'] == 'edit') && $id > 0) {
        $stmt = $mysqli->prepare("SELECT v.*, d.name as driver_name FROM vehicles v LEFT JOIN drivers d ON v.driver_id = d.id WHERE v.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $vehicle_data = $result->fetch_assoc();
            if ($_GET['action'] == 'view') { $view_mode = true; }
            if ($_GET['action'] == 'edit') { $edit_mode = true; }
        }
        $stmt->close();
    } elseif (($_GET['action'] == 'delete' || $_GET['action'] == 'reactivate') && $id > 0 && $is_admin) {
        $new_status = ($_GET['action'] == 'delete') ? 0 : 1;
        $action_word = ($new_status == 0) ? 'deactivated' : 'reactivated';
        $stmt = $mysqli->prepare("UPDATE vehicles SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        if($stmt->execute()){ $form_message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Vehicle {$action_word} successfully.</div>"; }
        else { $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error updating vehicle status.</div>"; }
        $stmt->close();
    }
}

// Data fetching for lists/dropdowns
$vehicles_list = [];
$drivers = $mysqli->query("SELECT id, name FROM drivers WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if (!$add_mode && !$edit_mode && !$view_mode) {
    // --- Pagination Logic ---
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9; // Display 9 vehicles per page
    $offset = ($page - 1) * $records_per_page;
    
    // --- Search Logic ---
    $search_term = trim($_GET['search'] ?? '');
    $where_sql = "";
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        // Search by vehicle number, type, or owner name
        $where_sql = " WHERE (v.vehicle_number LIKE ? OR v.vehicle_type LIKE ? OR v.owner_name LIKE ?)";
        $params = [$like_term, $like_term, $like_term];
        $types = "sss";
    }

    // Get total records with filtering
    $count_sql = "SELECT COUNT(v.id) FROM vehicles v" . $where_sql;
    $stmt_count = $mysqli->prepare($count_sql);
    if (!empty($params)) { $stmt_count->bind_param($types, ...$params); }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated vehicles
    $list_sql = "SELECT v.*, d.name as driver_name FROM vehicles v LEFT JOIN drivers d ON v.driver_id = d.id" . $where_sql . " ORDER BY v.id DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page; $types .= "i";
    $params[] = $offset; $types .= "i";
    
    $stmt_list = $mysqli->prepare($list_sql);
    if(!empty($types)) {
        // Use call_user_func_array for robust dynamic binding
        $bind_params = [];
        $bind_params[] = $types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt_list, 'bind_param'], $bind_params);
    }
    $stmt_list->execute();
    $vehicles_list = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single { height: 42px; border-radius: 0.5rem; border: 1px solid #d1d5db; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 0.75rem; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-col flex-1 relative">
         <header class="bg-white shadow-sm border-b border-gray-200 no-print">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                    <h1 class="text-xl font-semibold text-gray-800">Manage Vehicles</h1>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
            <?php if(!empty($form_message)) echo $form_message; ?>

            <?php if ($view_mode && $vehicle_data): ?>
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($vehicle_data['vehicle_number'] ?? ''); ?></h2>
                    <a href="manage_vehicles.php" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div><p class="text-sm text-gray-500">Vehicle Type</p><p class="font-semibold"><?php echo htmlspecialchars($vehicle_data['vehicle_type'] ?? ''); ?></p></div>
                    <div><p class="text-sm text-gray-500">Ownership</p><p class="font-semibold"><?php echo htmlspecialchars($vehicle_data['ownership_type'] ?? ''); ?></p></div>
                    <div><p class="text-sm text-gray-500">Owner Name</p><p class="font-semibold"><?php echo htmlspecialchars($vehicle_data['owner_name'] ?? ''); ?></p></div>
                    <div><p class="text-sm text-gray-500">Owner Contact</p><p class="font-semibold"><?php echo htmlspecialchars($vehicle_data['owner_contact'] ?? ''); ?></p></div>
                    <div><p class="text-sm text-gray-500">Assigned Driver</p><p class="font-semibold"><?php echo htmlspecialchars($vehicle_data['driver_name'] ?? 'N/A'); ?></p></div>
                    <div><p class="text-sm text-gray-500">Registration Date</p><p class="font-semibold"><?php echo $vehicle_data['registration_date'] ? date("d-m-Y", strtotime($vehicle_data['registration_date'])) : 'N/A'; ?></p></div>
                </div>
            </div>

            <?php elseif ($add_mode || $edit_mode): ?>
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                 <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $edit_mode ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h2>
                 <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="id" value="<?php echo $vehicle_data['id'] ?? ''; ?>">
                    <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Vehicle & Owner Info</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <div><label>Vehicle Number <span class="text-red-500">*</span></label><input type="text" name="vehicle_number" value="<?php echo htmlspecialchars($vehicle_data['vehicle_number'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2" required></div>
                        <div><label>Vehicle Type</label><input type="text" name="vehicle_type" value="<?php echo htmlspecialchars($vehicle_data['vehicle_type'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div>
                            <label class="block text-sm font-medium">Ownership</label>
                            <div class="mt-2 flex space-x-4">
                                <label class="flex items-center"><input type="radio" name="ownership_type" value="Owned" class="h-4 w-4" <?php if (($vehicle_data['ownership_type'] ?? 'Hired') == 'Owned') echo 'checked'; ?>><span class="ml-2">Owned</span></label>
                                <label class="flex items-center"><input type="radio" name="ownership_type" value="Hired" class="h-4 w-4" <?php if (($vehicle_data['ownership_type'] ?? 'Hired') == 'Hired') echo 'checked'; ?>><span class="ml-2">Hired</span></label>
                            </div>
                        </div>
                        <div><label>Owner Name</label><input type="text" name="owner_name" value="<?php echo htmlspecialchars($vehicle_data['owner_name'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Owner Contact</label><input type="text" name="owner_contact" value="<?php echo htmlspecialchars($vehicle_data['owner_contact'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Assigned Driver</label><select name="driver_id" class="searchable-select mt-1 block w-full"><option value="">Select Driver</option><?php foreach($drivers as $d): ?><option value="<?php echo $d['id']; ?>" <?php if(($vehicle_data['driver_id'] ?? '') == $d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select></div>
                    </div></fieldset>
                    <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Document Expiry</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <div><label>Registration Date</label><input type="date" name="registration_date" value="<?php echo htmlspecialchars($vehicle_data['registration_date'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Insurance Expiry</label><input type="date" name="insurance_expiry" value="<?php echo htmlspecialchars($vehicle_data['insurance_expiry'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Tax Expiry</label><input type="date" name="tax_expiry" value="<?php echo htmlspecialchars($vehicle_data['tax_expiry'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Fitness Expiry</label><input type="date" name="fitness_expiry" value="<?php echo htmlspecialchars($vehicle_data['fitness_expiry'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>Permit Expiry</label><input type="date" name="permit_expiry" value="<?php echo htmlspecialchars($vehicle_data['permit_expiry'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div><label>PUC Expiry</label><input type="date" name="puc_expiry" value="<?php echo htmlspecialchars($vehicle_data['puc_expiry'] ?? ''); ?>" class="mt-1 block w-full border rounded-md p-2"></div>
                        <div class="md:col-span-3"><label>Permit Details</label><textarea name="permit_details" rows="2" class="mt-1 block w-full border rounded-md p-2"><?php echo htmlspecialchars($vehicle_data['permit_details'] ?? ''); ?></textarea></div>
                    </div></fieldset>
                    <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Upload Documents</legend><div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <div><label>RC Document</label><input type="file" name="rc_doc" class="mt-1 block w-full text-sm"><input type="hidden" name="existing_rc_doc_path" value="<?php echo htmlspecialchars($vehicle_data['rc_doc_path'] ?? ''); ?>"></div>
                        <div><label>Insurance Document</label><input type="file" name="insurance_doc" class="mt-1 block w-full text-sm"><input type="hidden" name="existing_insurance_doc_path" value="<?php echo htmlspecialchars($vehicle_data['insurance_doc_path'] ?? ''); ?>"></div>
                        <div><label>Permit Document</label><input type="file" name="permit_doc" class="mt-1 block w-full text-sm"><input type="hidden" name="existing_permit_doc_path" value="<?php echo htmlspecialchars($vehicle_data['permit_doc_path'] ?? ''); ?>"></div>
                    </div></fieldset>
                    <div class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php if($vehicle_data['is_active'] ?? 1) echo 'checked'; ?> class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                    <div class="mt-6 flex justify-end space-x-3"><a href="manage_vehicles.php" class="py-2 px-4 border rounded-md">Cancel</a><button type="submit" class="py-2 px-6 bg-indigo-600 text-white rounded-md">Save Vehicle</button></div>
                 </form>
            </div>

            <?php else: ?>
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">Vehicle Fleet</h2>
                    <a href="manage_vehicles.php?action=add" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-plus mr-2"></i>Add Vehicle</a>
                </div>
                
                <div class="bg-white p-4 rounded-xl shadow-md">
                    <form method="GET">
                        <div class="flex space-x-2">
                            <input type="text" name="search" placeholder="Search by vehicle no, type, owner..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg">
                            <button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-search"></i></button>
                            <a href="manage_vehicles.php" class="py-2 px-4 bg-gray-100 rounded-lg">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($vehicles_list as $vehicle): ?>
                    <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></h3>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $vehicle['ownership_type'] == 'Owned' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo htmlspecialchars($vehicle['ownership_type']); ?></span>
                                    <?php echo $vehicle['is_active'] ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>'; ?>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></p>
                            <div class="mt-4 border-t pt-4 text-sm text-gray-600 space-y-2">
                                <p><i class="fas fa-user-tie w-5 mr-2 text-gray-400"></i>Owner: <?php echo htmlspecialchars($vehicle['owner_name']); ?></p>
                                <p><i class="fas fa-id-card w-5 mr-2 text-gray-400"></i>Driver: <?php echo htmlspecialchars($vehicle['driver_name'] ?? 'N/A'); ?></p>
                                
                                <p class="text-xs text-red-600 font-semibold mt-2">
                                    <?php 
                                        $expiries = ['Insurance' => 'insurance_expiry', 'Tax' => 'tax_expiry', 'Fitness' => 'fitness_expiry', 'Permit' => 'permit_expiry'];
                                        $expiring_docs = [];
                                        $expiring_soon = strtotime('+30 days');
                                        
                                        foreach($expiries as $doc => $col) {
                                            if (!empty($vehicle[$col]) && strtotime($vehicle[$col]) < $expiring_soon) {
                                                $expiring_docs[] = $doc;
                                            }
                                        }
                                        if (!empty($expiring_docs)) {
                                            echo '<i class="fas fa-exclamation-triangle mr-1"></i> Expiring Soon: ' . implode(', ', $expiring_docs);
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t flex justify-end space-x-3 text-sm font-medium">
                           <a href="vehicle_profile.php?id=<?php echo $vehicle['id']; ?>" class="text-gray-600 hover:text-indigo-800">Profile</a>
                            <a href="?action=edit&id=<?php echo $vehicle['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                            <?php if($is_admin): ?>
                                <?php if ($vehicle['is_active']): ?>
                                    <a href="?action=delete&id=<?php echo $vehicle['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-800">Deactivate</a>
                                <?php else: ?>
                                    <a href="?action=reactivate&id=<?php echo $vehicle['id']; ?>" onclick="return confirm('Are you sure?')" class="text-yellow-600 hover:text-yellow-800">Reactivate</a>
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
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                             <?php $query_params['page'] = $p; ?>
                             <a href="?<?php echo http_build_query($query_params); ?>" class="px-4 py-2 mx-1 text-sm font-medium <?php echo $p == $page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $p; ?></a>
                        <?php endfor; ?>
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
   // Mobile sidebar toggle
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