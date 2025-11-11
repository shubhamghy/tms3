<?php
// --- For Debugging: Temporarily add these lines to see detailed errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -----------------------------------------------------------------------

session_start();
require_once "config.php";

// Access Control
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

// --- Filter Handling ---
$entity_id = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : 0;
$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : '';
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$entity_details = null;
$transactions = [];
$opening_balance = 0;
$closing_balance = 0;
$ledger_direction = 'party'; // 'party' (Debit=Asset, Credit=Liability) or 'broker/vehicle' (Debit=Expense/Advance, Credit=Income)

// Fetch dropdown data
$parties_list = $mysqli->query("SELECT id, name FROM parties ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$brokers_list = $mysqli->query("SELECT id, name FROM brokers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$vehicles_list = $mysqli->query("SELECT id, vehicle_number as name FROM vehicles ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);

if ($entity_id > 0 && !empty($entity_type)) {

    if ($entity_type === 'party') {
        $ledger_direction = 'party';
        $stmt = $mysqli->prepare("SELECT id, name, credit_limit FROM parties WHERE id = ?");
        $stmt->bind_param("i", $entity_id);
        $stmt->execute();
        $entity_details = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // --- 1. Calculate Party Opening Balance ---
        $ob_debit_sql = "SELECT COALESCE(SUM(total_amount), 0) as total_debits FROM invoices WHERE consignor_id = ? AND invoice_date < ?";
        $stmt_ob_d = $mysqli->prepare($ob_debit_sql);
        $stmt_ob_d->bind_param("is", $entity_id, $start_date);
        $stmt_ob_d->execute();
        $total_debits_before = $stmt_ob_d->get_result()->fetch_assoc()['total_debits'] ?? 0;
        $stmt_ob_d->close();

        $ob_credit_sql = "SELECT COALESCE(SUM(p.amount_received), 0) as total_credits FROM invoice_payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.consignor_id = ? AND p.payment_date < ?";
        $stmt_ob_c = $mysqli->prepare($ob_credit_sql);
        $stmt_ob_c->bind_param("is", $entity_id, $start_date);
        $stmt_ob_c->execute();
        $total_credits_before = $stmt_ob_c->get_result()->fetch_assoc()['total_credits'] ?? 0;
        $stmt_ob_c->close();
        
        $opening_balance = $total_debits_before - $total_credits_before;
        
        // --- 2. Fetch Transactions for Period using UNION ALL (Improved) ---
        $sql = "(SELECT invoice_date AS date, CONCAT('Invoice No: ', invoice_no) as particulars, total_amount as debit, 0 as credit
                FROM invoices WHERE consignor_id = ? AND invoice_date BETWEEN ? AND ?)
                UNION ALL
                (SELECT p.payment_date AS date, CONCAT('Payment Received (', p.payment_mode, IF(p.reference_no IS NULL, '', CONCAT(' Ref: ', p.reference_no)), ')') as particulars, 0 as debit, p.amount_received as credit
                FROM invoice_payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.consignor_id = ? AND p.payment_date BETWEEN ? AND ?)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ississ", $entity_id, $start_date, $end_date, $entity_id, $start_date, $end_date);
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } elseif ($entity_type === 'broker') {
        $ledger_direction = 'broker/vehicle';
        $stmt = $mysqli->prepare("SELECT id, name FROM brokers WHERE id = ?");
        $stmt->bind_param("i", $entity_id);
        $stmt->execute();
        $entity_details = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // --- 1. Calculate Broker Opening Balance ---
        $ob_credit_sql = "SELECT COALESCE(SUM(p.amount), 0) as total_credits FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.broker_id = ? AND p.payment_type = 'Lorry Hire' AND s.consignment_date < ?";
        $stmt_ob_c = $mysqli->prepare($ob_credit_sql);
        $stmt_ob_c->bind_param("is", $entity_id, $start_date);
        $stmt_ob_c->execute();
        $total_credits_before = $stmt_ob_c->get_result()->fetch_assoc()['total_credits'] ?? 0;
        $stmt_ob_c->close();

        // This OB Debit needs to include the new expenses table query
        $ob_debit_sql = "
            SELECT COALESCE(SUM(t.amount), 0) as total_debits FROM (
                SELECT amount FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.broker_id = ? AND p.payment_type IN ('Advance Cash', 'Advance Diesel', 'Labour Charge', 'Dala Charge', 'Damage Deduction', 'Shortage Deduction', 'Balance Payment') AND COALESCE(p.payment_date, s.consignment_date) < ?
                UNION ALL SELECT amount FROM expenses e JOIN shipments s ON e.shipment_id = s.id WHERE s.broker_id = ? AND e.expense_date < ?
            ) t";

        $stmt_ob_d = $mysqli->prepare($ob_debit_sql);
        // Bind parameters: 2 pairs of (entity_id, start_date)
        $stmt_ob_d->bind_param("isis", $entity_id, $start_date, $entity_id, $start_date);
        $stmt_ob_d->execute();
        $total_debits_before = $stmt_ob_d->get_result()->fetch_assoc()['total_debits'] ?? 0;
        $stmt_ob_d->close();
        
        $opening_balance = $total_debits_before - $total_credits_before;
        
        // --- 2. Fetch Transactions for Period using UNION ALL ---
        $sql = "(SELECT s.consignment_date as date, CONCAT('Lorry Hire for CN: ', s.consignment_no) as particulars, 0 as debit, p.amount as credit
                FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.broker_id = ? AND p.payment_type = 'Lorry Hire' AND s.consignment_date BETWEEN ? AND ?)
                UNION ALL
                (SELECT COALESCE(p.payment_date, s.consignment_date) as date, CONCAT(p.payment_type, ' for CN: ', s.consignment_no) as particulars, p.amount as debit, 0 as credit
                FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.broker_id = ? AND p.payment_type IN ('Advance Cash', 'Advance Diesel', 'Labour Charge', 'Dala Charge', 'Damage Deduction', 'Shortage Deduction', 'Balance Payment') AND COALESCE(p.payment_date, s.consignment_date) BETWEEN ? AND ?)
                UNION ALL
                (SELECT e.expense_date as date, CONCAT('Expense Entry: ', e.category, ' for CN: ', s.consignment_no) as particulars, e.amount as debit, 0 as credit
                FROM expenses e JOIN shipments s ON e.shipment_id = s.id WHERE s.broker_id = ? AND e.expense_date BETWEEN ? AND ? AND e.shipment_id IS NOT NULL)";

        // Total 9 parameters: i, s, s (3 times)
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("issississ", 
            $entity_id, $start_date, $end_date, // Section 1
            $entity_id, $start_date, $end_date, // Section 2
            $entity_id, $start_date, $end_date  // Section 3 (NEW EXPENSES)
        );
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();


    } elseif ($entity_type === 'vehicle') {
        $ledger_direction = 'broker/vehicle';
        $stmt = $mysqli->prepare("SELECT id, vehicle_number as name FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $entity_id);
        $stmt->execute();
        $entity_details = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // --- 1. Calculate Vehicle Opening P/L Balance (Revenue - Expenses before start_date) ---
        // Revenue (Lorry Hire)
        $ob_rev_sql = "SELECT COALESCE(SUM(p.amount), 0) as total_rev FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.vehicle_id = ? AND p.payment_type = 'Lorry Hire' AND s.consignment_date < ?";
        $stmt_ob_r = $mysqli->prepare($ob_rev_sql);
        $stmt_ob_r->bind_param("is", $entity_id, $start_date);
        $stmt_ob_r->execute();
        $total_rev_before = $stmt_ob_r->get_result()->fetch_assoc()['total_rev'] ?? 0;
        $stmt_ob_r->close();

        // Expenses (Trip advances, Fuel, Maintenance, Other)
        // Correctly calculates total expenses before the start date using UNION ALL subquery
        $ob_exp_sql = "
            SELECT COALESCE(SUM(t.amount), 0) as total_exp FROM (
                SELECT amount FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.vehicle_id = ? AND p.payment_type IN ('Advance Cash', 'Advance Diesel', 'Labour Charge', 'Dala Charge') AND COALESCE(p.payment_date, s.consignment_date) < ?
                UNION ALL SELECT total_cost as amount FROM fuel_logs WHERE vehicle_id = ? AND log_date < ?
                UNION ALL SELECT service_cost as amount FROM maintenance_logs WHERE vehicle_id = ? AND service_date < ?
                UNION ALL SELECT amount FROM expenses WHERE vehicle_id = ? AND expense_date < ?
            ) t";
        $stmt_ob_e = $mysqli->prepare($ob_exp_sql);
        // Bind parameters for the OB expense query (4 pairs of i, s)
        $stmt_ob_e->bind_param("isississ", $entity_id, $start_date, $entity_id, $start_date, $entity_id, $start_date, $entity_id, $start_date);
        $stmt_ob_e->execute();
        $total_exp_before = $stmt_ob_e->get_result()->fetch_assoc()['total_exp'] ?? 0;
        $stmt_ob_e->close();

        $opening_balance = $total_rev_before - $total_exp_before;
        
        // --- 2. Fetch Transactions for Period using UNION ALL ---
        $sql = "
            (SELECT s.consignment_date as date, CONCAT('Lorry Hire Revenue for CN: ', s.consignment_no) as particulars, 0 as debit, p.amount as credit
            FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.vehicle_id = ? AND p.payment_type = 'Lorry Hire' AND s.consignment_date BETWEEN ? AND ?)
            UNION ALL
            (SELECT COALESCE(p.payment_date, s.consignment_date) as date, CONCAT('Trip Advance/Expense: ', p.payment_type, ' for CN: ', s.consignment_no) as particulars, p.amount as debit, 0 as credit
            FROM shipment_payments p JOIN shipments s ON p.shipment_id = s.id WHERE s.vehicle_id = ? AND p.payment_type IN ('Advance Cash', 'Advance Diesel', 'Labour Charge', 'Dala Charge') AND COALESCE(p.payment_date, s.consignment_date) BETWEEN ? AND ?)
            UNION ALL
            (SELECT log_date as date, CONCAT('Fuel Expense at ', fuel_station) as particulars, total_cost as debit, 0 as credit
            FROM fuel_logs WHERE vehicle_id = ? AND log_date BETWEEN ? AND ?)
            UNION ALL
            (SELECT service_date as date, CONCAT('Maintenance: ', service_type) as particulars, service_cost as debit, 0 as credit
            FROM maintenance_logs WHERE vehicle_id = ? AND service_date BETWEEN ? AND ?)
            UNION ALL
            (SELECT expense_date as date, CONCAT('General Expense: ', category, ' (', paid_to, ')') as particulars, amount as debit, 0 as credit
            FROM expenses WHERE vehicle_id = ? AND expense_date BETWEEN ? AND ?)
        ";
        
        $stmt = $mysqli->prepare($sql);
        // Bind parameters for all 5 union sections
        // FIX: The type string must be exactly 15 characters long (i, s, s) repeated 5 times.
        $stmt->bind_param("isssisssisssiss", 
            $entity_id, $start_date, $end_date, // 3
            $entity_id, $start_date, $end_date, // 3
            $entity_id, $start_date, $end_date, // 3
            $entity_id, $start_date, $end_date, // 3
            $entity_id, $start_date, $end_date  // 3
        );
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    // --- Post-processing: Apply Opening Balance and Calculate Running Balance ---

    // 1. Add Opening Balance entry
    if (abs($opening_balance) > 0.01 || $entity_type === 'vehicle') { // Always show OB for vehicle P/L
        $ob_entry = [
            'date' => $start_date, 
            'particulars' => 'Opening Balance', 
            'debit' => ($ledger_direction === 'party' || $entity_type === 'broker') ? ($opening_balance >= 0 ? $opening_balance : 0) : ($opening_balance < 0 ? abs($opening_balance) : 0),
            'credit' => ($ledger_direction === 'party' || $entity_type === 'broker') ? ($opening_balance < 0 ? abs($opening_balance) : 0) : ($opening_balance >= 0 ? $opening_balance : 0),
            'balance' => $opening_balance
        ];
        array_unshift($transactions, $ob_entry);
    }
    
    // 2. Sort all transactions by date
    if (!empty($transactions)) {
        usort($transactions, function($a, $b) { 
            // Keep OB at the very top (it has the start_date)
            if ($a['particulars'] === 'Opening Balance') return -1;
            if ($b['particulars'] === 'Opening Balance') return 1;
            
            $dateA = strtotime($a['date']); $dateB = strtotime($b['date']);
            if ($dateA == $dateB) { return 0; }
            return $dateA - $dateB;
        });

        // 3. Calculate running balance
        $balance = $opening_balance;
        
        foreach ($transactions as $i => &$t) {
            
            // FIX: Ensure balance is set correctly for the first entry
            if ($t['particulars'] === 'Opening Balance') {
                $t['balance'] = $opening_balance;
                continue;
            }

            if ($ledger_direction === 'party' || $entity_type === 'broker') {
                // Party/Broker Ledger: Balance = Old Balance + Debit (Invoice/Advance/Expense) - Credit (Payment/Hire)
                $balance = $balance + ($t['debit'] - $t['credit']);
            } elseif ($entity_type === 'vehicle') {
                // Vehicle P/L: Balance = Old Balance + Credit (Revenue) - Debit (Expense)
                $balance = $balance + ($t['credit'] - $t['debit']);
            }
            $t['balance'] = $balance;
        }
        unset($t);
        
        $closing_balance = $balance;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Ledger - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single { height: 42px; border: 1px solid #d1d5db; border-radius: 0.375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 0.75rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
            <header class="bg-white shadow-sm border-b border-gray-200 no-print">
                <div class="mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16">
                    <h1 class="text-xl font-semibold text-gray-800">Accounts Ledger</h1>
                </div></div>
            </header>
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-8">
                <div class="bg-white p-4 rounded-xl shadow-md mb-6 no-print">
                    <form id="ledger-form" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label for="entity_select" class="block text-sm font-medium text-gray-700">Select Party / Broker / Vehicle</label>
                            <select id="entity_select" class="searchable-select mt-1 block w-full">
                                <option value="">-- Choose... --</option>
                                <optgroup label="Customers (Parties)">
                                    <?php foreach($parties_list as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" data-type="party" <?php if($entity_id == $item['id'] && $entity_type == 'party') echo 'selected'; ?>><?php echo htmlspecialchars($item['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Vendors (Brokers)">
                                    <?php foreach($brokers_list as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" data-type="broker" <?php if($entity_id == $item['id'] && $entity_type == 'broker') echo 'selected'; ?>><?php echo htmlspecialchars($item['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Assets (Vehicles)">
                                    <?php foreach($vehicles_list as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" data-type="vehicle" <?php if($entity_id == $item['id'] && $entity_type == 'vehicle') echo 'selected'; ?>><?php echo htmlspecialchars($item['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md">
                        </div>
                        <input type="hidden" name="entity_id" id="entity_id_hidden">
                        <input type="hidden" name="entity_type" id="entity_type_hidden">
                        <button type="submit" class="w-full py-2 px-4 border rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">View Ledger</button>
                    </form>
                </div>

                <?php if ($entity_id > 0 && $entity_details): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md print-area">
                    <div class="flex flex-col sm:flex-row justify-between items-start mb-6 border-b pb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($entity_details['name']); ?></h2>
                            <p class="text-sm text-gray-500">Statement for <?php echo date('d-M-Y', strtotime($start_date)); ?> to <?php echo date('d-M-Y', strtotime($end_date)); ?></p>
                            <?php if ($entity_type === 'party' && !empty($entity_details['credit_limit'])): ?>
                            <p class="text-xs text-gray-500 mt-1">Credit Limit: **₹<?php echo number_format($entity_details['credit_limit'], 2); ?>**</p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:text-right">
                            <div class="flex space-x-2 no-print mb-4">
                                <button onclick="window.print()" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-print mr-2"></i>Print</button>
                                <a href="download_ledger.php?entity_id=<?php echo $entity_id; ?>&entity_type=<?php echo $entity_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-download mr-2"></i>Download CSV</a>
                            </div>
                            <p class="text-sm text-gray-500">
                                <?php 
                                    if ($entity_type === 'vehicle') echo 'Net Profit/Loss';
                                    else echo 'Closing Balance';
                                ?>
                            </p>
                            <p class="text-2xl font-bold 
                                <?php 
                                    if ($entity_type === 'vehicle') {
                                        echo $closing_balance >= 0 ? 'text-green-600' : 'text-red-600';
                                    } else {
                                        echo $closing_balance >= 0 ? 'text-red-600' : 'text-green-600';
                                    }
                                ?>">
                                ₹<?php echo number_format(abs($closing_balance), 2); ?> 
                                <?php 
                                    if ($entity_type === 'vehicle') {
                                        echo $closing_balance >= 0 ? 'P' : 'L';
                                    } elseif ($ledger_direction === 'party') {
                                        echo $closing_balance >= 0 ? 'Dr' : 'Cr';
                                    } else {
                                        echo $closing_balance >= 0 ? 'Dr' : 'Cr';
                                    }
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr>
                                <th class="px-4 py-3 text-left font-medium">Date</th><th class="px-4 py-3 text-left font-medium">Particulars</th>
                                <th class="px-4 py-3 text-right font-medium"><?php echo ($entity_type === 'vehicle') ? 'Expense (₹)' : 'Debit (₹)'; ?></th>
                                <th class="px-4 py-3 text-right font-medium"><?php echo ($entity_type === 'vehicle') ? 'Revenue (₹)' : 'Credit (₹)'; ?></th>
                                <th class="px-4 py-3 text-right font-medium">Balance (₹)</th>
                            </tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($transactions)): ?>
                                    <tr><td colspan="5" class="text-center py-10 text-gray-500">No transactions found for the selected period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap"><?php echo $t['particulars'] === 'Opening Balance' ? '' : date('d-m-Y', strtotime($t['date'])); ?></td>
                                        <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($t['particulars']); ?></td>
                                        <td class="px-4 py-3 text-right text-red-600"><?php echo $t['debit'] > 0 ? number_format($t['debit'], 2) : '-'; ?></td>
                                        <td class="px-4 py-3 text-right text-green-600"><?php echo $t['credit'] > 0 ? number_format($t['credit'], 2) : '-'; ?></td>
                                        <td class="px-4 py-3 text-right font-semibold">
                                            <?php echo number_format(abs($t['balance']), 2); ?> 
                                            <?php 
                                                if ($entity_type === 'vehicle') {
                                                    echo $t['balance'] >= 0 ? 'P' : 'L';
                                                } elseif ($ledger_direction === 'party') {
                                                    echo $t['balance'] >= 0 ? 'Dr' : 'Cr';
                                                } else { // Broker
                                                    echo $t['balance'] >= 0 ? 'Dr' : 'Cr';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        $('.searchable-select').select2({ width: '100%' });

        $('#entity_select').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            $('#entity_id_hidden').val(selectedOption.val());
            $('#entity_type_hidden').val(selectedOption.data('type'));
        });

        // Set initial hidden values on load/refresh
        const selectedOption = $('#entity_select').find('option:selected');
        if (selectedOption.val()) {
            $('#entity_id_hidden').val(selectedOption.val());
            $('#entity_type_hidden').val(selectedOption.data('type'));
        }
    });
    </script>
</body>
</html>
