<?php
session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

// --- MODIFICATION: Updated File Upload Helper ---
/**
 * Processes a file upload, compresses images, and moves the file.
 *
 * @param string $file_key_name The key from the $_FILES array (e.g., 'invoice_doc_1').
 * @param int $log_id The ID of the maintenance log for naming.
 * @param string|null $existing_path The path to the existing file for this slot (if any) to be replaced.
 * @return string|null Returns the new file path on success, or the $existing_path if no new file was uploaded, or null if deletion was intended.
 */
function process_maintenance_upload($file_key_name, $log_id, $existing_path = null) {
    $target_dir = "uploads/maintenance/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

    // Check if a file was uploaded for this key
    if (isset($_FILES[$file_key_name]) && $_FILES[$file_key_name]['error'] == 0) {
        $file = $_FILES[$file_key_name];
        $tmp_name = $file['tmp_name'];
        $file_ext = strtolower(pathinfo(basename($file["name"]), PATHINFO_EXTENSION));
        $new_file_name = "log_{$log_id}_{$file_key_name}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;
        $file_type = mime_content_type($tmp_name);

        $upload_success = false;

        // Check if GD library functions exist before trying to compress
        $can_compress = function_exists('imagecreatefromjpeg') && function_exists('imagejpeg') && 
                        function_exists('imagecreatefrompng') && function_exists('imagepng') && 
                        function_exists('imagecreatefromgif');

        if ($can_compress && in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
            $image = null;
            if ($file_type == 'image/jpeg') {
                $image = @imagecreatefromjpeg($tmp_name);
            } elseif ($file_type == 'image/png') {
                $image = @imagecreatefrompng($tmp_name);
            } elseif ($file_type == 'image/gif') {
                $image = @imagecreatefromgif($tmp_name);
            }

            if ($image) {
                if ($file_type == 'image/jpeg') {
                    $upload_success = imagejpeg($image, $target_file, 75); // 75% quality
                } elseif ($file_type == 'image/png') {
                    imagealphablending($image, false); // Keep transparency
                    imagesavealpha($image, true);
                    $upload_success = imagepng($image, $target_file, 6); // Compression level 0-9
                } else {
                    // GD can read GIFs but not always compress them well, just move
                    $upload_success = move_uploaded_file($tmp_name, $target_file);
                }
                imagedestroy($image);
            } else {
                // Image might be corrupt, just move it
                $upload_success = move_uploaded_file($tmp_name, $target_file);
            }
        } else {
            // Not an image OR GD lib not installed, just move the file (e.g., PDF)
            $upload_success = move_uploaded_file($tmp_name, $target_file);
        }
        
        if ($upload_success) {
            // Delete old file if it exists and is different
            if (!empty($existing_path) && file_exists($existing_path) && $existing_path != $target_file) {
                @unlink($existing_path);
            }
            return $target_file;
        } else {
            // Upload failed, but keep the old path if it existed
            return $existing_path;
        }
    }

    // Handle file deletion request
    if (isset($_POST['delete_file_' . $file_key_name]) && !empty($existing_path)) {
        if (file_exists($existing_path)) {
            @unlink($existing_path);
        }
        return null; // Return null to remove it from the array
    }

    // No new file, no deletion request, so keep the old path
    return $existing_path;
}


// --- Helper function for status badges (from report) ---
function getStatusBadge($days_diff) {
    if ($days_diff === null) {
        return ''; // No badge if date is not set
    }
    if ($days_diff < 0) {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue (' . abs($days_diff) . ' days)</span>';
    } elseif ($days_diff <= 30) {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Due in ' . $days_diff . ' days</span>';
    } else {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Scheduled</span>';
    }
}


$form_message = "";
$edit_mode = false;
$add_mode = false;
$log_data = ['id' => '', 'vehicle_id' => '', 'service_date' => date('Y-m-d'), 'service_type' => '', 'odometer_reading' => '', 'service_cost' => '', 'vendor_name' => '', 'description' => '', 'next_service_date' => null, 'invoice_doc_paths' => '[]', 'tyre_number' => null];

$service_types_result = $mysqli->query("SELECT name FROM maintenance_service_types WHERE is_active = 1 ORDER BY name ASC");
$service_types = $service_types_result->fetch_all(MYSQLI_ASSOC);


// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id']);
    $service_date = $_POST['service_date'];
    $service_type = trim($_POST['service_type']);
    $odometer = intval($_POST['odometer_reading']);
    $cost = (float)$_POST['service_cost'];
    $vendor = trim($_POST['vendor_name']);
    $description = trim($_POST['description']);
    $next_service_date = !empty($_POST['next_service_date']) ? $_POST['next_service_date'] : null;
    $branch_id = $_SESSION['branch_id'];
    $created_by = $_SESSION['id'];
    $tyre_number = ($service_type == 'Tyre Replacement') ? trim($_POST['tyre_number']) : null;
    
    $final_paths = [];
    $log_id_for_upload = $id;

    if ($id > 0) { // Update
        $log_id_for_upload = $id;
        $current_paths = json_decode($_POST['existing_invoice_doc_paths_json'] ?? '[]', true);

        // Process up to 4 file slots
        $final_paths[0] = process_maintenance_upload('invoice_doc_1', $log_id_for_upload, $current_paths[0] ?? null);
        $final_paths[1] = process_maintenance_upload('invoice_doc_2', $log_id_for_upload, $current_paths[1] ?? null);
        $final_paths[2] = process_maintenance_upload('invoice_doc_3', $log_id_for_upload, $current_paths[2] ?? null);
        $final_paths[3] = process_maintenance_upload('invoice_doc_4', $log_id_for_upload, $current_paths[3] ?? null);

        // Clean up array (remove nulls from deletion) and re-index
        $paths_to_save = json_encode(array_values(array_filter($final_paths)));

        $sql = "UPDATE maintenance_logs SET vehicle_id=?, service_date=?, service_type=?, odometer_reading=?, service_cost=?, vendor_name=?, description=?, next_service_date=?, invoice_doc_paths=?, tyre_number=? WHERE id=? AND branch_id=?";
        $stmt = $mysqli->prepare($sql);
        
        // --- THIS IS THE FIX ---
        // Changed "issidssssssii" (13) to "issidsssssii" (12)
        $stmt->bind_param("issidsssssii", $vehicle_id, $service_date, $service_type, $odometer, $cost, $vendor, $description, $next_service_date, $paths_to_save, $tyre_number, $id, $branch_id);
        // --- END FIX ---
    
    } else { // Insert
        // Insert first without file paths, to get the new log ID
        $sql = "INSERT INTO maintenance_logs (vehicle_id, service_date, service_type, odometer_reading, service_cost, vendor_name, description, next_service_date, branch_id, created_by, tyre_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("issidsssiss", $vehicle_id, $service_date, $service_type, $odometer, $cost, $vendor, $description, $next_service_date, $branch_id, $created_by, $tyre_number);
    }

    if ($stmt->execute()) {
        $log_id = ($id > 0) ? $id : $stmt->insert_id;

        if ($id == 0) { // Handle file processing *after* insert
            $final_paths[0] = process_maintenance_upload('invoice_doc_1', $log_id, null);
            $final_paths[1] = process_maintenance_upload('invoice_doc_2', $log_id, null);
            $final_paths[2] = process_maintenance_upload('invoice_doc_3', $log_id, null);
            $final_paths[3] = process_maintenance_upload('invoice_doc_4', $log_id, null);
            
            $paths_to_save = json_encode(array_values(array_filter($final_paths)));
            if (!empty($paths_to_save) && $paths_to_save != '[]') {
                $mysqli->query("UPDATE maintenance_logs SET invoice_doc_paths = '{$mysqli->real_escape_string($paths_to_save)}' WHERE id = $log_id");
            }
        }

        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Maintenance log saved successfully!</div>';
        $add_mode = $edit_mode = false;
    } else {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Handle GET Actions
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] == 'add') { $add_mode = true; }
    elseif ($_GET['action'] == 'edit' && $id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $log_data = $result->fetch_assoc();
            $edit_mode = true;
        }
        $stmt->close();
    }
}

// Data Fetching for Lists/Dropdowns
$maintenance_logs = [];
$vehicles = $mysqli->query("SELECT id, vehicle_number FROM vehicles WHERE is_active = 1 ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);

// Dashboard Stats
$stats = [
    'cost_month' => 0,
    'overdue' => 0,
    'due_soon' => 0
];
if (!$add_mode && !$edit_mode) {
    $branch_id = $_SESSION['branch_id'];
    $first_day_month = date('Y-m-01');
    $last_day_month = date('Y-m-t');
    
    $cost_result = $mysqli->query("SELECT SUM(service_cost) as total_cost FROM maintenance_logs WHERE branch_id = $branch_id AND service_date BETWEEN '$first_day_month' AND '$last_day_month'");
    $stats['cost_month'] = $cost_result->fetch_assoc()['total_cost'] ?? 0;

    $overdue_result = $mysqli->query("SELECT COUNT(id) as count FROM maintenance_logs WHERE branch_id = $branch_id AND next_service_date < CURDATE()");
    $stats['overdue'] = $overdue_result->fetch_assoc()['count'] ?? 0;
    
    $due_soon_result = $mysqli->query("SELECT COUNT(id) as count FROM maintenance_logs WHERE branch_id = $branch_id AND next_service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stats['due_soon'] = $due_soon_result->fetch_assoc()['count'] ?? 0;
}


if (!$add_mode && !$edit_mode) {
    // Search & Pagination for list view
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;
    $search_term = trim($_GET['search'] ?? '');
    
    $where_sql = " WHERE m.branch_id = ?";
    $params = [$_SESSION['branch_id']];
    $types = "i";

    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        $where_sql .= " AND (v.vehicle_number LIKE ? OR m.service_type LIKE ? OR m.vendor_name LIKE ?)";
        array_push($params, $like_term, $like_term, $like_term);
        $types .= "sss";
    }

    $count_sql = "SELECT COUNT(m.id) FROM maintenance_logs m LEFT JOIN vehicles v ON m.vehicle_id = v.id" . $where_sql;
    $stmt_count = $mysqli->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);

    $list_sql = "SELECT m.*, v.vehicle_number, DATEDIFF(m.next_service_date, CURDATE()) as days_diff 
                 FROM maintenance_logs m 
                 JOIN vehicles v ON m.vehicle_id = v.id" . $where_sql . " 
                 ORDER BY m.service_date DESC, m.id DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page; $types .= "i";
    $params[] = $offset; $types .= "i";
    
    $stmt_list = $mysqli->prepare($list_sql);
    $bind_params = [];
    $bind_params[] = $types;
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt_list, 'bind_param'], $bind_params);
    
    $stmt_list->execute();
    $maintenance_logs = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Maintenance - TMS</title>
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
        .conditional-field { display: none; }
        .file-slot {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.25rem;
        }
        .file-slot .existing-file {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }
        .file-slot .existing-file a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        .file-slot .existing-file a:hover { text-decoration: underline; }
        .file-slot .delete-check {
            margin-left: 0.75rem;
        }
        .file-slot .delete-check label {
            font-size: 0.875rem;
            color: #ef4444;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Service & Maintenance</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($add_mode || $edit_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $edit_mode ? 'Edit Maintenance Log' : 'Add New Maintenance Log'; ?></h2>
                    <form method="POST" class="space-y-6" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $log_data['id']; ?>">
                        
                        <input type="hidden" name="existing_invoice_doc_paths_json" value="<?php echo htmlspecialchars($log_data['invoice_doc_paths'] ?? '[]'); ?>">
                        
                        <?php 
                        $existing_paths = json_decode($log_data['invoice_doc_paths'] ?? '[]', true);
                        ?>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <div>
                                <label class="block text-sm font-medium">Vehicle <span class="text-red-500">*</span></label>
                                <select id="vehicle_id" name="vehicle_id" class="searchable-select mt-1 block w-full" required>
                                    <option value="">Select Vehicle</option>
                                    <?php foreach($vehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php if($log_data['vehicle_id'] == $v['id']) echo 'selected'; ?>><?php echo htmlspecialchars($v['vehicle_number']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium">Service Date <span class="text-red-500">*</span></label>
                                <input type="date" name="service_date" value="<?php echo htmlspecialchars($log_data['service_date']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium">Service Type <span class="text-red-500">*</span></label>
                                <select id="service_type" name="service_type" class="mt-1 block w-full px-3 py-2 border rounded-lg bg-white" required>
                                    <option value="">Select Type</option>
                                    <?php foreach($service_types as $type): ?>
                                    <option value="<?php echo $type['name']; ?>" <?php if($log_data['service_type'] == $type['name']) echo 'selected'; ?>><?php echo $type['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium">Odometer Reading</label>
                                <input type="number" id="odometer_reading" name="odometer_reading" value="<?php echo htmlspecialchars($log_data['odometer_reading']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" min="0" placeholder="e.g., 125000">
                                <p id="odometer_warning" class="text-xs text-red-600 mt-1 hidden">Warning: Odometer is less than last entry.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Service Cost <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="service_cost" value="<?php echo htmlspecialchars($log_data['service_cost']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium">Vendor / Garage</label>
                                <input type="text" name="vendor_name" value="<?php echo htmlspecialchars($log_data['vendor_name']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium">Description</label>
                                <textarea name="description" rows="2" class="mt-1 block w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($log_data['description']); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Next Service Due</label>
                                <input type="date" name="next_service_date" value="<?php echo htmlspecialchars($log_data['next_service_date']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg">
                            </div>

                            <div id="tyre_number_field" class="conditional-field" <?php if($log_data['service_type'] == 'Tyre Replacement') echo 'style="display:block;"'; ?>>
                                <label class="block text-sm font-medium">Tyre Number / Position</label>
                                <input type="text" name="tyre_number" value="<?php echo htmlspecialchars($log_data['tyre_number'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg">
                            </div>
                        </div>

                        <fieldset class="border p-4 rounded-lg">
                            <legend class="text-lg font-semibold px-2">Service Invoices (Max 4)</legend>
                            <p class="text-sm text-gray-500 mb-4">Upload invoices, bills, or photos. Images will be compressed.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php for ($i = 1; $i <= 4; $i++): 
                                    $existing_file_path = $existing_paths[$i - 1] ?? null;
                                ?>
                                <div>
                                    <label class="block text-sm font-medium">Invoice/File <?php echo $i; ?></label>
                                    <div class="file-slot">
                                        <input type="file" name="invoice_doc_<?php echo $i; ?>" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                                        <?php if (!empty($existing_file_path)): ?>
                                        <div class="existing-file mt-2">
                                            <a href="<?php echo htmlspecialchars($existing_file_path); ?>" target="_blank">
                                                <i class="fas fa-file-alt"></i> <?php echo "View File " . $i; ?>
                                            </a>
                                            <span class="delete-check">
                                                <input type="checkbox" name="delete_file_invoice_doc_<?php echo $i; ?>" id="delete_file_<?php echo $i; ?>">
                                                <label for="delete_file_<?php echo $i; ?>"><i class="fas fa-trash-alt"></i></label>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </fieldset>

                        <div class="mt-6 flex justify-end space-x-3"><a href="manage_maintenance.php" class="py-2 px-4 border rounded-md">Cancel</a><button type="submit" class="py-2 px-6 bg-indigo-600 text-white rounded-md">Save Log</button></div>
                    </form>
                </div>
                <?php else: ?>
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Maintenance History</h2>
                        <a href="manage_maintenance.php?action=add" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-plus mr-2"></i>Log Service</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-xl shadow-md">
                            <h4 class="text-sm font-medium text-gray-500">Cost This Month</h4>
                            <p class="text-2xl font-bold text-gray-800">₹<?php echo number_format($stats['cost_month'], 2); ?></p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-md">
                            <h4 class="text-sm font-medium text-gray-500">Services Overdue</h4>
                            <p class="text-2xl font-bold text-red-600"><?php echo $stats['overdue']; ?></p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-md">
                            <h4 class="text-sm font-medium text-gray-500">Services Due Soon</h4>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['due_soon']; ?></p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl shadow-md"><form method="GET"><div class="flex space-x-2"><input type="text" name="search" placeholder="Search by vehicle, service type..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg"><button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-search"></i></button><a href="manage_maintenance.php" class="py-2 px-4 bg-gray-100 rounded-lg">Reset</a></div></form></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach($maintenance_logs as $log): ?>
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars($log['service_type']); ?></p>
                                    <h3 class="font-bold text-lg text-gray-800 mt-2"><?php echo htmlspecialchars($log['vehicle_number']); ?></h3>
                                </div>
                                <p class="text-sm text-gray-500"><?php echo date("d M, Y", strtotime($log['service_date'])); ?></p>
                            </div>
                            <div class="mt-4 border-t pt-4 text-sm text-gray-600 space-y-2">
                                <div class="flex justify-between"><p>Cost:</p><p class="font-bold text-lg">₹<?php echo number_format($log['service_cost'], 2); ?></p></div>
                                <div class="flex justify-between"><p>Odometer:</p><p><?php echo htmlspecialchars($log['odometer_reading']); ?> km</p></div>
                                <div class="flex justify-between"><p>Vendor:</p><p><?php echo htmlspecialchars($log['vendor_name'] ?? 'N/A'); ?></p></div>
                                
                                <?php if($log['next_service_date']): ?>
                                <div class="flex justify-between items-center">
                                    <p>Next Due:</p>
                                    <div class="text-right">
                                        <span class="font-semibold"><?php echo date("d M, Y", strtotime($log['next_service_date'])); ?></span>
                                        <div class="mt-1"><?php echo getStatusBadge($log['days_diff']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php 
                                $invoice_paths = json_decode($log['invoice_doc_paths'] ?? '[]', true);
                                if (!empty($invoice_paths)):
                                ?>
                                <div class="flex justify-between">
                                    <p>Invoices:</p>
                                    <div class="flex flex-col items-end space-y-1">
                                        <?php foreach ($invoice_paths as $index => $path): ?>
                                            <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="text-indigo-600 hover:underline">
                                                File <?php echo $index + 1; ?> <i class="fas fa-external-link-alt fa-xs"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($log['tyre_number'])): ?>
                                    <div class="flex justify-between"><p>Tyre:</p><p><?php echo htmlspecialchars($log['tyre_number']); ?></p></div>
                                <?php endif; ?>
                            </div>
                             <div class="mt-4 pt-4 border-t flex justify-end space-x-3 text-sm font-medium">
                                <a href="?action=edit&id=<?php echo $log['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarClose = document.getElementById('close-sidebar-btn');

        function toggleSidebar() {
            if (sidebar && sidebarOverlay) {
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }
        }
        if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
        if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
        
        $('.searchable-select').select2({ width: '100%' });

        // Odometer Validation
        var last_odometer = 0;
        
        function fetchOdometer(vehicle_id) {
            if(vehicle_id) {
                // Pass exclude_id if in edit mode
                var exclude_id = <?php echo $edit_mode ? ($log_data['id'] ?? 0) : 0; ?>;
                $.get('get_vehicle_odometer.php?vehicle_id=' + vehicle_id + '&exclude_id=' + exclude_id, function(data) {
                    last_odometer = parseInt(data.last_odometer) || 0;
                    $('#odometer_reading').attr('min', last_odometer);
                    if(last_odometer > 0) {
                        $('#odometer_reading').attr('placeholder', 'Must be >= ' + last_odometer);
                    } else {
                        $('#odometer_reading').attr('placeholder', 'e.g., 125000');
                    }
                }, 'json');
            } else {
                last_odometer = 0;
                $('#odometer_reading').attr('min', 0);
                $('#odometer_reading').attr('placeholder', 'e.g., 125000');
            }
        }

        $('#vehicle_id').on('change', function() {
            fetchOdometer($(this).val());
        });
        
        // Trigger change on load if a vehicle is already selected (for edit mode)
        if ($('#vehicle_id').val()) {
             fetchOdometer($('#vehicle_id').val());
        }

        $('#odometer_reading').on('input', function() {
            var current_odometer = parseInt($(this).val()) || 0;
            if (current_odometer > 0 && current_odometer < last_odometer) {
                $('#odometer_warning').removeClass('hidden');
            } else {
                $('#odometer_warning').addClass('hidden');
            }
        });

        // Conditional Fields
        $('#service_type').on('change', function() {
            if ($(this).val() == 'Tyre Replacement') {
                $('#tyre_number_field').slideDown();
            } else {
                $('#tyre_number_field').slideUp();
            }
        });
        // Trigger on page load for edit mode
        $('#service_type').trigger('change');

    });
    window.addEventListener('load', function() {
        // Simple loader hide (if you have one)
        // document.getElementById('page-loader').style.display = 'none';
    });
    </script>
</body>
</html>