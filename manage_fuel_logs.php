<?php
session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

$form_message = "";
$edit_mode = false;
$add_mode = false;
$log_data = ['id' => '', 'vehicle_id' => '', 'log_date' => date('Y-m-d'), 'odometer_reading' => '', 'fuel_quantity' => '', 'fuel_rate' => '', 'fuel_station' => '', 'filled_by_driver_id' => null];
$expense_categories = ['Fuel', 'Maintenance', 'Toll', 'Salary', 'Office Rent', 'Utilities', 'Repair', 'Other'];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id']);
    $log_date = $_POST['log_date'];
    $odometer = intval($_POST['odometer_reading']);
    $quantity = (float)$_POST['fuel_quantity'];
    $rate = (float)$_POST['fuel_rate'];
    $total_cost = $quantity * $rate;
    $station = trim($_POST['fuel_station']);
    $driver_id = !empty($_POST['filled_by_driver_id']) ? intval($_POST['filled_by_driver_id']) : null;
    $branch_id = $_SESSION['branch_id'];
    $created_by = $_SESSION['id'];

    if ($id > 0) { // Update
        $sql = "UPDATE fuel_logs SET vehicle_id=?, log_date=?, odometer_reading=?, fuel_quantity=?, fuel_rate=?, total_cost=?, fuel_station=?, filled_by_driver_id=? WHERE id=? AND branch_id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isiddsdiii", $vehicle_id, $log_date, $odometer, $quantity, $rate, $total_cost, $station, $driver_id, $id, $branch_id);
    } else { // Insert
        $sql = "INSERT INTO fuel_logs (vehicle_id, log_date, odometer_reading, fuel_quantity, fuel_rate, total_cost, fuel_station, filled_by_driver_id, branch_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isiddsdiii", $vehicle_id, $log_date, $odometer, $quantity, $rate, $total_cost, $station, $driver_id, $branch_id, $created_by);
    }

    if ($stmt->execute()) {
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Fuel log saved successfully!</div>';
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
        $stmt = $mysqli->prepare("SELECT * FROM fuel_logs WHERE id = ?");
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
$fuel_logs = [];
$vehicles = $mysqli->query("SELECT id, vehicle_number FROM vehicles WHERE is_active = 1 ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);
$drivers = $mysqli->query("SELECT id, name FROM drivers WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if (!$add_mode && !$edit_mode) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;
    $search_term = trim($_GET['search'] ?? '');
    
    // Base WHERE clause for branch security
    $where_sql = " WHERE f.branch_id = ?";
    $params = [$_SESSION['branch_id']];
    $types = "i";

    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        $where_sql .= " AND (v.vehicle_number LIKE ? OR d.name LIKE ?)";
        array_push($params, $like_term, $like_term);
        $types .= "ss";
    }

    // Get total records with filtering
    $count_sql = "SELECT COUNT(f.id) FROM fuel_logs f LEFT JOIN vehicles v ON f.vehicle_id = v.id LEFT JOIN drivers d ON f.filled_by_driver_id = d.id" . $where_sql;
    $stmt_count = $mysqli->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch logs for the current page
    $list_sql = "SELECT f.*, v.vehicle_number, d.name as driver_name 
                 FROM fuel_logs f 
                 JOIN vehicles v ON f.vehicle_id = v.id 
                 LEFT JOIN drivers d ON f.filled_by_driver_id = d.id" . $where_sql . " 
                 ORDER BY f.log_date DESC, f.id DESC LIMIT ? OFFSET ?";
    
    $params[] = $records_per_page;
    $types .= "i";
    $params[] = $offset;
    $types .= "i";
    
    $stmt_list = $mysqli->prepare($list_sql);
    // Use call_user_func_array for robust binding
    $bind_params = [];
    $bind_params[] = $types;
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt_list, 'bind_param'], $bind_params);
    
    $stmt_list->execute();
    $result = $stmt_list->get_result();
    if ($result) {
        $fuel_logs = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_list->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fuel Logs - TMS</title>
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
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Fuel Logs</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($add_mode || $edit_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $edit_mode ? 'Edit Fuel Log' : 'Add New Fuel Log'; ?></h2>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="id" value="<?php echo $log_data['id']; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium">Vehicle <span class="text-red-500">*</span></label><select name="vehicle_id" class="searchable-select mt-1 block w-full" required><option value="">Select Vehicle</option><?php foreach($vehicles as $v): ?><option value="<?php echo $v['id']; ?>" <?php if($log_data['vehicle_id'] == $v['id']) echo 'selected'; ?>><?php echo htmlspecialchars($v['vehicle_number']); ?></option><?php endforeach; ?></select></div>
                            <div><label class="block text-sm font-medium">Date <span class="text-red-500">*</span></label><input type="date" name="log_date" value="<?php echo htmlspecialchars($log_data['log_date']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required></div>
                            <div><label class="block text-sm font-medium">Odometer Reading <span class="text-red-500">*</span></label><input type="number" name="odometer_reading" value="<?php echo htmlspecialchars($log_data['odometer_reading']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required></div>
                            <div><label class="block text-sm font-medium">Fuel Quantity (Ltr) <span class="text-red-500">*</span></label><input type="number" step="0.01" name="fuel_quantity" value="<?php echo htmlspecialchars($log_data['fuel_quantity']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required></div>
                            <div><label class="block text-sm font-medium">Fuel Rate (per Ltr) <span class="text-red-500">*</span></label><input type="number" step="0.01" name="fuel_rate" value="<?php echo htmlspecialchars($log_data['fuel_rate']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg" required></div>
                            <div><label class="block text-sm font-medium">Filled by (Driver)</label><select name="filled_by_driver_id" class="searchable-select mt-1 block w-full"><option value="">Select Driver</option><?php foreach($drivers as $d): ?><option value="<?php echo $d['id']; ?>" <?php if($log_data['filled_by_driver_id'] == $d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="md:col-span-3"><label class="block text-sm font-medium">Fuel Station</label><input type="text" name="fuel_station" value="<?php echo htmlspecialchars($log_data['fuel_station']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg"></div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3"><a href="manage_fuel_logs.php" class="py-2 px-4 border rounded-md">Cancel</a><button type="submit" class="py-2 px-6 bg-indigo-600 text-white rounded-md">Save Log</button></div>
                    </form>
                </div>
                <?php else: ?>
                <div class="space-y-6">
                    <div class="flex justify-between items-center"><h2 class="text-2xl font-bold text-gray-800">Fuel Log History</h2><a href="manage_fuel_logs.php?action=add" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-plus mr-2"></i>Add Fuel Log</a></div>
                    <div class="bg-white p-4 rounded-xl shadow-md"><form method="GET"><div class="flex space-x-2"><input type="text" name="search" placeholder="Search by vehicle or driver..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg"><button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-lg"><i class="fas fa-search"></i></button><a href="manage_fuel_logs.php" class="py-2 px-4 bg-gray-100 rounded-lg">Reset</a></div></form></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach($fuel_logs as $log): ?>
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($log['vehicle_number']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo date("d M, Y", strtotime($log['log_date'])); ?></p>
                                </div>
                                <a href="?action=edit&id=<?php echo $log['id']; ?>" class="text-green-600 text-sm">Edit</a>
                            </div>
                            <div class="mt-4 border-t pt-4 text-sm text-gray-600 space-y-2">
                                <div class="flex justify-between"><p>Total Cost:</p><p class="font-bold text-lg">₹<?php echo number_format($log['total_cost'], 2); ?></p></div>
                                <div class="flex justify-between"><p>Quantity:</p><p><?php echo htmlspecialchars($log['fuel_quantity']); ?> Ltr</p></div>
                                <div class="flex justify-between"><p>Rate:</p><p>₹<?php echo htmlspecialchars($log['fuel_rate']); ?>/Ltr</p></div>
                                <div class="flex justify-between"><p>Odometer:</p><p><?php echo htmlspecialchars($log['odometer_reading']); ?> km</p></div>
                                <div class="flex justify-between"><p>Driver:</p><p><?php echo htmlspecialchars($log['driver_name'] ?? 'N/A'); ?></p></div>
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
    });
    window.addEventListener('load', function() {
        document.getElementById('page-loader').style.display = 'none';
    });
    </script>
</body>
</html>