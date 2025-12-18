<?php
// --- For Debugging: Temporarily add these lines to see detailed errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -----------------------------------------------------------------------

session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager', 'staff'])) {
    header("location: index.php");
    exit;
}

// --- Data Fetching for Dropdowns ---
$branches = [];
if ($_SESSION['role'] === 'admin') {
    $sql_branches = "SELECT id, name FROM branches ORDER BY name ASC";
    if ($result = $mysqli->query($sql_branches)) {
        while ($row = $result->fetch_assoc()) {
            $branches[] = $row;
        }
    }
}

// --- Dashboard Data Calculation ---
$branch_conditions_string = "";
$selected_branch_ids = [];

// Apply branch filter for non-admins
if (in_array($_SESSION['role'], ['manager', 'staff'])) {
    $branch_id = $_SESSION['branch_id'] ?? 0;
    $branch_conditions_string = " WHERE s.branch_id = " . $branch_id;
    $selected_branch_ids[] = $branch_id;

} elseif ($_SESSION['role'] === 'admin') {
    // Admins can filter by one or more branches
    if (isset($_GET['branch_ids']) && is_array($_GET['branch_ids'])) {
        $selected_branch_ids = array_map('intval', $_GET['branch_ids']);
        if (!empty($selected_branch_ids)) {
            $branch_conditions_string = " WHERE s.branch_id IN (" . implode(',', $selected_branch_ids) . ")";
        }
    }
}

// Helper function now assumes the shipments table is aliased as 's'
function get_where_clause($existing_conditions = []) {
    global $branch_conditions_string;
    if (empty($existing_conditions)) {
        return $branch_conditions_string;
    }
    return ($branch_conditions_string ? $branch_conditions_string . " AND " : " WHERE ") . implode(" AND ", $existing_conditions);
}


// KPI Calculations
$total_bookings = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause())->fetch_assoc()['count'];
$booked_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["status = 'Booked'"]))->fetch_assoc()['count'];
$in_transit_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["status = 'In Transit'"]))->fetch_assoc()['count'];
$delivered_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["status = 'Delivered'"]))->fetch_assoc()['count'];
$completed_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["status = 'Completed'"]))->fetch_assoc()['count'];

// ✅ MOVED: Financial KPIs moved inside the role check
$invoiced_count = 0;
$pending_invoice_count = 0;
$awaiting_payment_count = 0;
$payment_received_total = 0;

if (in_array($_SESSION['role'], ['admin', 'manager'])) {
    $invoiced_count = $mysqli->query("SELECT COUNT(DISTINCT s.id) as count FROM shipments s JOIN invoice_items ii ON s.id = ii.shipment_id" . get_where_clause())->fetch_assoc()['count'];
    $pending_invoice_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s " . get_where_clause(["payment_entry_status = 'Done'", "NOT EXISTS (SELECT 1 FROM invoice_items ii WHERE ii.shipment_id = s.id)"]))->fetch_assoc()['count'];
    $awaiting_payment_count = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["payment_entry_status = 'Pending'"]))->fetch_assoc()['count'];
    $payment_received_query = "SELECT SUM(COALESCE(ip.amount_received, 0) + COALESCE(ip.tds_amount, 0)) as total FROM invoice_payments ip JOIN invoice_items ii ON ip.invoice_id = ii.invoice_id JOIN shipments s ON ii.shipment_id = s.id" . get_where_clause();
    $payment_received_total = $mysqli->query($payment_received_query)->fetch_assoc()['total'] ?? 0;
}


// Booking Comparison
$current_month_bookings = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["MONTH(consignment_date) = MONTH(CURDATE())", "YEAR(consignment_date) = YEAR(CURDATE())"]))->fetch_assoc()['count'];
$last_month_bookings = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["consignment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"]))->fetch_assoc()['count'];
$last_3_months_bookings = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["consignment_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)"]))->fetch_assoc()['count'];
$last_6_months_bookings = $mysqli->query("SELECT COUNT(id) as count FROM shipments s" . get_where_clause(["consignment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"]))->fetch_assoc()['count'];


// Chart Data - Shipment Status
$status_counts_query = "SELECT status, COUNT(id) as count FROM shipments s" . get_where_clause() . " GROUP BY status";
$status_counts_result = $mysqli->query($status_counts_query);
$status_chart_labels = [];
$status_chart_data = [];
if ($status_counts_result) {
    while ($row = $status_counts_result->fetch_assoc()) {
        $status_chart_labels[] = $row['status'];
        $status_chart_data[] = $row['count'];
    }
}

// Chart Data - Booking Performance Trend (Last 6 Months)
$booking_trend_labels = [];
$booking_trend_datasets = [];

for ($i = 5; $i >= 0; $i--) {
    $booking_trend_labels[] = date('M Y', strtotime("-$i months"));
}

$branch_comparison_query = "
    SELECT b.name as branch_name, DATE_FORMAT(s.consignment_date, '%b %Y') as month, COUNT(s.id) as count 
    FROM shipments s
    JOIN branches b ON s.branch_id = b.id
    " . get_where_clause(["s.consignment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"]) . "
    GROUP BY branch_name, month 
    ORDER BY s.consignment_date ASC";

$booking_trend_result = $mysqli->query($branch_comparison_query);
$branch_data = [];
if($booking_trend_result) {
    while($row = $booking_trend_result->fetch_assoc()){
        $branch_data[$row['branch_name']][$row['month']] = $row['count'];
    }
}

$colors = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#6366f1', '#a855f7', '#ec4899'];
$color_index = 0;

foreach ($branch_data as $branch_name => $monthly_counts) {
    $data_points = [];
    foreach ($booking_trend_labels as $label) {
        $data_points[] = $monthly_counts[$label] ?? 0;
    }
    $color = $colors[$color_index % count($colors)];
    $booking_trend_datasets[] = [
        'label' => $branch_name,
        'data' => $data_points,
        'fill' => false,
        'borderColor' => $color,
        'backgroundColor' => $color,
        'tension' => 0.4
    ];
    $color_index++;
}

// Vehicle Document Expiry (within 30 days)
$vehicle_expiry_sql = "SELECT id, vehicle_number, owner_name, rc_expiry, insurance_expiry, tax_expiry, fitness_expiry, permit_expiry 
                       FROM vehicles 
                       WHERE is_active = 1 AND (
                         rc_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR
                         insurance_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR
                         tax_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR
                         fitness_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR
                         permit_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                       ) ORDER BY vehicle_number ASC";
$vehicle_expiry_result = $mysqli->query($vehicle_expiry_sql);
$expiring_vehicles = [];
if ($vehicle_expiry_result) { while ($row = $vehicle_expiry_result->fetch_assoc()) { $expiring_vehicles[] = $row; } }

// Driver Document Expiry (within 30 days)
$driver_expiry_sql = "SELECT id, name, contact_number, license_expiry_date FROM drivers WHERE is_active = 1 AND license_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY license_expiry_date ASC";
$driver_expiry_result = $mysqli->query($driver_expiry_sql);
$expiring_drivers = [];
if ($driver_expiry_result) { while ($row = $driver_expiry_result->fetch_assoc()) { $expiring_drivers[] = $row; } }

// E-Way Bill Expiry Today
$eway_expiry_sql = "SELECT s.consignment_no, si.eway_bill_no, si.eway_bill_expiry, p.name as consignor_name
                    FROM shipment_invoices si
                    JOIN shipments s ON si.shipment_id = s.id
                    JOIN parties p ON s.consignor_id = p.id
                    " . get_where_clause(["si.eway_bill_expiry = CURDATE()"]);
$eway_expiry_result = $mysqli->query($eway_expiry_sql);
$expiring_eways = [];
if ($eway_expiry_result) { while ($row = $eway_expiry_result->fetch_assoc()) { $expiring_eways[] = $row; } }

// In-Transit Shipment Locations Query
$in_transit_locations_sql = "
    SELECT 
        s.id,
        s.consignment_no, 
        v.vehicle_number, 
        d.name as driver_name, 
        st_latest.location,
        st_latest.created_at as last_updated
    FROM shipments s
    LEFT JOIN vehicles v ON s.vehicle_id = v.id
    LEFT JOIN drivers d ON s.driver_id = d.id
    LEFT JOIN (
        SELECT 
            st1.shipment_id, 
            st1.location, 
            st1.created_at
        FROM shipment_tracking st1
        INNER JOIN (
            SELECT shipment_id, MAX(id) as max_id
            FROM shipment_tracking
            GROUP BY shipment_id
        ) st_max ON st1.shipment_id = st_max.shipment_id AND st1.id = st_max.max_id
    ) st_latest ON s.id = st_latest.shipment_id
    " . get_where_clause(["s.status = 'In Transit'", "st_latest.location IS NOT NULL"]);

$in_transit_result = $mysqli->query($in_transit_locations_sql);
$in_transit_data = [];
if ($in_transit_result) {
    while ($row = $in_transit_result->fetch_assoc()) {
        $in_transit_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--multiple { border-radius: 0.375rem; border: 1px solid #d1d5db; }
        
        #inTransitMap { height: 450px; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50 overflow-hidden">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
            <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden">
                            <i class="fas fa-bars fa-lg"></i>
                        </button>
                        <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="mb-6">
                    <form method="get" class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-lg shadow-sm">
                        <label for="branch_ids" class="text-sm font-medium text-gray-700">Compare Branches:</label>
                        <select name="branch_ids[]" id="branch_ids" multiple="multiple" class="block w-full sm:w-auto sm:flex-1">
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php if(in_array($branch['id'], $selected_branch_ids)) echo 'selected'; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Apply Filter</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Operational Workflow</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            
                            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-clipboard-list"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Booked</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $booked_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-violet-500 to-violet-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-truck-fast"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">In Transit</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $in_transit_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-check-double"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Delivered (Awaiting POD)</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $delivered_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-slate-500 to-slate-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-flag-checkered"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Completed (POD Recd.)</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $completed_count; ?></p>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Financials & Summary</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-box-archive"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Total Bookings</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $total_bookings; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-rose-500 to-rose-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-cash-register"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Awaiting Payment Entry</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $awaiting_payment_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-hourglass-half"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Invoice Pending</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $pending_invoice_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-700 to-gray-800 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Invoice Created</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap"><?php echo $invoiced_count; ?></p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-4">
                                <div class="text-5xl opacity-70 flex-shrink-0"><i class="fas fa-rupee-sign"></i></div>
                                <div class="text-center sm:text-right flex-1">
                                    <h4 class="text-lg font-semibold whitespace-normal">Payment Received</h4>
                                    <p class="text-3xl font-bold whitespace-nowrap">₹<?php echo number_format($payment_received_total, 2); ?></p>
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php endif; ?>
                    </div>

                <div class="bg-white p-6 rounded-xl shadow-md my-6">
                    <h3 class="text-lg font-semibold mb-4">Live In-Transit Shipments (<?php echo count($in_transit_data); ?>)</h3>
                    <?php if(empty($in_transit_data)): ?>
                        <p class="text-center text-gray-500">No shipments are currently in transit.</p>
                    <?php else: ?>
                        <div id="inTransitMap" class="rounded-lg z-10"></div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 my-6">
                    <div class="lg:col-span-1 space-y-6">
                         <h3 class="text-xl font-semibold text-gray-700">Booking Performance</h3>
                         
                         <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <h4 class="text-gray-500 font-medium whitespace-normal">Current Month</h4>
                                <p class="text-3xl font-bold text-gray-800 whitespace-nowrap"><?php echo $current_month_bookings; ?></p>
                            </div>
                            <div class="text-4xl text-indigo-400 flex-shrink-0"><i class="fas fa-calendar-check"></i></div>
                         </div>
                         <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <h4 class="text-gray-500 font-medium whitespace-normal">Last Month</h4>
                                <p class="text-3xl font-bold text-gray-800 whitespace-nowrap"><?php echo $last_month_bookings; ?></p>
                            </div>
                            <div class="text-4xl text-indigo-400 flex-shrink-0"><i class="fas fa-calendar-day"></i></div>
                         </div>
                         <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <h4 class="text-gray-500 font-medium whitespace-normal">Last 3 Months</h4>
                                <p class="text-3xl font-bold text-gray-800 whitespace-nowrap"><?php echo $last_3_months_bookings; ?></p>
                            </div>
                            <div class="text-4xl text-indigo-400 flex-shrink-0"><i class="fas fa-calendar-weeks"></i></div>
                         </div>
                         <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <h4 class="text-gray-500 font-medium whitespace-normal">Last 6 Months</h4>
                                <p class="text-3xl font-bold text-gray-800 whitespace-nowrap"><?php echo $last_6_months_bookings; ?></p>
                            </div>
                            <div class="text-4xl text-indigo-400 flex-shrink-0"><i class="fas fa-calendar-alt"></i></div>
                         </div>
                    </div>
                     <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
                        <h3 class="text-lg font-semibold mb-4">Monthly Booking Trends (Last 6 Months)</h3>
                        <canvas id="bookingTrendChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md my-6">
                    <h3 class="text-lg font-semibold mb-4">Shipment Status Overview</h3>
                    <canvas id="shipmentStatusChart"></canvas>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <h3 class="text-lg font-semibold mb-4 text-red-600 flex items-center"><i class="fas fa-triangle-exclamation mr-2"></i> Vehicle Documents Expiring (Next 30 Days)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white text-sm">
                                <thead class="bg-gray-50"><tr><th class="py-2 px-4 text-left font-semibold">Vehicle No</th><th class="py-2 px-4 text-left font-semibold">Document</th><th class="py-2 px-4 text-left font-semibold">Expiry Date</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if(empty($expiring_vehicles)): ?>
                                        <tr><td colspan="3" class="py-4 text-center text-gray-500">No vehicle documents expiring soon.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($expiring_vehicles as $vehicle): 
                                            $expiries = ['RC' => $vehicle['rc_expiry'], 'Insurance' => $vehicle['insurance_expiry'], 'Tax' => $vehicle['tax_expiry'], 'Fitness' => $vehicle['fitness_expiry'], 'Permit' => $vehicle['permit_expiry']];
                                            foreach($expiries as $doc => $date): if($date && strtotime($date) >= time() && strtotime($date) <= strtotime('+30 days')): ?>
                                                <tr><td class="py-2 px-4"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></td><td class="py-2 px-4"><?php echo $doc; ?></td><td class="py-2 px-4 text-red-600 font-semibold"><?php echo date("d-m-Y", strtotime($date)); ?></td></tr>
                                        <?php endif; endforeach; endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                         <h3 class="text-lg font-semibold mb-4 text-red-600 flex items-center"><i class="fas fa-id-card-clip mr-2"></i> Driver Documents Expiring (Next 30 Days)</h3>
                         <div class="overflow-x-auto">
                            <table class="min-w-full bg-white text-sm">
                                <thead class="bg-gray-50"><tr><th class="py-2 px-4 text-left font-semibold">Driver Name</th><th class="py-2 px-4 text-left font-semibold">Expiry Date</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                     <?php if(empty($expiring_drivers)): ?>
                                        <tr><td colspan="2" class="py-4 text-center text-gray-500">No driver documents expiring soon.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($expiring_drivers as $driver): ?>
                                        <tr><td class="py-2 px-4"><?php echo htmlspecialchars($driver['name']); ?></td><td class="py-2 px-4 text-red-600 font-semibold"><?php echo date("d-m-Y", strtotime($driver['license_expiry_date'])); ?></td></tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
                         <h3 class="text-lg font-semibold mb-4 text-orange-600 flex items-center"><i class="fas fa-file-invoice mr-2"></i> E-Way Bills Expiring Today</h3>
                         <div class="overflow-x-auto">
                            <table class="min-w-full bg-white text-sm">
                                <thead class="bg-gray-50"><tr><th class="py-2 px-4 text-left font-semibold">CN No.</th><th class="py-2 px-4 text-left font-semibold">E-Way Bill No.</th><th class="py-2 px-4 text-left font-semibold">Consignor</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                     <?php if(empty($expiring_eways)): ?>
                                        <tr><td colspan="3" class="py-4 text-center text-gray-500">No E-Way bills expiring today.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($expiring_eways as $eway): ?>
                                        <tr><td class="py-2 px-4"><?php echo htmlspecialchars($eway['consignment_no']); ?></td><td class="py-2 px-4"><?php echo htmlspecialchars($eway['eway_bill_no']); ?></td><td class="py-2 px-4"><?php echo htmlspecialchars($eway['consignor_name']); ?></td></tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div> 
                    </div>
                </div>
                <?php include 'footer.php'; ?>
            </main> 
        </div>
    </div>
    <script>
        $(document).ready(function() {
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

            // --- Page Specific Scripts ---
            if ($('#branch_ids').length) {
                $('#branch_ids').select2({
                    placeholder: "Select branches to compare",
                    allowClear: true,
                    width: 'style'
                });
            }

            // In-Transit Map Logic
            if ($('#inTransitMap').length) {
                const inTransitData = <?php echo json_encode($in_transit_data); ?>;
                
                // --- Geolocation Mapping ---
                const locationCoords = {
                    'Guwahati': [26.14, 91.73],
                    'Silchar': [24.83, 92.79],
                    'Nagaon': [26.35, 92.68],
                    'Tura': [25.51, 90.22],
                    'Karimganj': [24.87, 92.36],
                    'Maibong': [25.30, 93.17],
                    'Haflong': [25.17, 93.03],
                    'Khanpui': [23.90, 92.68],
                    'Bawngkawn': [23.74, 92.73],
                    'Aizawl': [23.72, 92.71],
                    'Lunglei': [22.88, 92.73],
                    'Saiha': [22.48, 92.97],
                    'Rangvamual': [23.63, 92.69],
                    'Agartala': [23.83, 91.28],
                    'Teliamura': [23.85, 91.63],
                    'Udaipur': [23.53, 91.48],
                    'Kumarghat': [24.16, 91.83],
                    'Dharmanagar': [24.37, 92.17],
                    'Belonia': [23.25, 91.45],
                    'Khowai': [24.06, 91.60],
                    'Kolasib': [24.23, 92.67],
                    'Gohpur': [26.88, 93.63],
                    'Sivsagar': [26.98, 94.63],
                    'Jorhat': [26.75, 94.22],
                    'North Lakhimpur': [27.23, 94.12],
                    'Bilasipara': [26.23, 90.23],
                    'Dhubri': [26.02, 89.97],
                    'Bongaigaon': [26.47, 90.56],
                    'Sapatgaram': [26.40, 90.27],
                    'Srirampur': [26.43, 89.98],
                    'Ladrymbai': [25.43, 92.39],
                    'Phulbari': [25.86, 90.02],
                    'Williamnagar': [25.61, 90.60],
                    'Mendipathar': [25.92, 90.62],
                    'Krishnai': [26.00, 90.81],
                    'Dudhnoi': [25.98, 90.73],
                    'Kokrajhar': [26.40, 90.27],
                    'Barpeta town': [26.33, 91.00],
                    'Digarkhal': [24.81, 92.62],
                    'Churaibari': [24.33, 92.31],
                    'Mankachar': [25.53, 89.86],

                    'Hatsingimari': [25.86, 89.98],
                    'Phuentsholing': [26.85, 89.38],
                    'Thimphu town': [27.47, 89.63],
                    'Shilong': [25.57, 91.89],
                    'Malidor': [24.71, 92.42],
                    'Lumding': [25.75, 93.17],
                    'Karupetia': [26.51, 92.13],
                    'Sairang': [23.80, 92.67],
                    'Howly': [26.43, 90.97],
                    'Singimari': [25.75, 90.63],
                    'Champaknagar': [23.77, 91.38],
                    'Bhaga Bazar': [24.70, 92.83],
                    'Lakhipur': [24.80, 93.02],
                    'Kalacherra': [24.23, 91.95],
                    'Gandacherra': [23.86, 91.78],
                    'Abdullapur': [24.57, 92.30],
                    'Moranhat': [27.18, 94.93],
                    'Bagmara': [25.19, 90.64],
                    'Tulamura': [23.45, 91.43],
                    'Hailakandi': [24.68, 92.57],
                    'Mahendraganj': [25.30, 89.84],
                    'Tikrikilla': [25.86, 90.30],
                    'Lawngtlai': [22.53, 92.88],
                    'Kakraban': [23.40, 91.43],
                    'Nalchar': [23.51, 91.43]
                };

                // Set initial map view. Centered on Northeast India [lat, lng]
                const map = L.map('inTransitMap').setView([25.5, 92.9], 7); 

                // Add the map "skin" from OpenStreetMap (free)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                let markers = [];
                inTransitData.forEach(shipment => {
                    // Find coordinates by matching the location string
                    const cleanLocation = shipment.location ? shipment.location.trim() : '';
                    const coords = locationCoords[cleanLocation];
                    
                    // Only plot if we have coordinates for the location
                    if (coords) {
                        const popupContent = `
                            <div class='text-sm space-y-1'>
                                <div class='font-bold text-base'><a href='view_shipment_details.php?id=${shipment.id}' target='_blank' class='text-indigo-600 hover:underline'>${shipment.consignment_no}</a></div>
                                <div><b>Vehicle:</b> ${shipment.vehicle_number || 'N/A'}</div>
                                <div><b>Driver:</b> ${shipment.driver_name || 'N/A'}</div>
                                <div><b>Last Location:</b> ${shipment.location}</div>
                                <div><b>Updated:</b> ${new Date(shipment.last_updated).toLocaleString()}</div>
                            </div>
                        `;
                        
                        // Create a truck icon
                        const truckIcon = L.divIcon({
                            html: '<i class="fas fa-truck fa-2x text-violet-600"></i>',
                            className: 'bg-transparent border-0',
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });

                        let marker = L.marker(coords, { icon: truckIcon }).addTo(map).bindPopup(popupContent);
                        markers.push(marker);
                    } else {
                        // Log a warning for locations you need to add to the map
                        if(cleanLocation) {
                             console.warn(`No coordinates found for location: '${cleanLocation}'. Add it to the locationCoords object in dashboard.php`);
                        }
                    }
                });
                
                // Auto-zoom the map to fit all markers
                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.3)); // Add 30% padding
                }
            }


            // Chart logic
            if (document.getElementById('shipmentStatusChart')) {
                const statusCtx = document.getElementById('shipmentStatusChart').getContext('2d');
                new Chart(statusCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($status_chart_labels); ?>,
                        datasets: [{
                            label: '# of Shipments',
                            data: <?php echo json_encode($status_chart_data); ?>,
                            backgroundColor: ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#6366f1', '#a855f7', '#6b7280'],
                            borderRadius: 5,
                        }]
                    },
                    options: { scales: { y: { beginAtZero: true } }, responsive: true, plugins: { legend: { display: false } } }
                });
            }

            if (document.getElementById('bookingTrendChart')) {
                const trendCtx = document.getElementById('bookingTrendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($booking_trend_labels); ?>,
                        datasets: <?php echo json_encode($booking_trend_datasets); ?>
                    },
                    options: { scales: { y: { beginAtZero: true } }, responsive: true }
                });
            }
        });
    </script>
   
</body>
</html>
