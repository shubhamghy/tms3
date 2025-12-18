<?php
session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

$is_admin = $_SESSION['role'] === 'admin';

// --- Filter Handling ---
$today = new DateTime();
$thirty_days_ago = (new DateTime())->sub(new DateInterval('P30D'));

$filter_start_date = $_GET['start_date'] ?? $thirty_days_ago->format('Y-m-d');
$filter_end_date = $_GET['end_date'] ?? $today->format('Y-m-d');
$filter_branch_id = $is_admin ? ($_GET['branch_id'] ?? '') : $_SESSION['branch_id'];
// --- âœ… NEW: Added new filters ---
$filter_vehicle_id = $_GET['vehicle_id'] ?? '';
$filter_consignor_id = $_GET['consignor_id'] ?? '';
$filter_consignee_id = $_GET['consignee_id'] ?? '';


// --- Data Fetching ---
$report_data = [];
$total_revenue = 0;
$total_shipment_expenses = 0;
$total_gross_profit = 0;

// Query 1: Get profit from individual shipments
// --- âœ… MODIFIED: Added JOINs for vehicles and parties ---
$sql_shipments = "
    SELECT 
        s.id, s.consignment_no, s.consignment_date, s.origin, s.destination, br.name as branch_name,
        COALESCE((SELECT sp.amount FROM shipment_payments sp WHERE sp.shipment_id = s.id AND sp.payment_type = 'Billing Rate'), 0) AS income,
        COALESCE((SELECT sp.amount FROM shipment_payments sp WHERE sp.shipment_id = s.id AND sp.payment_type = 'Lorry Hire'), 0) AS lorry_hire,
        COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.shipment_id = s.id), 0) AS other_expenses
    FROM shipments s
    LEFT JOIN branches br ON s.branch_id = br.id
    LEFT JOIN vehicles v ON s.vehicle_id = v.id
    LEFT JOIN parties p_consignor ON s.consignor_id = p_consignor.id
    LEFT JOIN parties p_consignee ON s.consignee_id = p_consignee.id
";

$where_clauses = [];
$params = [];
$types = "";

$where_clauses[] = "s.consignment_date BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

$branch_filter_sql = "";
if ($is_admin) {
    if (!empty($filter_branch_id)) {
        $where_clauses[] = "s.branch_id = ?";
        $params[] = $filter_branch_id;
        $types .= "i";
        $branch_filter_sql = " AND branch_id = " . intval($filter_branch_id);
    }
} else {
    $where_clauses[] = "s.branch_id = ?";
    $params[] = $_SESSION['branch_id'];
    $types .= "i";
    $branch_filter_sql = " AND branch_id = " . intval($_SESSION['branch_id']);
}

// --- âœ… NEW: Add new filters to WHERE clause ---
if (!empty($filter_vehicle_id)) {
    $where_clauses[] = "s.vehicle_id = ?";
    $params[] = $filter_vehicle_id;
    $types .= "i";
}
if (!empty($filter_consignor_id)) {
    $where_clauses[] = "s.consignor_id = ?";
    $params[] = $filter_consignor_id;
    $types .= "i";
}
if (!empty($filter_consignee_id)) {
    $where_clauses[] = "s.consignee_id = ?";
    $params[] = $filter_consignee_id;
    $types .= "i";
}
// --- End of new WHERE clauses ---

if (!empty($where_clauses)) {
    $sql_shipments .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_shipments .= " ORDER BY s.consignment_date DESC";

$stmt = $mysqli->prepare($sql_shipments);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['total_expenses'] = $row['lorry_hire'] + $row['other_expenses'];
    $row['profit_loss'] = $row['income'] - $row['total_expenses'];
    $report_data[] = $row;
    $total_revenue += $row['income'];
    $total_shipment_expenses += $row['total_expenses'];
    $total_gross_profit += $row['profit_loss'];
}
$stmt->close();

// --- MODIFIED: Fetch Operational and Salary Expenses Separately ---
$total_op_expenses = 0;
$total_salary_expenses = 0;

// This query remains unchanged as operational/salary expenses are not tied to shipments (shipment_id IS NULL)
// They are correctly filtered by branch via $branch_filter_sql
$op_expense_sql = "SELECT category, SUM(amount) as total FROM expenses WHERE shipment_id IS NULL AND expense_date BETWEEN ? AND ? {$branch_filter_sql} GROUP BY category";
$stmt_op = $mysqli->prepare($op_expense_sql);
$stmt_op->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt_op->execute();
$result_op = $stmt_op->get_result();
while($row_op = $result_op->fetch_assoc()) {
    if ($row_op['category'] === 'Salary') {
        $total_salary_expenses += $row_op['total'];
    } else {
        $total_op_expenses += $row_op['total'];
    }
}
$stmt_op->close();


// --- Final Calculation: Net Profit ---
$total_expenses = $total_shipment_expenses + $total_op_expenses + $total_salary_expenses;
$net_profit = $total_revenue - $total_expenses;


// --- âœ… NEW: Fetch data for filter dropdowns ---
$branches = [];
if ($is_admin) {
    $branches = $mysqli->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
}
// --- ðŸ”´ FIX: Changed 'registration_no' to 'vehicle_number' ---
$vehicles = $mysqli->query("SELECT id, vehicle_number FROM vehicles WHERE is_active = 1 ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);
// (Assuming 'parties' table with 'id' and 'name' for both consignors and consignees)
$parties = $mysqli->query("SELECT id, name FROM parties WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
// --- End of new data fetch ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Report - TMS</title>
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
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
        @media print {
            body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none; }
        }
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
                        <h1 class="text-xl font-semibold text-gray-800">Profit & Loss Report</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]">
                
                <div class="bg-white p-4 rounded-xl shadow-md mb-6 no-print">
                    <form id="filter-form" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                        </div>
                        <?php if ($is_admin): ?>
                        <div>
                            <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch</label>
                            <select name="branch_id" id="branch_id" class="searchable-select mt-1 block w-full">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php if ($filter_branch_id == $branch['id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div>
                            <label for="vehicle_id" class="block text-sm font-medium text-gray-700">Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" class="searchable-select mt-1 block w-full">
                                <option value="">All Vehicles</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php if ($filter_vehicle_id == $vehicle['id']) echo 'selected'; ?>><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="consignor_id" class="block text-sm font-medium text-gray-700">Consignor</label>
                            <select name="consignor_id" id="consignor_id" class="searchable-select mt-1 block w-full">
                                <option value="">All Consignors</option>
                                <?php foreach ($parties as $party): ?>
                                <option value="<?php echo $party['id']; ?>" <?php if ($filter_consignor_id == $party['id']) echo 'selected'; ?>><?php echo htmlspecialchars($party['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="consignee_id" class="block text-sm font-medium text-gray-700">Consignee</label>
                            <select name="consignee_id" id="consignee_id" class="searchable-select mt-1 block w-full">
                                <option value="">All Consignees</option>
                                <?php foreach ($parties as $party): ?>
                                <option value="<?php echo $party['id']; ?>" <?php if ($filter_consignee_id == $party['id']) echo 'selected'; ?>><?php echo htmlspecialchars($party['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div class="flex items-end space-x-2">
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">Filter</button>
                            <a href="reports.php" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Reset</a>
                        </div>
                        <div class="flex items-end">
                            <a href="#" id="download-btn" class="w-full inline-flex justify-center py-2 px-4 border shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-100 hover:bg-green-200"><i class="fas fa-download mr-2"></i>Download CSV</a>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-green-500 text-white p-6 rounded-xl shadow-lg"><h4 class="text-lg opacity-80">Total Revenue</h4><p class="text-3xl font-bold">â‚¹<?php echo number_format($total_revenue, 2); ?></p></div>
                    <div class="bg-red-500 text-white p-6 rounded-xl shadow-lg"><h4 class="text-lg opacity-80">Shipment Expenses</h4><p class="text-3xl font-bold">â‚¹<?php echo number_format($total_shipment_expenses, 2); ?></p></div>
                    <div class="bg-red-600 text-white p-6 rounded-xl shadow-lg"><h4 class="text-lg opacity-80">Salary Expenses</h4><p class="text-3xl font-bold">â‚¹<?php echo number_format($total_salary_expenses, 2); ?></p></div>
                    <div class="bg-red-700 text-white p-6 rounded-xl shadow-lg"><h4 class="text-lg opacity-80">Other Op. Expenses</h4><p class="text-3xl font-bold">â‚¹<?php echo number_format($total_op_expenses, 2); ?></p></div>
                    <div class="<?php echo $net_profit >= 0 ? 'bg-blue-600' : 'bg-gray-700'; ?> text-white p-6 rounded-xl shadow-lg"><h4 class="text-lg opacity-80">Net Profit / Loss</h4><p class="text-3xl font-bold">â‚¹<?php echo number_format($net_profit, 2); ?></p></div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md print-area">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">LR No.</th>
                                    <th class="px-4 py-3 text-left font-medium">Date</th>
                                    <th class="px-4 py-3 text-left font-medium">Branch</th>
                                    <th class="px-4 py-3 text-right font-medium">Income</th>
                                    <th class="px-4 py-3 text-right font-medium">Lorry Hire</th>
                                    <th class="px-4 py-3 text-right font-medium">Other Trip Exp.</th>
                                    <th class="px-4 py-3 text-right font-medium">Gross Profit</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($report_data)): ?>
                                    <tr><td colspan="7" class="text-center py-10 text-gray-500">No data found for the selected filters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap"><a href="view_shipment_details.php?id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:underline"><?php echo htmlspecialchars($row['consignment_no']); ?></a></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d-m-Y', strtotime($row['consignment_date'])); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                        <td class="px-4 py-3 text-right text-green-600"><?php echo number_format($row['income'], 2); ?></td>
                                        <td class="px-4 py-3 text-right text-red-600"><?php echo number_format($row['lorry_hire'], 2); ?></td>
                                        <td class="px-4 py-3 text-right text-red-600"><?php echo number_format($row['other_expenses'], 2); ?></td>
                                        <td class="px-4 py-3 text-right font-bold <?php echo $row['profit_loss'] >= 0 ? 'text-green-700' : 'text-red-700'; ?>"><?php echo number_format($row['profit_loss'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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

        function toggleSidebar() { if (sidebar && sidebarOverlay) { sidebar.classList.toggle('-translate-x-full'); sidebarOverlay.classList.toggle('hidden'); } }
        if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
        if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
        
        // Init Select2 on all searchable-select classes
        $('.searchable-select').select2({ width: '100%' });

        $('#download-btn').on('click', function(e) {
            e.preventDefault();
            const form = $('#filter-form');
            const params = form.serialize();
            window.location.href = 'download_report.php?' + params;
        });
    });

    window.addEventListener('load', function() {
        document.getElementById('page-loader').style.display = 'none';
    });
    </script>
</body>
</html>
