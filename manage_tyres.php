<?php
session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

$form_message = "";

// --- Handle all form submissions ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $mysqli->begin_transaction();
    try {
        if ($action === 'add_tyre') {
            $tyre_number = trim($_POST['tyre_number']);
            $stmt_check = $mysqli->prepare("SELECT id FROM tyre_inventory WHERE tyre_number = ?");
            $stmt_check->bind_param("s", $tyre_number);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                throw new Exception("This Tyre Number is already in the inventory.");
            }
            $stmt_check->close();

            $sql = "INSERT INTO tyre_inventory (tyre_brand, tyre_model, tyre_number, purchase_date, purchase_cost, vendor_name) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssids", $_POST['tyre_brand'], $_POST['tyre_model'], $tyre_number, $_POST['purchase_date'], $_POST['purchase_cost'], $_POST['vendor_name']);
            if(!$stmt->execute()) throw new Exception($stmt->error);
            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">New tyre added to inventory.</div>';
        
        } elseif ($action === 'delete_tyre') {
            $tyre_id = intval($_POST['tyre_id_to_delete']);
            if ($tyre_id > 0) {
                $stmt_check = $mysqli->prepare("SELECT status FROM tyre_inventory WHERE id = ?");
                $stmt_check->bind_param("i", $tyre_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $tyre_to_delete = $result_check->fetch_assoc();
                $stmt_check->close();

                if ($tyre_to_delete && $tyre_to_delete['status'] === 'Mounted') {
                    throw new Exception("Cannot delete a tyre that is currently mounted on a vehicle.");
                }

                $stmt_delete = $mysqli->prepare("DELETE FROM tyre_inventory WHERE id = ?");
                $stmt_delete->bind_param("i", $tyre_id);
                if(!$stmt_delete->execute()) throw new Exception($stmt_delete->error);
                $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Tyre deleted successfully.</div>';
            }
        
        } elseif ($action === 'mount_tyre') {
            // Odometer Validation
            $stmt_check_odo = $mysqli->prepare("SELECT MAX(unmount_odometer) as last_odo FROM vehicle_tyres WHERE vehicle_id = ?");
            $stmt_check_odo->bind_param("i", $_POST['vehicle_id']);
            $stmt_check_odo->execute();
            $last_odo = $stmt_check_odo->get_result()->fetch_assoc()['last_odo'] ?? 0;
            $stmt_check_odo->close();
            if (intval($_POST['mount_odometer']) < $last_odo) {
                throw new Exception("Mount odometer ({$_POST['mount_odometer']}) cannot be less than the vehicle's last recorded odometer ({$last_odo}).");
            }

            $sql_mount = "INSERT INTO vehicle_tyres (vehicle_id, tyre_id, position, mount_date, mount_odometer) VALUES (?, ?, ?, ?, ?)";
            $stmt_mount = $mysqli->prepare($sql_mount);
            $stmt_mount->bind_param("iissi", $_POST['vehicle_id'], $_POST['tyre_id'], $_POST['position'], $_POST['mount_date'], $_POST['mount_odometer']);
            if(!$stmt_mount->execute()) throw new Exception($stmt_mount->error);
            $sql_update_status = "UPDATE tyre_inventory SET status = 'Mounted' WHERE id = ?";
            $stmt_update = $mysqli->prepare($sql_update_status);
            $stmt_update->bind_param("i", $_POST['tyre_id']);
            if(!$stmt_update->execute()) throw new Exception($stmt_update->error);
            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Tyre mounted successfully.</div>';
        
        } elseif ($action === 'add_and_mount_tyre') {
            // Odometer Validation
            $stmt_check_odo = $mysqli->prepare("SELECT MAX(unmount_odometer) as last_odo FROM vehicle_tyres WHERE vehicle_id = ?");
            $stmt_check_odo->bind_param("i", $_POST['vehicle_id']);
            $stmt_check_odo->execute();
            $last_odo = $stmt_check_odo->get_result()->fetch_assoc()['last_odo'] ?? 0;
            $stmt_check_odo->close();
            if (intval($_POST['mount_odometer']) < $last_odo) {
                throw new Exception("Mount odometer ({$_POST['mount_odometer']}) cannot be less than the vehicle's last recorded odometer ({$last_odo}).");
            }

            $tyre_number = trim($_POST['tyre_number']);
            $stmt_check = $mysqli->prepare("SELECT id FROM tyre_inventory WHERE tyre_number = ?");
            $stmt_check->bind_param("s", $tyre_number);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) throw new Exception("Tyre Number already exists.");
            $stmt_check->close();
            $sql_add = "INSERT INTO tyre_inventory (tyre_brand, tyre_model, tyre_number, purchase_date, purchase_cost, vendor_name, status) VALUES (?, ?, ?, ?, ?, ?, 'Mounted')";
            $stmt_add = $mysqli->prepare($sql_add);
            $stmt_add->bind_param("sssids", $_POST['tyre_brand'], $_POST['tyre_model'], $tyre_number, $_POST['purchase_date'], $_POST['purchase_cost'], $_POST['vendor_name']);
            if(!$stmt_add->execute()) throw new Exception($stmt_add->error);
            $new_tyre_id = $stmt_add->insert_id;
            $sql_mount = "INSERT INTO vehicle_tyres (vehicle_id, tyre_id, position, mount_date, mount_odometer) VALUES (?, ?, ?, ?, ?)";
            $stmt_mount = $mysqli->prepare($sql_mount);
            $stmt_mount->bind_param("iissi", $_POST['vehicle_id'], $new_tyre_id, $_POST['position'], $_POST['mount_date'], $_POST['mount_odometer']);
            if(!$stmt_mount->execute()) throw new Exception($stmt_mount->error);
            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">New tyre added and mounted successfully.</div>';
        
        } elseif ($action === 'unmount_tyre') {
            // Odometer Validation
            $stmt_check = $mysqli->prepare("SELECT mount_odometer FROM vehicle_tyres WHERE id = ?");
            $stmt_check->bind_param("i", $_POST['vehicle_tyre_id']);
            $stmt_check->execute();
            $mount_data = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            $mount_odometer = $mount_data['mount_odometer'] ?? 0;

            if (intval($_POST['unmount_odometer']) < $mount_odometer) {
                throw new Exception("Unmount odometer ({$_POST['unmount_odometer']}) cannot be less than mount odometer ({$mount_odometer}).");
            }

            $sql_unmount = "UPDATE vehicle_tyres SET unmount_date = ?, unmount_odometer = ?, unmount_reason = ? WHERE id = ?";
            $stmt_unmount = $mysqli->prepare($sql_unmount);
            $stmt_unmount->bind_param("sisi", $_POST['unmount_date'], $_POST['unmount_odometer'], $_POST['unmount_reason'], $_POST['vehicle_tyre_id']);
            if(!$stmt_unmount->execute()) throw new Exception($stmt_unmount->error);
            
            // If reason is Worn Out or Damaged, retire the tyre. Otherwise, return to stock.
            $new_status = 'In Stock';
            if (in_array($_POST['unmount_reason'], ['Worn Out', 'Damaged'])) {
                $new_status = 'Retired';
            }

            $sql_update_status = "UPDATE tyre_inventory SET status = ? WHERE id = ?";
            $stmt_update = $mysqli->prepare($sql_update_status);
            $stmt_update->bind_param("si", $new_status, $_POST['tyre_id_to_unmount']);
            if(!$stmt_update->execute()) throw new Exception($stmt_update->error);
            
            $form_message = '<div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50">Tyre unmounted. Status set to ' . $new_status . '.</div>';
        
        } elseif ($action === 'retread_tyre') {
            $sql_retread = "INSERT INTO tyre_retreading (tyre_id, retread_date, cost, vendor_name, description) VALUES (?, ?, ?, ?, ?)";
            $stmt_retread = $mysqli->prepare($sql_retread);
            $stmt_retread->bind_param("isiss", $_POST['tyre_id'], $_POST['retread_date'], $_POST['retread_cost'], $_POST['retread_vendor'], $_POST['retread_description']);
            if(!$stmt_retread->execute()) throw new Exception($stmt_retread->error);
            $form_message = '<div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50">Tyre retreading history added successfully.</div>';
        
        } elseif ($action === 'retire_tyre') {
            $tyre_id_to_retire = intval($_POST['tyre_id_to_retire']);
            // Only allow retiring tyres that are 'In Stock'
            $sql_retire = "UPDATE tyre_inventory SET status = 'Retired' WHERE id = ? AND status = 'In Stock'";
            $stmt_retire = $mysqli->prepare($sql_retire);
            $stmt_retire->bind_param("i", $tyre_id_to_retire);
            if(!$stmt_retire->execute()) throw new Exception($stmt_retire->error);
            $form_message = '<div class="p-4 mb-4 text-sm text-gray-800 rounded-lg bg-gray-50">Tyre marked as Retired.</div>';
        }

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
    }
}

// --- Pagination and Search Logic ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 9;
$offset = ($page - 1) * $records_per_page;
$search_term = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? 'All'); // Added status filter

$where_clauses = [];
$params = [];
$types = "";

// MODIFIED: Search by tyre number OR vehicle number
if (!empty($search_term)) {
    $where_clauses[] = "(ti.tyre_number LIKE ? OR v.vehicle_number LIKE ?)";
    $like_term = "%{$search_term}%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

// Added: Filter by status
if (!empty($status_filter) && $status_filter !== 'All') {
    $where_clauses[] = "ti.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// MODIFIED: Base query with JOINs for use in both COUNT and data fetching
$query_base_from = "FROM tyre_inventory ti 
                    LEFT JOIN vehicle_tyres vt ON ti.id = vt.tyre_id AND vt.unmount_date IS NULL 
                    LEFT JOIN vehicles v ON vt.vehicle_id = v.id";

// Get total records for pagination, using the JOINs
$count_sql = "SELECT COUNT(DISTINCT ti.id) " . $query_base_from . $where_sql;
$stmt_count = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);
$stmt_count->close();


// --- Data Fetching for Views ---
// Fetch the paginated list of tyres
$tyre_inventory_query = "
    SELECT 
        ti.*, 
        v.vehicle_number,
        (SELECT COUNT(*) FROM tyre_retreading tr WHERE tr.tyre_id = ti.id) as retread_count
    " . $query_base_from . "
    " . $where_sql . "
    GROUP BY ti.id
    ORDER BY ti.id DESC
    LIMIT ? OFFSET ?";

$list_params = $params;
$list_types = $types;
$list_params[] = $records_per_page;
$list_types .= "i";
$list_params[] = $offset;
$list_types .= "i";

$stmt_list = $mysqli->prepare($tyre_inventory_query);
if (!empty($list_types)) {
    $stmt_list->bind_param($list_types, ...$list_params);
}
$stmt_list->execute();
$tyre_inventory = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_list->close();

// REMOVED: N+1 query block. Details are now fetched via ajax_get_tyre_details.php

// Data for modals and selectors
// MODIFIED: Filter to show only 'Owned' vehicles in the layout selector
$vehicles = $mysqli->query("SELECT id, vehicle_number FROM vehicles WHERE is_active = 1 AND ownership_type = 'Owned' ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);
$in_stock_tyres = $mysqli->query("SELECT id, tyre_brand, tyre_model, tyre_number FROM tyre_inventory WHERE status = 'In Stock' ORDER BY tyre_brand")->fetch_all(MYSQLI_ASSOC);
$selected_vehicle_id = intval($_GET['vehicle_id'] ?? 0);
$mounted_tyres = [];
if ($selected_vehicle_id > 0) {
    $sql = "SELECT vt.id, vt.position, vt.mount_date, vt.mount_odometer, ti.tyre_brand, ti.tyre_model, ti.tyre_number, ti.id as tyre_id 
            FROM vehicle_tyres vt JOIN tyre_inventory ti ON vt.tyre_id = ti.id 
            WHERE vt.vehicle_id = ? AND vt.unmount_date IS NULL";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $selected_vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $mounted_tyres[$row['position']] = $row;
    }
    $stmt->close();
}
// TODO: This should be dynamic based on vehicle type
$axle_positions = ['Front-Left', 'Front-Right', 'Rear-Inner-Left', 'Rear-Outer-Left', 'Rear-Inner-Right', 'Rear-Outer-Right', 'Spare'];
function getStatusBadge($status) {
    $colors = ['In Stock' => 'bg-green-100 text-green-800', 'Mounted' => 'bg-blue-100 text-blue-800', 'Retired' => 'bg-gray-100 text-gray-800'];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tyre Management - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single { height: 42px; border-radius: 0.5rem; border: 1px solid #d1d5db; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 0.75rem; }
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100" x-data="tyreApp()">
        <?php include 'sidebar.php'; ?>

        <div class="flex flex-col flex-1 relative">
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Tyre Management</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>
                
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-6 overflow-x-auto">
                        <a href="#" @click.prevent="activeTab = 'inventory'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'inventory'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Tyre Inventory</a>
                        <a href="#" @click.prevent="activeTab = 'layout'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'layout'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Vehicle Tyre Layout</a>
                    </nav>
                </div>
                
                <div x-show="activeTab === 'inventory'" x-cloak class="space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">Tyre Stock</h2>
                        <button @click="isAddTyreModalOpen = true" class="py-2 px-4 bg-indigo-600 text-white rounded-lg shadow-sm hover:bg-indigo-700 transition w-full sm:w-auto">
                            <i class="fas fa-plus mr-2"></i>Add New Tyre
                        </button>
                    </div>

                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <form method="GET" class="flex flex-col md:flex-row gap-4">
                            <input type="hidden" name="tab" value="inventory"> <div class="flex-grow">
                                <label for="search" class="sr-only">Search</label>
                                <input type="text" id="search" name="search" placeholder="Search by tyre or vehicle number..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="shrink-0">
                                <label for="status" class="sr-only">Status</label>
                                <select name="status" id="status" class="w-full md:w-auto px-3 py-2 h-full border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All Statuses</option>
                                    <option value="In Stock" <?php if($status_filter == 'In Stock') echo 'selected'; ?>>In Stock</option>
                                    <option value="Mounted" <?php if($status_filter == 'Mounted') echo 'selected'; ?>>Mounted</option>
                                    <option value="Retired" <?php if($status_filter == 'Retired') echo 'selected'; ?>>Retired</option>
                                </select>
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-lg shrink-0"><i class="fas fa-search"></i> Filter</button>
                                <a href="manage_tyres.php" class="py-2 px-4 bg-gray-200 text-gray-700 rounded-lg shrink-0">Reset</a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="hidden xl:block bg-white rounded-xl shadow-md overflow-x-auto">
                         <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-medium text-gray-500">Tyre Number</th><th class="px-4 py-3 text-left font-medium text-gray-500">Brand & Model</th><th class="px-4 py-3 text-left font-medium text-gray-500">Status</th><th class="px-4 py-3 text-center font-medium text-gray-500">Retreads</th><th class="px-4 py-3 text-center font-medium text-gray-500">Actions</th></tr></thead>
                            <tbody class="divide-y">
                                <?php foreach($tyre_inventory as $tyre): ?>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars($tyre['tyre_number']); ?></td>
                                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($tyre['tyre_brand'] . ' ' . $tyre['tyre_model']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadge($tyre['status']); ?>"><?php echo htmlspecialchars($tyre['status']); ?></span>
                                        <?php if (!empty($tyre['vehicle_number'])): ?>
                                            <p class="text-xs text-gray-500 mt-1">on <?php echo htmlspecialchars($tyre['vehicle_number']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600"><?php echo $tyre['retread_count']; ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button @click="openViewDetailsModal(<?php echo $tyre['id']; ?>)" class="text-indigo-600 hover:text-indigo-900 text-xs py-1 px-2 rounded bg-indigo-100 font-medium">Details</button>
                                            <button @click="openRetreadModal(<?php echo $tyre['id']; ?>, '<?php echo htmlspecialchars($tyre['tyre_number']); ?>')" class="text-blue-600 hover:text-blue-900 text-xs py-1 px-2 rounded bg-blue-100 font-medium">Retread</button>
                                            
                                            <?php if ($tyre['status'] === 'In Stock'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to retire this tyre? This marks it as end-of-life.');" class="inline-block">
                                                    <input type="hidden" name="action" value="retire_tyre">
                                                    <input type="hidden" name="tyre_id_to_retire" value="<?php echo $tyre['id']; ?>">
                                                    <button type="submit" class="text-gray-600 hover:text-gray-900 text-xs py-1 px-2 rounded bg-gray-100 font-medium">Retire</button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this tyre? This is for data entry errors. Use \'Retire\' for worn out tyres.');" class="inline-block">
                                                    <input type="hidden" name="action" value="delete_tyre">
                                                    <input type="hidden" name="tyre_id_to_delete" value="<?php echo $tyre['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs py-1 px-2 rounded bg-red-100 font-medium">Delete</button>
                                                </form>
                                            <?php elseif ($tyre['status'] !== 'Mounted'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this tyre?');" class="inline-block">
                                                    <input type="hidden" name="action" value="delete_tyre">
                                                    <input type="hidden" name="tyre_id_to_delete" value="<?php echo $tyre['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs py-1 px-2 rounded bg-red-100 font-medium">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:hidden gap-6">
                         <?php foreach($tyre_inventory as $tyre): ?>
                            <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($tyre['tyre_number']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($tyre['tyre_brand'] . ' ' . $tyre['tyre_model']); ?></p>
                                        <?php if (!empty($tyre['vehicle_number'])): ?>
                                            <p class="text-xs font-medium text-blue-600 mt-1"><i class="fas fa-truck fa-fw mr-1"></i><?php echo htmlspecialchars($tyre['vehicle_number']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadge($tyre['status']); ?> shrink-0"><?php echo htmlspecialchars($tyre['status']); ?></span>
                                </div>
                                <div class="mt-4 border-t pt-4 flex-grow">
                                     <div class="flex justify-between text-sm"><p>Retreads:</p><p class="font-medium"><?php echo $tyre['retread_count']; ?></p></div>
                                </div>
                                <div class="mt-4 pt-4 border-t flex flex-wrap gap-2 justify-end">
                                    <button @click="openViewDetailsModal(<?php echo $tyre['id']; ?>)" class="text-indigo-600 text-xs py-1 px-3 rounded-full bg-indigo-100 font-medium">Details</button>
                                    <button @click="openRetreadModal(<?php echo $tyre['id']; ?>, '<?php echo htmlspecialchars($tyre['tyre_number']); ?>')" class="text-blue-600 text-xs py-1 px-3 rounded-full bg-blue-100 font-medium">Retread</button>
                                     
                                     <?php if ($tyre['status'] === 'In Stock'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to retire this tyre? This marks it as end-of-life.');" class="inline-block">
                                            <input type="hidden" name="action" value="retire_tyre">
                                            <input type="hidden" name="tyre_id_to_retire" value="<?php echo $tyre['id']; ?>">
                                            <button type="submit" class="text-gray-600 text-xs py-1 px-3 rounded-full bg-gray-100 font-medium">Retire</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this tyre? This is for data entry errors. Use \'Retire\' for worn out tyres.');" class="inline-block">
                                            <input type="hidden" name="action" value="delete_tyre">
                                            <input type="hidden" name="tyre_id_to_delete" value="<?php echo $tyre['id']; ?>">
                                            <button type="submit" class="text-red-600 text-xs py-1 px-3 rounded-full bg-red-100 font-medium">Delete</button>
                                        </form>
                                    <?php elseif ($tyre['status'] !== 'Mounted'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this tyre?');" class="inline-block">
                                            <input type="hidden" name="action" value="delete_tyre">
                                            <input type="hidden" name="tyre_id_to_delete" value="<?php echo $tyre['id']; ?>">
                                            <button type="submit" class="text-red-600 text-xs py-1 px-3 rounded-full bg-red-100 font-medium">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pt-4">
                        <nav class="flex justify-center">
                            <ul class="flex items-center -space-x-px h-10 text-sm">
                                <li>
                                    <a href="<?php echo ($page > 1) ? '?page='.($page-1).'&search='.urlencode($search_term).'&status='.urlencode($status_filter) : '#'; ?>"
                                       class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg <?php echo ($page > 1) ? 'hover:bg-gray-100 hover:text-gray-700' : 'opacity-50 cursor-not-allowed'; ?>">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li>
                                    <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>"
                                       class="flex items-center justify-center px-4 h-10 leading-tight <?php echo ($p == $page) ? 'text-blue-600 bg-blue-50 border-blue-300' : 'text-gray-500 bg-white border-gray-300'; ?> border hover:bg-gray-100 hover:text-gray-700">
                                        <?php echo $p; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li>
                                    <a href="<?php echo ($page < $total_pages) ? '?page='.($page+1).'&search='.urlencode($search_term).'&status='.urlencode($status_filter) : '#'; ?>"
                                       class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg <?php echo ($page < $total_pages) ? 'hover:bg-gray-100 hover:text-gray-700' : 'opacity-50 cursor-not-allowed'; ?>">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

                <div x-show="activeTab === 'layout'" x-cloak class="space-y-6">
                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <form method="GET">
                             <input type="hidden" name="tab" value="layout">
                             <label for="vehicle_id_select" class="block text-sm font-medium text-gray-700">Select Vehicle</label>
                             <select id="vehicle_id_select" name="vehicle_id" onchange="this.form.submit()" class="searchable-select mt-1 block w-full">
                                <option>Select a vehicle to see its tyre layout...</option>
                                <?php foreach($vehicles as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php if($selected_vehicle_id == $v['id']) echo 'selected'; ?>><?php echo htmlspecialchars($v['vehicle_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <?php if($selected_vehicle_id > 0): ?>
                    <div class="bg-white p-4 md:p-6 rounded-xl shadow-md">
                        <h3 class="text-lg font-semibold mb-4">Tyre Positions for <?php echo htmlspecialchars(array_values(array_filter($vehicles, fn($v) => $v['id'] == $selected_vehicle_id))[0]['vehicle_number']); ?></h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach($axle_positions as $position): ?>
                                <div class="border rounded-lg p-4 bg-gray-50/50">
                                    <p class="font-semibold text-gray-800"><?php echo $position; ?></p>
                                    <?php if(isset($mounted_tyres[$position])): $tyre = $mounted_tyres[$position]; ?>
                                        <div class="mt-2 text-sm space-y-1">
                                            <p><strong>Tyre:</strong> <?php echo htmlspecialchars($tyre['tyre_number'] . ' (' . $tyre['tyre_brand'] . ')'); ?></p>
                                            <p><strong>Mounted:</strong> <?php echo date('d-m-Y', strtotime($tyre['mount_date'])); ?> at <?php echo $tyre['mount_odometer']; ?> km</p>
                                            <button @click="openUnmountModal(<?php echo htmlspecialchars(json_encode($tyre)); ?>)" class="mt-2 text-xs py-1 px-3 bg-red-100 text-red-700 rounded-full hover:bg-red-200 font-medium">Unmount</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <p>Position Empty</p>
                                            <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0 mt-2">
                                                <button @click="openMountModal('<?php echo $position; ?>')" class="w-full sm:w-auto text-xs py-1 px-3 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 font-medium">Mount Existing</button>
                                                <button @click="openAddAndMountModal('<?php echo $position; ?>')" class="w-full sm:w-auto text-xs py-1 px-3 bg-green-100 text-green-700 rounded-full hover:bg-green-200 font-medium">Add & Mount</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php include 'footer.php'; ?>
            </main>
        </div>

        <div x-show="isAddTyreModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isAddTyreModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg w-full relative z-40"><form method="post"><input type="hidden" name="action" value="add_tyre"><div class="px-6 py-4"><h3 class="text-lg font-medium">Add New Tyre to Inventory</h3><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-sm font-medium">Tyre Number*</label><input type="text" name="tyre_number" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div><label class="text-sm font-medium">Brand*</label><input type="text" name="tyre_brand" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div><label class="text-sm font-medium">Model</label><input type="text" name="tyre_model" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
            <div><label class="text-sm font-medium">Purchase Date*</label><input type="date" name="purchase_date" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div><label class="text-sm font-medium">Purchase Cost*</label><input type="number" step="0.01" name="purchase_cost" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div><label class="text-sm font-medium">Vendor Name</label><input type="text" name="vendor_name" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
        </div></div><div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isAddTyreModalOpen = false" class="py-2 px-4 border rounded-md">Cancel</button><button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-md">Save Tyre</button></div></form></div></div></div>
        
        <div x-show="isMountModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isMountModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg w-full relative z-40"><form method="post"><input type="hidden" name="action" value="mount_tyre"><input type="hidden" name="vehicle_id" value="<?php echo $selected_vehicle_id; ?>"><input type="hidden" name="position" :value="selectedPosition"><div class="px-6 py-4"><h3 class="text-lg font-medium">Mount Existing Tyre to <span x-text="selectedPosition" class="font-bold"></span></h3><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="text-sm font-medium">Tyre (In Stock)</label><select name="tyre_id" class="searchable-select mt-1 block w-full" required><option value="">Select Tyre...</option><?php foreach($in_stock_tyres as $tyre): ?><option value="<?php echo $tyre['id']; ?>"><?php echo htmlspecialchars($tyre['tyre_number'] . ' (' . $tyre['tyre_brand'] . ')'); ?></option><?php endforeach; ?></select></div><div><label class="text-sm font-medium">Mount Date</label><input type="date" name="mount_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div><div class="md:col-span-2"><label class="text-sm font-medium">Vehicle Odometer (km)</label><input type="number" name="mount_odometer" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div></div></div><div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isMountModalOpen = false" class="py-2 px-4 border rounded-md">Cancel</button><button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-md">Mount Tyre</button></div></form></div></div></div>
        
        <div x-show="isAddAndMountModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isAddAndMountModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg w-full relative z-40"><form method="post"><input type="hidden" name="action" value="add_and_mount_tyre"><input type="hidden" name="vehicle_id" value="<?php echo $selected_vehicle_id; ?>"><input type="hidden" name="position" :value="selectedPosition"><div class="px-6 py-4"><h3 class="text-lg font-medium">Add & Mount New Tyre to <span x-text="selectedPosition" class="font-bold"></span></h3><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><p class="text-sm font-semibold text-gray-600 border-b pb-1">Tyre Purchase Details</p></div>
            <div><label class="text-sm font-medium">Tyre Number*</label><input type="text" name="tyre_number" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Brand*</label><input type="text" name="tyre_brand" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Model</label><input type="text" name="tyre_model" class="mt-1 block w-full border rounded-md p-2"></div><div><label class="text-sm font-medium">Purchase Date*</label><input type="date" name="purchase_date" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Purchase Cost*</label><input type="number" step="0.01" name="purchase_cost" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Vendor</label><input type="text" name="vendor_name" class="mt-1 block w-full border rounded-md p-2"></div>
            <div class="md:col-span-2"><p class="text-sm font-semibold text-gray-600 border-b pb-1 mt-2">Mounting Details</p></div>
            <div><label class="text-sm font-medium">Mount Date</label><input type="date" name="mount_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Odometer (km)</label><input type="number" name="mount_odometer" class="mt-1 block w-full border rounded-md p-2" required></div>
        </div></div><div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isAddAndMountModalOpen = false" class="py-2 px-4 border rounded-md">Cancel</button><button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-md">Save & Mount</button></div></form></div></div></div>
        
        <div x-show="isUnmountModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isUnmountModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg w-full relative z-40"><form method="post"><input type="hidden" name="action" value="unmount_tyre"><input type="hidden" name="vehicle_tyre_id" :value="vehicleTyreToUnmount.id"><input type="hidden" name="tyre_id_to_unmount" :value="vehicleTyreToUnmount.tyre_id"><div class="px-6 py-4"><h3 class="text-lg font-medium">Unmount Tyre from <span x-text="vehicleTyreToUnmount.position" class="font-bold"></span></h3><p class="text-sm">Tyre: <span x-text="vehicleTyreToUnmount.tyre_number"></span></p><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-sm font-medium">Unmount Date*</label><input type="date" name="unmount_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div><label class="text-sm font-medium">Vehicle Odometer (km)*</label><input type="number" name="unmount_odometer" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Reason for Unmount*</label>
                <select name="unmount_reason" class="mt-1 block w-full px-3 py-2 border rounded-md bg-white" required>
                    <option value="">Select reason...</option>
                    <option value="Worn Out">Worn Out (Retire Tyre)</option>
                    <option value="Damaged">Damaged (Retire Tyre)</option>
                    <option value="Rotation">Rotation (Back to Stock)</option>
                    <option value="Puncture">Puncture / Repair (Back to Stock)</option>
                    <option value="Other">Other (Back to Stock)</option>
                </select>
            </div>
        </div></div><div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isUnmountModalOpen = false" class="py-2 px-4 border rounded-md">Cancel</button><button type="submit" class="py-2 px-4 bg-red-600 text-white rounded-md">Confirm Unmount</button></div></form></div></div></div>
        
        <div x-show="isRetreadModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isRetreadModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg w-full relative z-40"><form method="post"><input type="hidden" name="action" value="retread_tyre"><input type="hidden" name="tyre_id" :value="selectedTyre.id"><div class="px-6 py-4"><h3 class="text-lg">Add Retreading Record for <span x-text="selectedTyre.number" class="font-bold"></span></h3><div class="mt-4 grid grid-cols-2 gap-4">
            <div><label class="text-sm font-medium">Retread Date</label><input type="date" name="retread_date" class="mt-1 block w-full border rounded-md p-2" required></div><div><label class="text-sm font-medium">Retread Cost</label><input type="number" step="0.01" name="retread_cost" class="mt-1 block w-full border rounded-md p-2" required></div><div class="col-span-2"><label class="text-sm font-medium">Vendor Name</label><input type="text" name="retread_vendor" class="mt-1 block w-full border rounded-md p-2"></div><div class="col-span-2"><label class="text-sm font-medium">Description</label><textarea name="retread_description" rows="2" class="mt-1 block w-full border rounded-md p-2"></textarea></div>
        </div></div><div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isRetreadModalOpen = false" class="py-2 px-4 border rounded-md">Cancel</button><button type="submit" class="py-2 px-4 bg-blue-600 text-white rounded-md">Save Record</button></div></form></div></div></div>
        
        <div x-show="isViewDetailsModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak><div class="flex items-center justify-center min-h-screen p-4"><div @click="isViewDetailsModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-3xl w-full relative z-40"><div class="p-6" x-show="tyreDetails.details">
            <div class="flex justify-between items-start"><h3 class="text-xl font-bold text-gray-800" x-text="tyreDetails.details.tyre_number || 'Loading...'"></h3><button @click="isViewDetailsModalOpen = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button></div>
            
            <div class="mt-4 border-t pt-4 text-sm" x-if="tyreDetails.details">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div><p class="text-xs text-gray-500">Purchase Date</p><p class="font-semibold" x-text="new Date(tyreDetails.details.purchase_date).toLocaleDateString('en-GB')"></p></div>
                    <div><p class="text-xs text-gray-500">Cost</p><p class="font-semibold" x-text="'₹' + parseFloat(tyreDetails.details.purchase_cost).toFixed(2)"></p></div>
                    <div><p class="text-xs text-gray-500">Vendor</p><p class="font-semibold" x-text="tyreDetails.details.vendor_name || 'N/A'"></p></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 p-4 bg-indigo-50 rounded-lg" x-if="tyreDetails.performance">
                    <div>
                        <p class="text-xs text-gray-500">Total KMs Run</p>
                        <p class="font-semibold text-lg text-indigo-700" x-text="tyreDetails.performance.total_km.toLocaleString() + ' km'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total Cost (incl. Retreads)</p>
                        <p class="font-semibold text-lg text-indigo-700" x-text="'₹' + tyreDetails.performance.total_cost.toLocaleString()"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Cost Per KM (CPK)</p>
                        <p class="font-semibold text-lg text-indigo-700" x-text="'₹' + tyreDetails.performance.cpk + ' /km'"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div><h4 class="font-semibold mb-2 text-gray-700">Mounting History</h4><ul class="space-y-3 text-xs" x-html="formatMountHistory(tyreDetails.mounts)"></ul></div>
                    <div><h4 class="font-semibold mb-2 text-gray-700">Retreading History</h4><ul class="space-y-3 text-xs" x-html="formatRetreadHistory(tyreDetails.retreads)"></ul></div>
                </div>
            </div>
            
            <div x-if="!tyreDetails.details" class="py-12 flex justify-center items-center">
                 <p class="text-gray-500">Loading details...</p>
            </div>

        </div></div></div></div>
    </div>
    
    <script>
        // --- Sidebar Toggle Script ---
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
            
            $('#vehicle_id_select.searchable-select').select2({ width: '100%' });
        });

        // --- Alpine.js logic ---
        function tyreApp() {
            // Helper function to get URL parameter by name
            const getUrlParam = (name) => {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }
            
            // Determine active tab: 'layout' if vehicle_id or tab=layout is in URL, otherwise 'inventory'
            const activeTab = (getUrlParam('vehicle_id') || getUrlParam('tab') === 'layout') ? 'layout' : 'inventory';

            return {
                activeTab: activeTab,
                isAddTyreModalOpen: false, isMountModalOpen: false, isUnmountModalOpen: false,
                isRetreadModalOpen: false, isViewDetailsModalOpen: false, isAddAndMountModalOpen: false,
                selectedPosition: '', vehicleTyreToUnmount: {}, selectedTyre: { id: null, number: '' },
                tyreDetails: { details: null, mounts:[], retreads:[], performance: { total_km: 0, total_cost: 0, cpk: 'N/A' } },

                openMountModal(position) { this.selectedPosition = position; this.isMountModalOpen = true; this.initSelect2InModal(); },
                openAddAndMountModal(position) { this.selectedPosition = position; this.isAddAndMountModalOpen = true; },
                openUnmountModal(vehicleTyre) { this.vehicleTyreToUnmount = vehicleTyre; this.isUnmountModalOpen = true; },
                openRetreadModal(tyreId, tyreNumber) { this.selectedTyre.id = tyreId; this.selectedTyre.number = tyreNumber; this.isRetreadModalOpen = true; },
                
                openViewDetailsModal(tyreId) {
                    this.tyreDetails = { details: null, mounts:[], retreads:[], performance: { total_km: 0, total_cost: 0, cpk: 'N/A' } };
                    this.isViewDetailsModalOpen = true;
                    
                    fetch(`ajax_get_tyre_details.php?tyre_id=${tyreId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                this.isViewDetailsModalOpen = false;
                            } else {
                                this.tyreDetails = data;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching tyre details:', error);
                            alert('Failed to load tyre details.');
                            this.isViewDetailsModalOpen = false;
                        });
                },
                
                initSelect2InModal() { this.$nextTick(() => { $('[name="tyre_id"].searchable-select').select2({ dropdownParent: $('[x-show="isMountModalOpen"]'), width: '100%' }); }); },
                
                formatMountHistory(mounts) {
                    if (!mounts || mounts.length === 0) return '<li class="text-gray-500">No mounting history.</li>';
                    return mounts.map(m => {
                        let unmountInfo = '<br><span class="text-green-600 font-medium">Currently Mounted</span>';
                        if (m.unmount_date) {
                            let kmRun = (m.unmount_odometer && m.mount_odometer) ? ` <span class="font-semibold text-blue-700">(${m.unmount_odometer - m.mount_odometer} km)</span>` : '';
                            let reason = m.unmount_reason ? ` <span class="text-gray-800">[${m.unmount_reason}]</span>` : '';
                            unmountInfo = `<br><span class="text-gray-600">Unmounted: ${new Date(m.unmount_date).toLocaleDateString('en-GB')}${reason}${kmRun}</span>`;
                        }
                        return `<li class="border-b pb-2"><strong>${new Date(m.mount_date).toLocaleDateString('en-GB')}</strong> on <strong class="text-indigo-600">${m.vehicle_number}</strong> (${m.position})${unmountInfo}</li>`;
                    }).join('');
                },
                formatRetreadHistory(retreads) { if (!retreads || retreads.length === 0) return '<li class="text-gray-500">No retreading history.</li>'; return retreads.map(r => `<li class="border-b pb-2"><strong>${new Date(r.retread_date).toLocaleDateString('en-GB')}</strong> - <strong class="text-indigo-600">₹${parseFloat(r.cost).toFixed(2)}</strong><br><span class="text-gray-600">Vendor: ${r.vendor_name || 'N/A'}</span></li>`).join(''); }
            }
        }
    </script>
</body>
</html>