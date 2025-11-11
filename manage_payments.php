<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$current_branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0; // Added branch_id
$can_manage = in_array($user_role, ['admin', 'manager']);

if (!$can_manage) {
    header("location: dashboard.php");
    exit;
}

$form_message = "";
$edit_mode = false;
$shipment_data = [];
$payment_data = [];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['shipment_id'])) {
    $shipment_id = intval($_POST['shipment_id']);
    $created_by_id = $_SESSION['id'];
    $payment_date = date('Y-m-d');

    $mysqli->begin_transaction();
    try {
        $delete_stmt = $mysqli->prepare("DELETE FROM shipment_payments WHERE shipment_id = ?");
        $delete_stmt->bind_param("i", $shipment_id);
        if (!$delete_stmt->execute()) { throw new Exception("Error clearing old payment data."); }
        $delete_stmt->close();

        $payment_sql = "INSERT INTO shipment_payments (shipment_id, payment_type, amount, billing_method, rate, payment_date, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $payment_stmt = $mysqli->prepare($payment_sql);
        $charge_sql = "INSERT INTO shipment_payments (shipment_id, payment_type, amount, payment_date, created_by_id) VALUES (?, ?, ?, ?, ?)";
        $charge_stmt = $mysqli->prepare($charge_sql);

        // Party Billing
        $payment_type_billing = 'Billing Rate';
        $party_billing_method = $_POST['party_billing_method'];
        $party_rate = (float)$_POST['party_rate'];
        $party_total_billing = (float)$_POST['party_total_billing'];
        $payment_stmt->bind_param("isdsdsi", $shipment_id, $payment_type_billing, $party_total_billing, $party_billing_method, $party_rate, $payment_date, $created_by_id);
        if (!$payment_stmt->execute()) { throw new Exception("Error saving Billing Rate."); }

        // Lorry Hire
        $payment_type_hire = 'Lorry Hire';
        $vehicle_billing_method = $_POST['vehicle_billing_method'];
        $vehicle_rate = (float)$_POST['vehicle_rate'];
        $vehicle_total_hire = (float)$_POST['vehicle_total_hire'];
        $payment_stmt->bind_param("isdsdsi", $shipment_id, $payment_type_hire, $vehicle_total_hire, $vehicle_billing_method, $vehicle_rate, $payment_date, $created_by_id);
        if (!$payment_stmt->execute()) { throw new Exception("Error saving Lorry Hire."); }
        
        $payment_stmt->close();

        // Other charges
        $other_charges = ['Advance Cash' => $_POST['advance_cash'], 'Advance Diesel' => $_POST['advance_diesel'], 'Labour Charge' => $_POST['labour_charge'], 'Dala Charge' => $_POST['dala_charge'], 'Lifting Charge' => $_POST['lifting_charge']];
        foreach ($other_charges as $type => $amount) {
            if (!empty($amount)) {
                $amount_decimal = (float)$amount;
                $charge_stmt->bind_param("isdsi", $shipment_id, $type, $amount_decimal, $payment_date, $created_by_id);
                if (!$charge_stmt->execute()) { throw new Exception("Error saving payment type: $type"); }
            }
        }
        $charge_stmt->close();

        // Update shipment status
        $update_sql = "UPDATE shipments SET payment_entry_status='Done' WHERE id=?";
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param("i", $shipment_id);
        if (!$update_stmt->execute()) { throw new Exception("Error updating shipment status."); }
        $update_stmt->close();

        $mysqli->commit();
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Payment details saved successfully!</div>';
        $edit_mode = false;

    } catch (Exception $e) {
        $mysqli->rollback();
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
    }
}

// --- Branch Filtering Logic ---
$branch_sql_filter = "";
$branch_param_type = "";
$branch_param_value = null;

if ($user_role !== 'admin' && $current_branch_id > 0) {
    $branch_sql_filter = " AND s.branch_id = ?";
    $branch_param_type = "i"; // 'i' for integer
    $branch_param_value = $current_branch_id;
}

// Handle GET request for editing
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $shipment_id = intval($_GET['id']);
    // MODIFIED: Added $branch_sql_filter
    $sql = "SELECT s.*, consignor.name AS consignor_name, consignee.name AS consignee_name, v.vehicle_number, d.name as driver_name FROM shipments s JOIN parties consignor ON s.consignor_id = consignor.id JOIN parties consignee ON s.consignee_id = consignee.id LEFT JOIN vehicles v ON s.vehicle_id = v.id LEFT JOIN drivers d ON s.driver_id = d.id WHERE s.id = ? $branch_sql_filter";
    
    if ($stmt = $mysqli->prepare($sql)) {
        // MODIFIED: Bind branch_id if it exists
        if ($branch_param_value !== null) {
            $stmt->bind_param("i" . $branch_param_type, $shipment_id, $branch_param_value);
        } else {
            $stmt->bind_param("i", $shipment_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $shipment_data = $result->fetch_assoc();
            $payment_result = $mysqli->query("SELECT payment_type, amount, billing_method, rate FROM shipment_payments WHERE shipment_id = $shipment_id");
            while($row = $payment_result->fetch_assoc()){
                $payment_data[$row['payment_type']] = $row;
            }
        } else {
            $edit_mode = false; 
            // MODIFIED: Updated message to be more accurate
            $form_message = '<div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50">Shipment not found or you do not have permission to view it.</div>';
        }
        $stmt->close();
    }
}


// --- Pagination & Data Fetching for Lists ---
$records_per_page = 10;
$reverify_payments = [];
$pending_payments = [];
$completed_payments = [];

// Get search terms
$search_reverify = isset($_GET['search_reverify']) ? trim($_GET['search_reverify']) : '';
$search_pending = isset($_GET['search_pending']) ? trim($_GET['search_pending']) : '';
$search_done = isset($_GET['search_done']) ? trim($_GET['search_done']) : '';

// Get current page numbers
$page_reverify = isset($_GET['page_reverify']) ? (int)$_GET['page_reverify'] : 1;
$page_pending = isset($_GET['page_pending']) ? (int)$_GET['page_pending'] : 1;
$page_done = isset($_GET['page_done']) ? (int)$_GET['page_done'] : 1;

if (!$edit_mode) {
    // Reverify Payments
    $offset_reverify = ($page_reverify - 1) * $records_per_page;
    $search_param_reverify = "%{$search_reverify}%";
    
    // MODIFIED: Added $branch_sql_filter
    $sql_total_reverify = "SELECT COUNT(*) FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Reverify' AND s.consignment_no LIKE ? $branch_sql_filter";
    $stmt_total_reverify = $mysqli->prepare($sql_total_reverify);
    // MODIFIED: Bind branch_id if it exists
    if ($branch_param_value !== null) {
        $stmt_total_reverify->bind_param("s" . $branch_param_type, $search_param_reverify, $branch_param_value);
    } else {
        $stmt_total_reverify->bind_param("s", $search_param_reverify);
    }
    $stmt_total_reverify->execute();
    $total_reverify = $stmt_total_reverify->get_result()->fetch_row()[0];
    $stmt_total_reverify->close();
    $total_pages_reverify = ceil($total_reverify / $records_per_page);
    
    // MODIFIED: Added $branch_sql_filter
    $sql_reverify = "SELECT s.id, s.consignment_no, s.consignment_date, p.name as consignor_name, s.origin, s.destination FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Reverify' AND s.consignment_no LIKE ? $branch_sql_filter ORDER BY s.consignment_date DESC LIMIT ?, ?";
    if ($stmt = $mysqli->prepare($sql_reverify)) {
        // MODIFIED: Bind branch_id if it exists
        if ($branch_param_value !== null) {
            $stmt->bind_param("s" . $branch_param_type . "ii", $search_param_reverify, $branch_param_value, $offset_reverify, $records_per_page);
        } else {
            $stmt->bind_param("sii", $search_param_reverify, $offset_reverify, $records_per_page);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $reverify_payments[] = $row; }
        $stmt->close();
    }

    // Pending Payments
    $offset_pending = ($page_pending - 1) * $records_per_page;
    $search_param_pending = "%{$search_pending}%";
    
    // MODIFIED: Added $branch_sql_filter
    $sql_total_pending = "SELECT COUNT(*) FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Pending' AND s.consignment_no LIKE ? $branch_sql_filter";
    $stmt_total_pending = $mysqli->prepare($sql_total_pending);
    // MODIFIED: Bind branch_id if it exists
    if ($branch_param_value !== null) {
        $stmt_total_pending->bind_param("s" . $branch_param_type, $search_param_pending, $branch_param_value);
    } else {
        $stmt_total_pending->bind_param("s", $search_param_pending);
    }
    $stmt_total_pending->execute();
    $total_pending = $stmt_total_pending->get_result()->fetch_row()[0];
    $stmt_total_pending->close();
    $total_pages_pending = ceil($total_pending / $records_per_page);

    // MODIFIED: Added $branch_sql_filter
    $sql_pending = "SELECT s.id, s.consignment_no, s.consignment_date, p.name as consignor_name, s.origin, s.destination FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Pending' AND s.consignment_no LIKE ? $branch_sql_filter ORDER BY s.consignment_date DESC LIMIT ?, ?";
    if ($stmt = $mysqli->prepare($sql_pending)) {
        // MODIFIED: Bind branch_id if it exists
        if ($branch_param_value !== null) {
            $stmt->bind_param("s" . $branch_param_type . "ii", $search_param_pending, $branch_param_value, $offset_pending, $records_per_page);
        } else {
            $stmt->bind_param("sii", $search_param_pending, $offset_pending, $records_per_page);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $pending_payments[] = $row; }
        $stmt->close();
    }

    // Completed Payments
    $offset_done = ($page_done - 1) * $records_per_page;
    $search_param_done = "%{$search_done}%";
    
    // MODIFIED: Added $branch_sql_filter
    $sql_total_done = "SELECT COUNT(*) FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Done' AND s.consignment_no LIKE ? $branch_sql_filter";
    $stmt_total_done = $mysqli->prepare($sql_total_done);
    // MODIFIED: Bind branch_id if it exists
    if ($branch_param_value !== null) {
        $stmt_total_done->bind_param("s" . $branch_param_type, $search_param_done, $branch_param_value);
    } else {
        $stmt_total_done->bind_param("s", $search_param_done);
    }
    $stmt_total_done->execute();
    $total_done = $stmt_total_done->get_result()->fetch_row()[0];
    $stmt_total_done->close();
    $total_pages_done = ceil($total_done / $records_per_page);
    
    // MODIFIED: Added $branch_sql_filter
    $sql_done = "SELECT s.id, s.consignment_no, s.consignment_date, p.name as consignor_name, s.origin, s.destination FROM shipments s JOIN parties p ON s.consignor_id = p.id WHERE s.payment_entry_status = 'Done' AND s.consignment_no LIKE ? $branch_sql_filter ORDER BY s.consignment_date DESC LIMIT ?, ?";
    if ($stmt = $mysqli->prepare($sql_done)) {
        // MODIFIED: Bind branch_id if it exists
        if ($branch_param_value !== null) {
            $stmt->bind_param("s" . $branch_param_type . "ii", $search_param_done, $branch_param_value, $offset_done, $records_per_page);
        } else {
            $stmt->bind_param("sii", $search_param_done, $offset_done, $records_per_page);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $completed_payments[] = $row; }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div id="loader" class="fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
    <div class="fas fa-spinner fa-spin fa-3x text-indigo-600"></div>
</div>
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Manage Payments</h1>
                        <div class="flex items-center pr-4">
                             <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                            <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($edit_mode): ?>
                <div class="bg-white p-8 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Consignment Summary</h2>
                    <div id="summary-details" data-weight-kg="<?php echo htmlspecialchars($shipment_data['chargeable_weight'] === 'FTL' ? 0 : ($shipment_data['chargeable_weight_unit'] == 'Ton' ? $shipment_data['chargeable_weight'] * 1000 : ($shipment_data['chargeable_weight_unit'] == 'Quintal' ? $shipment_data['chargeable_weight'] * 100 : $shipment_data['chargeable_weight']))); ?>" class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm text-gray-700 border-b pb-6 mb-6">
                        <div><strong class="block text-gray-500">LR No:</strong> <?php echo htmlspecialchars($shipment_data['consignment_no']); ?></div>
                        <div><strong class="block text-gray-500">Date:</strong> <?php echo date("d-m-Y", strtotime($shipment_data['consignment_date'])); ?></div>
                        <div><strong class="block text-gray-500">Vehicle No:</strong> <?php echo htmlspecialchars($shipment_data['vehicle_number'] ?? 'N/A'); ?></div>
                        <div><strong class="block text-gray-500">Driver:</strong> <?php echo htmlspecialchars($shipment_data['driver_name'] ?? 'N/A'); ?></div>
                        <div class="col-span-2 md:col-span-5"><strong class="block text-gray-500">Route:</strong> <?php echo htmlspecialchars($shipment_data['origin'] . ' to ' . $shipment_data['destination']); ?></div>
                        <div><strong class="block text-gray-500">Consignor:</strong> <?php echo htmlspecialchars($shipment_data['consignor_name']); ?></div>
                        <div><strong class="block text-gray-500">Consignee:</strong> <?php echo htmlspecialchars($shipment_data['consignee_name']); ?></div>
                        <div><strong class="block text-gray-500">Net Wt:</strong> <?php echo htmlspecialchars($shipment_data['net_weight'] . ' ' . $shipment_data['net_weight_unit']); ?></div>
                        <div><strong class="block text-gray-500">Chargeable Wt:</strong> <?php echo htmlspecialchars($shipment_data['chargeable_weight'] . ' ' . $shipment_data['chargeable_weight_unit']); ?></div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-6">Enter / Edit Payment Details</h2>
                    <form method="POST">
                        <input type="hidden" name="shipment_id" value="<?php echo $shipment_data['id']; ?>">
                        
                        <div class="border-b pb-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Party Billing</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><label class="block text-sm font-medium">Billing Method</label><select id="party_billing_method" name="party_billing_method" class="mt-1 block w-full px-3 py-2 border rounded-md" required><option value="Fixed" <?php if(($payment_data['Billing Rate']['billing_method'] ?? '') == 'Fixed') echo 'selected'; ?>>Fixed / Volume</option><option value="Kg" <?php if(($payment_data['Billing Rate']['billing_method'] ?? '') == 'Kg') echo 'selected'; ?>>Per Kg</option><option value="Quintal" <?php if(($payment_data['Billing Rate']['billing_method'] ?? '') == 'Quintal') echo 'selected'; ?>>Per Quintal</option><option value="Ton" <?php if(($payment_data['Billing Rate']['billing_method'] ?? '') == 'Ton') echo 'selected'; ?>>Per Ton</option></select></div>
                                <div><label class="block text-sm font-medium">Rate</label><input type="number" step="0.01" id="party_rate" name="party_rate" value="<?php echo htmlspecialchars($payment_data['Billing Rate']['rate'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                                <div><label class="block text-sm font-medium">Total Billing Amount</label><input type="number" step="0.01" id="party_total_billing" name="party_total_billing" value="<?php echo htmlspecialchars($payment_data['Billing Rate']['amount'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100" readonly></div>
                            </div>
                        </div>

                        <div class="border-b pb-6 mb-6">
                             <h3 class="text-lg font-semibold text-gray-700 mb-4">Vehicle Hire</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><label class="block text-sm font-medium">Billing Method</label><select id="vehicle_billing_method" name="vehicle_billing_method" class="mt-1 block w-full px-3 py-2 border rounded-md" required><option value="Fixed" <?php if(($payment_data['Lorry Hire']['billing_method'] ?? '') == 'Fixed') echo 'selected'; ?>>Fixed / Volume</option><option value="Kg" <?php if(($payment_data['Lorry Hire']['billing_method'] ?? '') == 'Kg') echo 'selected'; ?>>Per Kg</option><option value="Quintal" <?php if(($payment_data['Lorry Hire']['billing_method'] ?? '') == 'Quintal') echo 'selected'; ?>>Per Quintal</option><option value="Ton" <?php if(($payment_data['Lorry Hire']['billing_method'] ?? '') == 'Ton') echo 'selected'; ?>>Per Ton</option></select></div>
                                <div><label class="block text-sm font-medium">Rate</label><input type="number" step="0.01" id="vehicle_rate" name="vehicle_rate" value="<?php echo htmlspecialchars($payment_data['Lorry Hire']['rate'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                                <div><label class="block text-sm font-medium">Total Lorry Hire</label><input type="number" step="0.01" id="vehicle_total_hire" name="vehicle_total_hire" value="<?php echo htmlspecialchars($payment_data['Lorry Hire']['amount'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100" readonly></div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Other Charges & Advances</h3>
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                <div><label class="block text-sm font-medium">Advance (Cash/Bank)</label><input type="number" step="0.01" name="advance_cash" id="advance_cash" value="<?php echo htmlspecialchars($payment_data['Advance Cash']['amount'] ?? ''); ?>" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                                <div><label class="block text-sm font-medium">Advance (Diesel)</label><input type="number" step="0.01" name="advance_diesel" id="advance_diesel" value="<?php echo htmlspecialchars($payment_data['Advance Diesel']['amount'] ?? ''); ?>" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                                <div><label class="block text-sm font-medium">Labour Charge</label><input type="number" step="0.01" name="labour_charge" id="labour_charge" value="<?php echo htmlspecialchars($payment_data['Labour Charge']['amount'] ?? ''); ?>" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                                <div><label class="block text-sm font-medium">Dala Charge</label><input type="number" step="0.01" name="dala_charge" id="dala_charge" value="<?php echo htmlspecialchars($payment_data['Dala Charge']['amount'] ?? ''); ?>" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                                <div><label class="block text-sm font-medium">Lifting Charge</label><input type="number" step="0.01" name="lifting_charge" id="lifting_charge" value="<?php echo htmlspecialchars($payment_data['Lifting Charge']['amount'] ?? ''); ?>" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                                <div><label class="block text-sm font-medium">Balance Amount</label><input type="number" step="0.01" id="balance_amount" name="balance_amount" class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100" readonly></div>
                            </div>
                        </div>

                        <div class="mt-6 text-right">
                            <a href="manage_payments.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">Cancel</a>
                            <button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Details</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="space-y-8">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-yellow-600">Payment Re-verification Required</h2>
                            <form method="GET" class="flex gap-2">
                                <input type="hidden" name="page_reverify" value="1">
                                <input type="hidden" name="page_pending" value="<?php echo $page_pending; ?>">
                                <input type="hidden" name="page_done" value="<?php echo $page_done; ?>">
                                <input type="hidden" name="search_pending" value="<?php echo htmlspecialchars($search_pending); ?>">
                                <input type="hidden" name="search_done" value="<?php echo htmlspecialchars($search_done); ?>">
                                <input type="text" name="search_reverify" placeholder="Search by LR No." value="<?php echo htmlspecialchars($search_reverify); ?>" class="px-3 py-2 border rounded-md text-sm shadow-sm">
                                <button type="submit" class="py-2 px-4 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm">Search</button>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-yellow-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Route</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($reverify_payments as $shipment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($shipment['consignment_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['origin'] . ' to ' . $shipment['destination']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><a href="manage_payments.php?action=edit&id=<?php echo $shipment['id']; ?>" class="text-yellow-600 hover:text-yellow-900">Re-verify Details</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($reverify_payments)): ?><tr><td colspan="4" class="text-center py-4">No payments require re-verification<?php if(!empty($search_reverify)) echo ' for "'.htmlspecialchars($search_reverify).'"'; ?>.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-4 flex justify-end">
                            <?php for ($i = 1; $i <= $total_pages_reverify; $i++): ?><a href="?page_reverify=<?php echo $i; ?>&page_pending=<?php echo $page_pending; ?>&page_done=<?php echo $page_done; ?>&search_reverify=<?php echo htmlspecialchars($search_reverify); ?>&search_pending=<?php echo htmlspecialchars($search_pending); ?>&search_done=<?php echo htmlspecialchars($search_done); ?>" class="px-3 py-1 mx-1 text-sm font-medium <?php echo $i == $page_reverify ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a><?php endfor; ?>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Pending Payment Entry</h2>
                            <form method="GET" class="flex gap-2">
                                <input type="hidden" name="page_reverify" value="<?php echo $page_reverify; ?>">
                                <input type="hidden" name="page_pending" value="1">
                                <input type="hidden" name="page_done" value="<?php echo $page_done; ?>">
                                <input type="hidden" name="search_reverify" value="<?php echo htmlspecialchars($search_reverify); ?>">
                                <input type="hidden" name="search_done" value="<?php echo htmlspecialchars($search_done); ?>">
                                <input type="text" name="search_pending" placeholder="Search by LR No." value="<?php echo htmlspecialchars($search_pending); ?>" class="px-3 py-2 border rounded-md text-sm shadow-sm">
                                <button type="submit" class="py-2 px-4 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm">Search</button>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Route</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_payments as $shipment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($shipment['consignment_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['origin'] . ' to ' . $shipment['destination']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><a href="manage_payments.php?action=edit&id=<?php echo $shipment['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Add Details</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($pending_payments)): ?><tr><td colspan="4" class="text-center py-4">No payments are pending entry<?php if(!empty($search_pending)) echo ' for "'.htmlspecialchars($search_pending).'"'; ?>.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-4 flex justify-end">
                            <?php for ($i = 1; $i <= $total_pages_pending; $i++): ?><a href="?page_reverify=<?php echo $page_reverify; ?>&page_pending=<?php echo $i; ?>&page_done=<?php echo $page_done; ?>&search_reverify=<?php echo htmlspecialchars($search_reverify); ?>&search_pending=<?php echo htmlspecialchars($search_pending); ?>&search_done=<?php echo htmlspecialchars($search_done); ?>" class="px-3 py-1 mx-1 text-sm font-medium <?php echo $i == $page_pending ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a><?php endfor; ?>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Completed Payment Entries</h2>
                            <form method="GET" class="flex gap-2">
                                <input type="hidden" name="page_reverify" value="<?php echo $page_reverify; ?>">
                                <input type="hidden" name="page_pending" value="<?php echo $page_pending; ?>">
                                <input type="hidden" name="page_done" value="1">
                                <input type="hidden" name="search_reverify" value="<?php echo htmlspecialchars($search_reverify); ?>">
                                <input type="hidden" name="search_pending" value="<?php echo htmlspecialchars($search_pending); ?>">
                                <input type="text" name="search_done" placeholder="Search by LR No." value="<?php echo htmlspecialchars($search_done); ?>" class="px-3 py-2 border rounded-md text-sm shadow-sm">
                                <button type="submit" class="py-2 px-4 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm">Search</button>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Route</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($completed_payments as $shipment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($shipment['consignment_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['origin'] . ' to ' . $shipment['destination']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><a href="manage_payments.php?action=edit&id=<?php echo $shipment['id']; ?>" class="text-gray-600 hover:text-indigo-900">View/Edit Details</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($completed_payments)): ?><tr><td colspan="4" class="text-center py-4">No payment details have been entered yet<?php if(!empty($search_done)) echo ' for "'.htmlspecialchars($search_done).'"'; ?>.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <?php for ($i = 1; $i <= $total_pages_done; $i++): ?><a href="?page_reverify=<?php echo $page_reverify; ?>&page_pending=<?php echo $page_pending; ?>&page_done=<?php echo $i; ?>&search_reverify=<?php echo htmlspecialchars($search_reverify); ?>&search_pending=<?php echo htmlspecialchars($search_pending); ?>&search_done=<?php echo htmlspecialchars($search_done); ?>" class="px-3 py-1 mx-1 text-sm font-medium <?php echo $i == $page_done ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a><?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Mobile sidebar toggle ---
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


        // --- Page Specific Logic ---
        const summaryDetails = document.getElementById('summary-details');
        if (!summaryDetails) return;

        const chargeableWeightKg = parseFloat(summaryDetails.dataset.weightKg);
        const partyMethod = document.getElementById('party_billing_method');
        const partyRate = document.getElementById('party_rate');
        const partyTotal = document.getElementById('party_total_billing');
        const vehicleMethod = document.getElementById('vehicle_billing_method');
        const vehicleRate = document.getElementById('vehicle_rate');
        const vehicleTotal = document.getElementById('vehicle_total_hire');
        const balanceInputs = document.querySelectorAll('.balance-calc');
        const balanceAmountField = document.getElementById('balance_amount');

        function calculateTotal(method, rate, weight) {
            if (isNaN(rate) || rate <= 0) return 0;
            switch (method) {
                case 'Kg': return rate * weight;
                case 'Quintal': return rate * (weight / 100);
                case 'Ton': return rate * (weight / 1000);
                case 'Fixed': default: return rate;
            }
        }

        function calculateBalance() {
            const lorryHire = parseFloat(vehicleTotal.value) || 0;
            let totalDeductions = 0;
            balanceInputs.forEach(input => {
                totalDeductions += parseFloat(input.value) || 0;
            });
            const balance = lorryHire - totalDeductions;
            balanceAmountField.value = balance.toFixed(2);
        }

        function updatePartyTotal() {
            const rate = parseFloat(partyRate.value) || 0;
            const total = calculateTotal(partyMethod.value, rate, chargeableWeightKg);
            partyTotal.value = total.toFixed(2);
        }

        function updateVehicleTotal() {
            const rate = parseFloat(vehicleRate.value) || 0;
            const total = calculateTotal(vehicleMethod.value, rate, chargeableWeightKg);
            vehicleTotal.value = total.toFixed(2);
            calculateBalance();
        }

        partyMethod.addEventListener('change', updatePartyTotal);
        partyRate.addEventListener('keyup', updatePartyTotal);
        vehicleMethod.addEventListener('change', updateVehicleTotal);
        vehicleRate.addEventListener('keyup', updateVehicleTotal);
        balanceInputs.forEach(input => {
            input.addEventListener('keyup', calculateBalance);
        });
        
        updatePartyTotal();
        updateVehicleTotal();
    });
    </script>
    <script>
    // Hide the loader once the entire page is fully loaded
    window.onload = function() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    };
</script>
</body>
</html>