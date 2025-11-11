<?php
// --- STEP 1: ADD THIS AT THE VERY TOP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

// --- Data Fetching & Calculation ---
$aging_data = [];
$totals = [
    'balance' => 0, 'current' => 0, '30_days' => 0, '60_days' => 0, '90_days' => 0, '90_plus_days' => 0
];

// MODIFIED: Select credit_limit from parties table
$sql = "
    SELECT 
        p.id as party_id,
        p.name as consignor_name,
        p.credit_limit, 
        i.id as invoice_id,
        i.invoice_no,
        i.invoice_date,
        i.total_amount,
        COALESCE((SELECT SUM(amount_received) FROM invoice_payments WHERE invoice_id = i.id), 0) as paid_amount,
        DATEDIFF(CURDATE(), i.invoice_date) as age_days
    FROM invoices i
    JOIN parties p ON i.consignor_id = p.id
    WHERE i.status IN ('Generated', 'Partially Paid', 'Unpaid') AND i.total_amount > COALESCE((SELECT SUM(amount_received) FROM invoice_payments WHERE invoice_id = i.id), 0)
    ORDER BY p.name, i.invoice_date ASC
";

$result = $mysqli->query($sql);

if (!$result) {
    die("SQL Error: " . $mysqli->error);
}

// Process the results 
while ($row = $result->fetch_assoc()) {
    $balance = $row['total_amount'] - $row['paid_amount'];
    
    // Initialize party if not exists
    if (!isset($aging_data[$row['party_id']])) {
        $aging_data[$row['party_id']] = [
            'name' => $row['consignor_name'],
            'credit_limit' => $row['credit_limit'], // NEW FIELD
            'invoices' => [],
            'total_balance' => 0
        ];
    }
    
    // Categorize into aging buckets
    $invoice_details = [
        'invoice_no' => $row['invoice_no'], 'invoice_date' => $row['invoice_date'], 'total_amount' => $row['total_amount'],
        'paid_amount' => $row['paid_amount'], 'balance' => $balance, 'age_days' => $row['age_days'],
        'current' => 0, '30_days' => 0, '60_days' => 0, '90_days' => 0, '90_plus_days' => 0
    ];
    
    // Original aging logic remains sound
    if ($row['age_days'] <= 30) {
        $invoice_details['current'] = $balance;
        $totals['current'] += $balance;
    } elseif ($row['age_days'] <= 60) {
        $invoice_details['30_days'] = $balance;
        $totals['30_days'] += $balance;
    } elseif ($row['age_days'] <= 90) {
        $invoice_details['60_days'] = $balance;
        $totals['60_days'] += $balance;
    } elseif ($row['age_days'] <= 120) {
        $invoice_details['90_days'] = $balance;
        $totals['90_days'] += $balance;
    } else {
        $invoice_details['90_plus_days'] = $balance;
        $totals['90_plus_days'] += $balance;
    }
    
    $aging_data[$row['party_id']]['invoices'][] = $invoice_details;
    $aging_data[$row['party_id']]['total_balance'] += $balance;
    $totals['balance'] += $balance;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/R Aging Report - TMS</title>
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
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <header class="bg-white shadow-sm border-b border-gray-200 no-print">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <h1 class="text-xl font-semibold text-gray-800">Accounts Receivable Aging</h1>
                        <button onclick="window.print()" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-print mr-2"></i>Print Report</button>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8">
                <div class="bg-white p-6 rounded-xl shadow-md print-area">
                    <h2 class="text-2xl font-bold text-center mb-2">A/R Aging Summary</h2>
                    <p class="text-center text-sm text-gray-500 mb-6">As of <?php echo date("F j, Y"); ?></p>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr class="text-right">
                                    <th class="px-4 py-3 text-left font-semibold">Customer Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Credit Limit</th>
                                    <th class="px-4 py-3 font-semibold">Total Due</th>
                                    <th class="px-4 py-3 font-semibold">Current (1-30)</th>
                                    <th class="px-4 py-3 font-semibold">31-60 Days</th>
                                    <th class="px-4 py-3 font-semibold">61-90 Days</th>
                                    <th class="px-4 py-3 font-semibold">91-120 Days</th>
                                    <th class="px-4 py-3 font-semibold">120+ Days</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(empty($aging_data)): ?>
                                    <tr><td colspan="8" class="text-center py-10 text-gray-500">No outstanding receivables found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($aging_data as $party_id => $data): 
                                        $limit_exceeded = $data['total_balance'] > $data['credit_limit'] && $data['credit_limit'] > 0;
                                        $row_class = $limit_exceeded ? 'bg-red-50 font-bold text-red-800' : 'bg-gray-50 font-bold';
                                    ?>
                                        <tr class="text-right <?php echo $row_class; ?>">
                                            <td class="px-4 py-3 text-left">
                                                <a href="reports_ledger.php?entity_id=<?php echo $party_id; ?>&entity_type=party" class="text-indigo-600 hover:underline">
                                                    <?php echo htmlspecialchars($data['name']); ?>
                                                </a>
                                                <?php if($limit_exceeded): ?>
                                                    <i class="fas fa-exclamation-triangle text-red-500 ml-2" title="Credit limit exceeded!"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-left">
                                                <?php echo number_format($data['credit_limit'], 2); ?>
                                            </td>
                                            <td><?php echo number_format($data['total_balance'], 2); ?></td>
                                            <td><?php echo number_format(array_sum(array_column($data['invoices'], 'current')), 2); ?></td>
                                            <td><?php echo number_format(array_sum(array_column($data['invoices'], '30_days')), 2); ?></td>
                                            <td><?php echo number_format(array_sum(array_column($data['invoices'], '60_days')), 2); ?></td>
                                            <td><?php echo number_format(array_sum(array_column($data['invoices'], '90_days')), 2); ?></td>
                                            <td><?php echo number_format(array_sum(array_column($data['invoices'], '90_plus_days')), 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-gray-200 font-bold">
                                <tr class="text-right">
                                    <td class="px-4 py-3 text-left" colspan="2">GRAND TOTAL</td>
                                    <td><?php echo number_format($totals['balance'], 2); ?></td>
                                    <td><?php echo number_format($totals['current'], 2); ?></td>
                                    <td><?php echo number_format($totals['30_days'], 2); ?></td>
                                    <td><?php echo number_format($totals['60_days'], 2); ?></td>
                                    <td><?php echo number_format($totals['90_days'], 2); ?></td>
                                    <td><?php echo number_format($totals['90_plus_days'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
