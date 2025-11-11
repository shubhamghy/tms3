<?php
// --- For Debugging: Temporarily add these lines to see detailed errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// --- Page State Management ---
$form_message = "";
$edit_mode = false;
$booking_data = [];
$booking_invoices = [];

// Handle GET request for editing
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $shipment_id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM shipments WHERE id = ?");
    $stmt->bind_param("i", $shipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $booking_data = $result->fetch_assoc();
    }
    $stmt->close();

    $stmt_invoices = $mysqli->prepare("SELECT * FROM shipment_invoices WHERE shipment_id = ?");
    $stmt_invoices->bind_param("i", $shipment_id);
    $stmt_invoices->execute();
    $result_invoices = $stmt_invoices->get_result();
    while ($row = $result_invoices->fetch_assoc()) {
        $booking_invoices[] = $row;
    }
    $stmt_invoices->close();
}

// --- Data Fetching for Dropdowns with Branch Filtering ---
$parties = []; $brokers = []; $drivers = []; $vehicles = []; $cities = []; $descriptions = []; $states = [];
$countries = []; $modal_cities = []; // --- NEW for dependent dropdowns

$user_role = $_SESSION['role'] ?? null;
$branch_id = $_SESSION['branch_id'] ?? 0;

// Base WHERE clause for branch-specific data that also includes global items (branch_id IS NULL)
$branch_filter_clause = "";
if ($user_role !== 'admin' && $branch_id > 0) {
    $branch_filter_clause = " AND (branch_id IS NULL OR branch_id = " . intval($branch_id) . ")";
}

// Special WHERE clause for brokers (includes global brokers)
$broker_branch_clause = "";
if ($user_role !== 'admin' && $branch_id > 0) {
    $broker_branch_clause = " AND (visibility_type = 'global' OR branch_id = " . intval($branch_id) . ")";
}

// Fetch Parties - *** UPDATED to include city ***
$sql_parties = "SELECT id, name, address, city, party_type FROM parties WHERE is_active = 1{$branch_filter_clause} ORDER BY name ASC";
if ($result = $mysqli->query($sql_parties)) { $parties = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch Brokers
$sql_brokers = "SELECT id, name FROM brokers WHERE is_active = 1{$broker_branch_clause} ORDER BY name ASC";
if ($result = $mysqli->query($sql_brokers)) { $brokers = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch Drivers
$sql_drivers = "SELECT id, name FROM drivers WHERE is_active = 1{$branch_filter_clause} ORDER BY name ASC";
if ($result = $mysqli->query($sql_drivers)) { $drivers = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch Vehicles
$sql_vehicles = "SELECT id, vehicle_number FROM vehicles WHERE is_active = 1{$branch_filter_clause} ORDER BY vehicle_number ASC";
if ($result = $mysqli->query($sql_vehicles)) { $vehicles = $result->fetch_all(MYSQLI_ASSOC); }

// These are global
$sql_descriptions = "SELECT id, description FROM consignment_descriptions WHERE is_active = 1 ORDER BY description ASC"; if ($result = $mysqli->query($sql_descriptions)) { $descriptions = $result->fetch_all(MYSQLI_ASSOC); }

// --- UPDATED queries for dependent location dropdowns ---
// Fetch Countries
$sql_countries = "SELECT id, name FROM countries ORDER BY name ASC"; 
if ($result = $mysqli->query($sql_countries)) { $countries = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch States with country_id (assuming table structure)
$sql_states = "SELECT id, name, country_id FROM states ORDER BY name ASC"; 
if ($result = $mysqli->query($sql_states)) { $states = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch Cities with state_id (for modal)
$sql_modal_cities = "SELECT id, name, state_id FROM cities ORDER BY name ASC"; 
if ($result = $mysqli->query($sql_modal_cities)) { $modal_cities = $result->fetch_all(MYSQLI_ASSOC); }

// Fetch Cities (for origin/destination dropdowns - needs only names)
$sql_cities = "SELECT id, name FROM cities ORDER BY name ASC"; 
if ($result = $mysqli->query($sql_cities)) { $cities = $result->fetch_all(MYSQLI_ASSOC); }


// --- Form Processing ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $shipment_id = intval($_POST['shipment_id']);
    $consignment_no = trim($_POST['consignment_no']);
    $is_duplicate = false;

    // Check for duplicate consignment number
    $check_sql = "SELECT id FROM shipments WHERE consignment_no = ?";
    if ($check_stmt = $mysqli->prepare($check_sql)) {
        $check_stmt->bind_param("s", $consignment_no);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            if ($shipment_id > 0) { // In edit mode
                $check_stmt->bind_result($found_id);
                $check_stmt->fetch();
                if ($found_id != $shipment_id) { $is_duplicate = true; }
            } else { // In create mode
                $is_duplicate = true;
            }
        }
        $check_stmt->close();
    }

    if ($is_duplicate) {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: This Consignment Number is already in use.</div>';
    } else {
        $mysqli->begin_transaction();
        try {
            // Prepare common variables
            $is_shipping_different = isset($_POST['is_shipping_different']) ? 1 : 0;
            $shipping_name = $is_shipping_different ? trim($_POST['shipping_name']) : null;
            $shipping_address = $is_shipping_different ? trim($_POST['shipping_address']) : null;
            $net_weight = isset($_POST['net_weight_ftl']) ? 'FTL' : trim($_POST['net_weight']);
            $chargeable_weight = isset($_POST['chargeable_weight_ftl']) ? 'FTL' : trim($_POST['chargeable_weight']);
            $net_weight_unit = isset($_POST['net_weight_ftl']) ? '' : $_POST['net_weight_unit'];
            $chargeable_weight_unit = isset($_POST['chargeable_weight_ftl']) ? '' : $_POST['chargeable_weight_unit'];
            
            // Handle null values for optional foreign keys
            $description_id = !empty($_POST['description_id']) ? intval($_POST['description_id']) : null;
            $broker_id = !empty($_POST['broker_id']) ? intval($_POST['broker_id']) : null;
            $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
            $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
            
            $created_by_id = $_SESSION['id'];
            $branch_id = $_SESSION['branch_id'] ?? 1; // Default to 1 if not set

            if ($shipment_id > 0) { // UPDATE
                $sql = "UPDATE shipments SET consignment_no=?, consignment_date=?, consignor_id=?, consignee_id=?, is_shipping_different=?, shipping_name=?, shipping_address=?, origin=?, destination=?, description_id=?, quantity=?, package_type=?, net_weight=?, net_weight_unit=?, chargeable_weight=?, chargeable_weight_unit=?, billing_type=?, broker_id=?, driver_id=?, vehicle_id=?, payment_entry_status='Reverify' WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssiiissssisssssssiisi", $consignment_no, $_POST['consignment_date'], $_POST['consignor_id'], $_POST['consignee_id'], $is_shipping_different, $shipping_name, $shipping_address, $_POST['origin'], $_POST['destination'], $description_id, $_POST['quantity'], $_POST['package_type'], $net_weight, $net_weight_unit, $chargeable_weight, $chargeable_weight_unit, $_POST['billing_type'], $broker_id, $driver_id, $vehicle_id, $shipment_id);
            } else { // INSERT
                $status = 'Booked';
                $payment_entry_status = 'Pending';
                $sql = "INSERT INTO shipments (consignment_no, consignment_date, consignor_id, consignee_id, is_shipping_different, shipping_name, shipping_address, origin, destination, description_id, quantity, package_type, net_weight, net_weight_unit, chargeable_weight, chargeable_weight_unit, billing_type, broker_id, driver_id, vehicle_id, created_by_id, branch_id, status, payment_entry_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);
                
                // ✅ THIS IS THE CORRECTED BIND_PARAM CALL with exactly 24 types for 24 variables
                $stmt->bind_param(
                    "ssiiissssisssssssiiiiiss",
                    $consignment_no,
                    $_POST['consignment_date'],
                    $_POST['consignor_id'],
                    $_POST['consignee_id'],
                    $is_shipping_different,
                    $shipping_name,
                    $shipping_address,
                    $_POST['origin'],
                    $_POST['destination'],
                    $description_id,
                    $_POST['quantity'],
                    $_POST['package_type'],
                    $net_weight,
                    $net_weight_unit,
                    $chargeable_weight,
                    $chargeable_weight_unit,
                    $_POST['billing_type'],
                    $broker_id,
                    $driver_id,
                    $vehicle_id,
                    $created_by_id,
                    $branch_id,
                    $status,
                    $payment_entry_status 
                );
            }
            
            if (!$stmt->execute()) { throw new Exception("Error saving shipment: " . $stmt->error); }
            if ($shipment_id == 0) { $shipment_id = $stmt->insert_id; }
            $stmt->close();
            
            $mysqli->query("DELETE FROM shipment_invoices WHERE shipment_id = $shipment_id");
            if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $invoice_sql = "INSERT INTO shipment_invoices (shipment_id, invoice_no, invoice_date, invoice_amount, eway_bill_no, eway_bill_expiry) VALUES (?, ?, ?, ?, ?, ?)";
                $invoice_stmt = $mysqli->prepare($invoice_sql);
                foreach ($_POST['invoices'] as $invoice) {
                    $invoice_no = trim($invoice['number']);
                    $invoice_date = !empty($invoice['date']) ? $invoice['date'] : null;
                    $invoice_amount = !empty($invoice['amount']) ? (float)$invoice['amount'] : null;
                    $eway_no = trim($invoice['eway_no']);
                    $eway_expiry = !empty($invoice['eway_expiry']) ? $invoice['eway_expiry'] : null;
                    
                    if (!empty($invoice_no)) {
                        $invoice_stmt->bind_param("issdss", $shipment_id, $invoice_no, $invoice_date, $invoice_amount, $eway_no, $eway_expiry);
                        if (!$invoice_stmt->execute()) {
                            throw new Exception("Error saving invoice: " . $invoice_stmt->error);
                        }
                    }
                }
                $invoice_stmt->close();
            }

            $mysqli->commit();
            header("location: print_lr_landscape.php?id=" . $shipment_id);
            exit;

        } catch (Exception $e) {
            $mysqli->rollback();
            $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'New'; ?> Shipment Booking - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single { height: 42px; border: 1px solid #d1d5db; border-radius: 0.375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 0.75rem; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
        .select2-container--default.select2-container--disabled .select2-selection--single { background-color: #e9ecef; cursor: not-allowed; }
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-100">

<div id="loader" class="fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
    <div class="fas fa-spinner fa-spin fa-3x text-indigo-600"></div>
</div>

<div class="flex h-screen bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-col flex-1 overflow-y-auto">
        
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $edit_mode ? 'Edit Shipment Booking' : 'New Shipment Booking'; ?></h1>
                    <div class="flex items-center pr-4"><span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span><a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a></div>
                </div>
            </div>
        </header>
        
        <main class="p-4 md:p-8" x-data="bookingApp()">
            
            <?php if(!empty($form_message)) echo $form_message; ?>
            
            <form id="booking-form" method="post" class="space-y-4" x-data="{ activeSection: 1 }">
                <input type="hidden" name="shipment_id" value="<?php echo $booking_data['id'] ?? ''; ?>">
                
                <div class="border rounded-lg bg-white shadow-sm">
                    <div @click="activeSection = (activeSection === 1 ? 0 : 1)" class="p-4 bg-gray-50 cursor-pointer flex justify-between items-center rounded-t-lg">
                        <h3 class="font-semibold text-lg text-gray-800">Step 1: Booking & Party Details</h3>
                        <i class="fas" :class="activeSection === 1 ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div x-show="activeSection === 1" x-transition class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label for="consignment_no" class="block text-sm font-medium">Consignment No.</label><input type="text" name="consignment_no" id="consignment_no" value="<?php echo htmlspecialchars($booking_data['consignment_no'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><p id="cn-status" class="mt-2 text-sm"></p></div>
                            <div><label for="consignment_date" class="block text-sm font-medium">Consignment Date</label><input type="date" name="consignment_date" id="consignment_date" value="<?php echo htmlspecialchars($booking_data['consignment_date'] ?? date('Y-m-d')); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></div>
                        </div>
                        <hr>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="consignor_id" class="block text-sm font-medium">Consignor Name</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="consignor_id" id="consignor_id" class="searchable-select block w-full" required><option value="">Select Consignor</option><?php foreach ($parties as $party): if(in_array($party['party_type'], ['Consignor', 'Both'])): ?><option value="<?php echo $party['id']; ?>"><?php echo htmlspecialchars($party['name']); ?></option><?php endif; endforeach; ?></select></div>
                                    <button type="button" @click="openModal('party', 'Consignor', '#consignor_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                                <div id="consignor_address" class="mt-2 text-sm text-gray-600 p-2 bg-gray-50 rounded-md min-h-[40px]"></div>
                            </div>
                            <div>
                                <label for="consignee_id" class="block text-sm font-medium">Consignee (Billing)</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="consignee_id" id="consignee_id" class="searchable-select block w-full" required><option value="">Select Consignee</option><?php foreach ($parties as $party): if(in_array($party['party_type'], ['Consignee', 'Both'])): ?><option value="<?php echo $party['id']; ?>"><?php echo htmlspecialchars($party['name']); ?></option><?php endif; endforeach; ?></select></div>
                                    <button type="button" @click="openModal('party', 'Consignee', '#consignee_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                                <div id="consignee_address" class="mt-2 text-sm text-gray-600 p-2 bg-gray-50 rounded-md min-h-[40px]"></div>
                            </div>
                            <div class="md:col-span-2"><label for="is_shipping_different" class="flex items-center"><input type="checkbox" id="is_shipping_different" name="is_shipping_different" class="h-4 w-4 text-indigo-600 border-gray-300 rounded" <?php if(!empty($booking_data['is_shipping_different'])) echo 'checked'; ?>><span class="ml-2 text-sm text-gray-900">Shipping address is different</span></label></div>
                            <div id="shipping-address-fields" class="<?php echo empty($booking_data['is_shipping_different']) ? 'hidden' : ''; ?> md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label for="shipping_name" class="block text-sm font-medium">Shipping Name</label><input type="text" name="shipping_name" id="shipping_name" value="<?php echo htmlspecialchars($booking_data['shipping_name'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></div>
                                <div class="md:col-span-2"><label for="shipping_address" class="block text-sm font-medium">Shipping Address</label><textarea name="shipping_address" id="shipping_address" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?php echo htmlspecialchars($booking_data['shipping_address'] ?? ''); ?></textarea></div>
                            </div>
                            <div>
                                <label for="origin" class="block text-sm font-medium">Origin</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow">
                                        <select name="origin" id="origin" class="searchable-select block w-full" required>
                                            <option value="">Select Origin</option>
                                            <?php foreach ($cities as $city): ?>
                                                <option value="<?php echo htmlspecialchars($city['name']); ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" @click="openModal('city', 'City', '#origin')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div>
                                <label for="destination" class="block text-sm font-medium">Destination</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow">
                                        <select name="destination" id="destination" class="searchable-select block w-full" required>
                                            <option value="">Select Destination</option>
                                            <?php foreach ($cities as $city): ?>
                                                 <option value="<?php echo htmlspecialchars($city['name']); ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" @click="openModal('city', 'City', '#destination')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-lg bg-white shadow-sm">
                    <div @click="activeSection = (activeSection === 2 ? 0 : 2)" class="p-4 bg-gray-50 cursor-pointer flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-gray-800">Step 2: Consignment & Invoice Details</h3>
                        <i class="fas" :class="activeSection === 2 ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div x-show="activeSection === 2" x-transition class="p-6 space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-4"><h3 class="text-md font-medium text-gray-900">Invoice & E-Way Bill Details</h3><button type="button" id="add-invoice-btn" class="inline-flex items-center px-3 py-1 border text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add Row</button></div>
                            <div id="invoice-list" class="space-y-4"></div>
                        </div>
                        <hr>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="description_id" class="block text-sm font-medium text-gray-700">Description of Consignment</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="description_id" id="description_id" class="searchable-select block w-full"><option value="">Select Description</option><?php foreach ($descriptions as $desc): ?><option value="<?php echo $desc['id']; ?>"><?php echo htmlspecialchars($desc['description']); ?></option><?php endforeach; ?></select></div>
                                    <button type="button" @click="openModal('description', 'Description', '#description_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Quantity</label><input type="text" name="quantity" id="quantity" value="<?php echo htmlspecialchars($booking_data['quantity'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></div>
                            <div><label for="package_type" class="block text-sm font-medium text-gray-700">Package Type</label><select name="package_type" id="package_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm"><option value="">Select Type</option><option value="Cartons">Cartons</option><option value="Cartons/Pieces">Cartons/Pieces</option><option value="Packets">Packets</option><option value="Pieces">Pieces</option><option value="Loose">Loose</option></select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Net Weight</label><div class="mt-1 flex rounded-md shadow-sm"><input type="text" name="net_weight" id="net_weight" value="<?php echo htmlspecialchars($booking_data['net_weight'] ?? ''); ?>" class="flex-1 block w-full min-w-0 rounded-none rounded-l-md px-3 py-2 border border-gray-300"><select name="net_weight_unit" id="net_weight_unit" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><option>Kg</option><option>Quintal</option><option>Ton</option></select></div><div class="mt-2 flex items-center"><input id="net_weight_ftl" name="net_weight_ftl" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded"><label for="net_weight_ftl" class="ml-2 block text-sm text-gray-900">FTL</label></div></div>
                            <div><label class="block text-sm font-medium text-gray-700">Chargeable Weight</label><div class="mt-1 flex rounded-md shadow-sm"><input type="text" name="chargeable_weight" id="chargeable_weight" value="<?php echo htmlspecialchars($booking_data['chargeable_weight'] ?? ''); ?>" class="flex-1 block w-full min-w-0 rounded-none rounded-l-md px-3 py-2 border border-gray-300"><select name="chargeable_weight_unit" id="chargeable_weight_unit" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><option>Kg</option><option>Quintal</option><option>Ton</option></select></div><div class="mt-2 flex items-center"><input id="chargeable_weight_ftl" name="chargeable_weight_ftl" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded"><label for="chargeable_weight_ftl" class="ml-2 block text-sm text-gray-900">FTL</label></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg bg-white shadow-sm">
                    <div @click="activeSection = (activeSection === 3 ? 0 : 3)" class="p-4 bg-gray-50 cursor-pointer flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-gray-800">Step 3: Vehicle & Billing</h3>
                        <i class="fas" :class="activeSection === 3 ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div x-show="activeSection === 3" x-transition class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="broker_id" class="block text-sm font-medium text-gray-700">Broker/Owner</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="broker_id" id="broker_id" class="searchable-select block w-full"><option value="">Select Broker/Owner</option><?php foreach ($brokers as $broker): ?><option value="<?php echo $broker['id']; ?>"><?php echo htmlspecialchars($broker['name']); ?></option><?php endforeach; ?></select></div>
                                    <button type="button" @click="openModal('broker', 'Broker', '#broker_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div>
                                <label for="vehicle_id" class="block text-sm font-medium text-gray-700">Vehicle</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="vehicle_id" id="vehicle_id" class="searchable-select block w-full"><option value="">Select Vehicle</option><?php foreach ($vehicles as $vehicle): ?><option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></option><?php endforeach; ?></select></div>
                                    <button type="button" @click="openModal('vehicle', 'Vehicle', '#vehicle_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div>
                                <label for="driver_id" class="block text-sm font-medium text-gray-700">Driver</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex-grow"><select name="driver_id" id="driver_id" class="searchable-select block w-full"><option value="">Select Driver</option><?php foreach ($drivers as $driver): ?><option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['name']); ?></option><?php endforeach; ?></select></div>
                                    <button type="button" @click="openModal('driver', 'Driver', '#driver_id')" class="flex-shrink-0 px-3 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="md:col-span-3"><label for="billing_type" class="block text-sm font-medium text-gray-700">Billing Type</label><select name="billing_type" id="billing_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm" required><option>To Pay</option><option>Paid</option><option>To be Billed</option></select></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-right">
                    <button type="submit" id="submit-btn" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"><?php echo $edit_mode ? 'Update Consignment' : 'Save & Print LR'; ?></button>
                </div>
            </form>

            <div x-show="isModalOpen" class="fixed inset-0 z-40 overflow-y-auto" x-cloak>
                <div class="flex items-center justify-center min-h-screen">
                    <div @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div>
                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <form id="quick-add-form" @submit.prevent="submitQuickAdd">
                            <div class="px-6 py-4">
                                <h3 class="text-lg font-medium" x-text="`Add New ${modalTitle}`"></h3>
                                <div id="modal-fields" class="mt-4 space-y-4"></div>
                                <p id="modal-error" class="text-red-600 text-sm mt-2"></p>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                                <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </main>
    </div>
</div>
    
<div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-20 hidden md:hidden"></div>

<script>
    // Pass PHP arrays to JavaScript
    const countriesData = <?php echo json_encode($countries); ?>;
    const statesData = <?php echo json_encode($states); ?>;
    const modalCitiesData = <?php echo json_encode($modal_cities); ?>;
    
    // *** UPDATED to include city ***
    let partiesData = <?php echo json_encode($parties); ?>;

    function bookingApp() {
        return {
            isModalOpen: false, modalTitle: '', modalType: '', targetSelect: '',
            openModal(type, title, target) {
                this.modalTitle = title; this.modalType = type; this.targetSelect = target; this.isModalOpen = true;
                document.getElementById('modal-error').textContent = '';
                const fieldsContainer = document.getElementById('modal-fields');
                let fieldsHtml = '';
                
                // *** UPDATED to add 'required' and State/City dropdowns for Party ***
                if (type === 'party') {
                    let countryOptions = countriesData.map(country => `<option value="${country.id}">${country.name}</option>`).join('');
                    fieldsHtml = `
                        <input type="hidden" name="party_type" value="${title}">
                        <input type="hidden" name="city" id="modal_city_name"> 
                        <div><label class="block text-sm">Name</label><input type="text" name="name" required class="mt-1 w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm">Address</label><textarea name="address" required class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                        
                        <div><label class="block text-sm">Country</label><select name="country_id" id="modal_country" required class="mt-1 w-full p-2 border rounded-md"><option value="">Select Country</option>${countryOptions}</select></div>
                        <div><label class="block text-sm">State</label><select name="state_id" id="modal_state" required class="mt-1 w-full p-2 border rounded-md" disabled><option value="">Select Country first</option></select></div>
                        <div><label class="block text-sm">City</label><select id="modal_city" required class="mt-1 w-full p-2 border rounded-md" disabled><option value="">Select State first</option></select></div>

                        <div><label class="block text-sm">GST No.</label><input type="text" name="gst_no" class="mt-1 w-full p-2 border rounded-md"></div>`;
                
                } else if (type === 'broker') {
                     fieldsHtml = `
                        <div><label class="block text-sm">Broker Name</label><input type="text" name="name" required class="mt-1 w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm">Address</label><textarea name="address" class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                        <div><label class="block text-sm">Contact No.</label><input type="text" name="contact_number" class="mt-1 w-full p-2 border rounded-md"></div>`;
                
                } else if (type === 'vehicle') {
                     fieldsHtml = `<div><label class="block text-sm">Vehicle Number</label><input type="text" name="vehicle_number" required class="mt-1 w-full p-2 border rounded-md" style="text-transform:uppercase"></div>`;
                
                } else if (type === 'driver') {
                     fieldsHtml = `
                        <div><label class="block text-sm">Driver Name</label><input type="text" name="name" required class="mt-1 w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm">License Number</label><input type="text" name="license_number" required class="mt-1 w-full p-2 border rounded-md"></div>`;
                
                } else if (type === 'description') {
                     fieldsHtml = `<div><label class="block text-sm">Consignment Description</label><input type="text" name="description" required class="mt-1 w-full p-2 border rounded-md"></div>`;
                
                } else if (type === 'city') {
                    // This 'city' type is for the Origin/Destination quick-add, not the party modal.
                    let stateOptions = statesData.map(state => `<option value="${state.id}">${state.name}</option>`).join('');
                    fieldsHtml = `
                        <div><label class="block text-sm">City Name</label><input type="text" name="name" required class="mt-1 w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm">State</label><select name="state_id" required class="mt-1 w-full p-2 border rounded-md"><option value="">Select State</option>${stateOptions}</select></div>`;
                }
                fieldsContainer.innerHTML = fieldsHtml;
            },
            
            async submitQuickAdd() {
                const form = document.getElementById('quick-add-form');
                const formData = new FormData(form);
                const errorDiv = document.getElementById('modal-error');
                errorDiv.textContent = '';
                
                // *** NEW: Set the hidden city name field before submitting ***
                if (this.modalType === 'party') {
                    const selectedCityName = $('#modal_city').find('option:selected').text();
                    if (selectedCityName && selectedCityName !== "Select State first" && selectedCityName !== "No cities found") {
                        formData.set('city', selectedCityName);
                    }
                }

                try {
                    const response = await fetch(`quick_add.php?type=${this.modalType}`, { method: 'POST', body: formData });
                    if (!response.ok) throw new Error('Server returned an error.');
                    const data = await response.json();
                    if (data.success) {
                        if (this.modalType === 'city') {
                            // --- ✅ THIS IS THE CORRECTED LOGIC FOR THE GLITCH ---
                            // 1. Create a new, UNSELECTED option. The value is the city's NAME.
                            const newCityOption = new Option(data.name, data.name);
                            
                            // 2. Append a clone of this option to BOTH dropdowns.
                            //    This makes the new city available everywhere without selecting it.
                            $('#origin').append(newCityOption.cloneNode(true));
                            $('#destination').append(newCityOption.cloneNode(true));
                            
                            // 3. Programmatically select the new city in the TARGET dropdown ONLY.
                            $(this.targetSelect).val(data.name).trigger('change');

                        } else {
                            // Logic for other types (Party, Broker, etc.) remains the same.
                            const newOption = new Option(data.name, data.id, true, true);
                            $(this.targetSelect).append(newOption).trigger('change');
                        }
                        
                        // *** UPDATED to include city in the partiesData array ***
                        if (this.modalType === 'party') {
                            partiesData.push({id: data.id, name: data.name, address: data.address, city: data.city, party_type: data.party_type});
                        }
                        this.isModalOpen = false;
                    } else {
                        errorDiv.textContent = data.message || 'An unknown error occurred.';
                    }
                } catch (error) {
                    console.error('Quick Add Error:', error);
                    errorDiv.textContent = 'A server error occurred.';
                }
            }
        };
    }

    jQuery(function($) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarClose = document.getElementById('close-sidebar-btn'); 
        function toggleSidebar() { if (sidebar && sidebarOverlay) { sidebar.classList.toggle('-translate-x-full'); sidebarOverlay.classList.toggle('hidden'); } }
        if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
        if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
        
        $('.searchable-select').select2({ width: '100%' });
        
        const bookingInvoices = <?php echo json_encode($booking_invoices); ?>;

        // *** UPDATED to auto-select origin/destination ***
        function updateAddress(selectedId, addressDiv, targetLocationSelect) {
            const party = partiesData.find(p => p.id == selectedId);
            if (party) {
                // 1. Update the address box
                addressDiv.html(party.address ? party.address.replace(/\n/g, '<br>') : '');
                
                // 2. Check and set the location (Origin/Destination)
                if (party.city && $(targetLocationSelect).length) {
                    // Check if the city is already an option in the dropdown
                    if ($(targetLocationSelect).find(`option[value='${party.city}']`).length > 0) {
                        // If it exists, select it
                        $(targetLocationSelect).val(party.city).trigger('change');
                    } else {
                        // If it doesn't exist, create it, append it, and then select it
                        const newCityOption = new Option(party.city, party.city, true, true);
                        $(targetLocationSelect).append(newCityOption).trigger('change');
                    }
                }
            } else {
                // Clear address if no party is selected
                addressDiv.html('');
            }
        }
        
        async function setAssignedDriver() {
            const vehicleId = $('#vehicle_id').val();
            const driverSelect = $('#driver_id');
            if (!vehicleId) { 
                driverSelect.val("").trigger('change').prop('disabled', false).select2({ disabled: false });
                return; 
            }
            try {
                const response = await fetch(`get_vehicle_details.php?vehicle_id=${vehicleId}`);
                const data = await response.json();
                if (data.driver_id) {
                    driverSelect.val(data.driver_id).trigger('change').prop('disabled', true).select2({ disabled: true });
                } else {
                    driverSelect.val("").trigger('change').prop('disabled', false).select2({ disabled: false });
                }
            } catch (error) { 
                console.error('Error fetching vehicle details:', error); 
                driverSelect.prop('disabled', false).select2({ disabled: false });
            }
        }
        
        $('#booking-form').on('submit', function() { $('#driver_id').prop('disabled', false); });
        
        let invoiceCounter = 0;
        function addInvoiceRow(invoice = {}) {
            invoiceCounter++;
            // *** FIXED: class_id changed to class ***
            const invoiceRowHtml = `<div class="invoice-row grid grid-cols-1 md:grid-cols-12 gap-4 items-center border-t pt-4 first:border-t-0"><div class="md:col-span-3"><input type="text" name="invoices[${invoiceCounter}][number]" value="${invoice.invoice_no || ''}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Invoice Number"></div><div class="md:col-span-2"><input type="date" name="invoices[${invoiceCounter}][date]" value="${invoice.invoice_date || ''}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></div><div class="md:col-span-2"><input type="number" step="0.01" name="invoices[${invoiceCounter}][amount]" value="${invoice.invoice_amount || ''}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Amount"></div><div class="md:col-span-2"><input type="text" name="invoices[${invoiceCounter}][eway_no]" value="${invoice.eway_bill_no || ''}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="E-Way Bill No."></div><div class="md:col-span-2"><input type="date" name="invoices[${invoiceCounter}][eway_expiry]" value="${invoice.eway_bill_expiry || ''}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></div><div class="md:col-span-1 text-center"><button type="button" class="remove-invoice-btn text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button></div></div>`;
            $('#invoice-list').append(invoiceRowHtml);
        }
        
        $('#add-invoice-btn').on('click', () => addInvoiceRow());
        $('#invoice-list').on('click', '.remove-invoice-btn', function() { if ($('.invoice-row').length > 1) { $(this).closest('.invoice-row').remove(); } });
        
        function handleFtlCheckbox(checkbox, weightInput, unitSelect) {
            if (checkbox.is(':checked')) {
                weightInput.val('FTL').prop('disabled', true);
                unitSelect.prop('disabled', true);
            } else {
                if (weightInput.val() === 'FTL') { weightInput.val(''); }
                weightInput.prop('disabled', false);
                unitSelect.prop('disabled', false);
            }
        }
        $('#net_weight_ftl').on('change', function() { handleFtlCheckbox($(this), $('#net_weight'), $('#net_weight_unit')); });
        $('#chargeable_weight_ftl').on('change', function() { handleFtlCheckbox($(this), $('#chargeable_weight'), $('#chargeable_weight_unit')); });
        
        // *** UPDATED: Submit button logic fixed ***
        $('#consignment_no').on('blur', async function() {
            const cnInput = $(this);
            const cnStatus = $('#cn-status');
            const submitBtn = $('#submit-btn');
            const consignmentNo = cnInput.val().trim();
            const shipmentId = $('input[name="shipment_id"]').val();
            
            // If the field is empty, just clear the status and ensure the button is enabled.
            // The 'required' attribute on the HTML input will handle validation.
            if (consignmentNo === '') { 
                cnStatus.text(''); // Clear status
                submitBtn.prop('disabled', false); // Ensure button is enabled
                return; 
            }

            try {
                let url = `check_consignment.php?consignment_no=${encodeURIComponent(consignmentNo)}`;
                if (shipmentId) { url += `&id=${shipmentId}`; }
                const response = await fetch(url);
                const data = await response.json();
                if (data.exists) {
                    cnStatus.text('This CN is already in use.').removeClass('text-green-600').addClass('text-red-600');
                    submitBtn.prop('disabled', true);
                } else {
                    cnStatus.text('CN is available.').removeClass('text-red-600').addClass('text-green-600');
                    submitBtn.prop('disabled', false);
                }
            } catch (error) {
                console.error('Error checking CN:', error);
                cnStatus.text('Could not verify CN. Please try again.').removeClass('text-green-600').addClass('text-red-600');
                submitBtn.prop('disabled', true);
            }
        });

        // *** UPDATED to pass the target dropdown ID ***
        $('#consignor_id').on('change', function() { updateAddress($(this).val(), $('#consignor_address'), '#origin'); });
        $('#consignee_id').on('change', function() { updateAddress($(this).val(), $('#consignee_address'), '#destination'); });
        
        $('#vehicle_id').on('change', setAssignedDriver);
        $('#is_shipping_different').on('change', function() { $('#shipping-address-fields').toggleClass('hidden', !$(this).is(':checked')); });

        if (bookingInvoices.length > 0) {
            $('#invoice-list').empty();
            bookingInvoices.forEach(invoice => addInvoiceRow(invoice));
        } else { addInvoiceRow(); }

        // --- NEW Event Handlers for Dependent Dropdowns in Modal ---
        $(document).on('change', '#modal_country', function() {
            const countryId = $(this).val();
            const stateSelect = $('#modal_state');
            const citySelect = $('#modal_city');
            
            stateSelect.empty().append('<option value="">Loading...</option>');
            citySelect.empty().append('<option value="">Select State first</option>').prop('disabled', true);

            if (!countryId) {
                stateSelect.empty().append('<option value="">Select Country first</option>').prop('disabled', true);
                return;
            }

            const filteredStates = statesData.filter(state => state.country_id == countryId);
            
            stateSelect.empty().append('<option value="">Select State</option>');
            if (filteredStates.length > 0) {
                filteredStates.forEach(state => {
                    stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                });
                stateSelect.prop('disabled', false);
            } else {
                stateSelect.empty().append('<option value="">No states found</option>').prop('disabled', true);
            }
        });

        $(document).on('change', '#modal_state', function() {
            const stateId = $(this).val();
            const citySelect = $('#modal_city');
            
            citySelect.empty().append('<option value="">Loading...</option>');

            if (!stateId) {
                citySelect.empty().append('<option value="">Select State first</option>').prop('disabled', true);
                return;
            }

            const filteredCities = modalCitiesData.filter(city => city.state_id == stateId);
            
            citySelect.empty().append('<option value="">Select City</option>');
            if (filteredCities.length > 0) {
                filteredCities.forEach(city => {
                    citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                });
                citySelect.prop('disabled', false);
            } else {
                citySelect.empty().append('<option value="">No cities found</option>').prop('disabled', true);
            }
        });
        
        <?php if ($edit_mode): ?>
        // In edit mode, we still set the values from the booking data.
        // The `updateAddress` calls will just fill the address boxes.
        $('#consignor_id').val('<?php echo $booking_data['consignor_id']; ?>').trigger('change');
        $('#consignee_id').val('<?php echo $booking_data['consignee_id']; ?>').trigger('change');
        
        // We explicitly set the origin/destination from the *saved booking*, 
        // not the party's default, as the user might have changed it.
        $('#origin').val('<?php echo $booking_data['origin']; ?>').trigger('change');
        $('#destination').val('<?php echo $booking_data['destination']; ?>').trigger('change');
        
        $('#description_id').val('<?php echo $booking_data['description_id']; ?>').trigger('change');
        $('#package_type').val('<?php echo $booking_data['package_type']; ?>');
        $('#billing_type').val('<?php echo $booking_data['billing_type']; ?>');
        $('#broker_id').val('<?php echo $booking_data['broker_id']; ?>').trigger('change');
        $('#vehicle_id').val('<?php echo $booking_data['vehicle_id']; ?>').trigger('change');
        if ('<?php echo $booking_data['net_weight']; ?>' === 'FTL') { $('#net_weight_ftl').prop('checked', true); }
        $('#net_weight_unit').val('<?php echo $booking_data['net_weight_unit']; ?>');
        handleFtlCheckbox($('#net_weight_ftl'), $('#net_weight'), $('#net_weight_unit'));
        if ('<?php echo $booking_data['chargeable_weight']; ?>' === 'FTL') { $('#chargeable_weight_ftl').prop('checked', true); }
        $('#chargeable_weight_unit').val('<?php echo $booking_data['chargeable_weight_unit']; ?>');
        handleFtlCheckbox($('#chargeable_weight_ftl'), $('#chargeable_weight'), $('#chargeable_weight_unit'));
        <?php endif; ?>
    });

    window.onload = function() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    };
</script>
</body>
</html>

