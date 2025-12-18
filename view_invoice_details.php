<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$can_manage = in_array($user_role, ['admin', 'manager']);
$is_admin = ($user_role === 'admin');

if (!$can_manage) {
    header("location: dashboard.php");
    exit;
}

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($invoice_id === 0) {
    die("Error: No invoice ID provided.");
}

$message = "";

// --- Handle Add Payment Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment'])) {
    $amount_received = $_POST['amount_received'];
    $tds_amount = $_POST['tds_amount'] ?? 0.00;
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $reference_no = $_POST['reference_no'];
    $remarks = $_POST['remarks'] ?? null;
    $received_by = $_SESSION['id'];

    if (!empty($amount_received) || !empty($tds_amount)) { // Allow 0 payment if TDS is present
        $mysqli->begin_transaction();
        try {
            // 1. Insert into invoice_payments
            $sql_pay = "INSERT INTO invoice_payments (invoice_id, payment_date, amount_received, tds_amount, payment_mode, reference_no, remarks, received_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_pay = $mysqli->prepare($sql_pay);
            $stmt_pay->bind_param("isddsssi", $invoice_id, $payment_date, $amount_received, $tds_amount, $payment_mode, $reference_no, $remarks, $received_by);
            $stmt_pay->execute();
            $stmt_pay->close();

            // 2. Update the invoice status (check against sum of amount_received AND tds_amount)
            $sql_total = "SELECT total_amount, (SELECT SUM(COALESCE(amount_received, 0)) + SUM(COALESCE(tds_amount, 0)) FROM invoice_payments WHERE invoice_id = ?) as total_paid FROM invoices WHERE id = ?";
            $stmt_total = $mysqli->prepare($sql_total);
            $stmt_total->bind_param("ii", $invoice_id, $invoice_id);
            $stmt_total->execute();
            $totals = $stmt_total->get_result()->fetch_assoc();
            $stmt_total->close();

            $total_amount = $totals['total_amount'];
            $total_paid = $totals['total_paid'] ?? 0;
            
            // Use round() for safe comparison of decimals
            $new_status = (round($total_paid, 2) >= round($total_amount, 2)) ? 'Paid' : 'Partially Paid';
            
            $sql_update = "UPDATE invoices SET status = ? WHERE id = ?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("si", $new_status, $invoice_id);
            $stmt_update->execute();
            $stmt_update->close();

            $mysqli->commit();
            $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Payment added successfully.</div>";

        } catch (Exception $e) {
            $mysqli->rollback();
            $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error adding payment: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50'>Please fill all required fields.</div>";
    }
}

// --- NEW: Handle Delete Payment Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_payment' && isset($_GET['payment_id']) && $is_admin) {
    $payment_id_to_delete = intval($_GET['payment_id']);

    $mysqli->begin_transaction();
    try {
        // 1. Delete the payment
        $stmt_del_pay = $mysqli->prepare("DELETE FROM invoice_payments WHERE id = ? AND invoice_id = ?");
        $stmt_del_pay->bind_param("ii", $payment_id_to_delete, $invoice_id);
        $stmt_del_pay->execute();
        $stmt_del_pay->close();

        // 2. Recalculate and update the invoice status
        $sql_total = "SELECT total_amount, (SELECT SUM(COALESCE(amount_received, 0)) + SUM(COALESCE(tds_amount, 0)) FROM invoice_payments WHERE invoice_id = ?) as total_paid FROM invoices WHERE id = ?";
        $stmt_total = $mysqli->prepare($sql_total);
        $stmt_total->bind_param("ii", $invoice_id, $invoice_id);
        $stmt_total->execute();
        $totals = $stmt_total->get_result()->fetch_assoc();
        $stmt_total->close();

        $total_amount = $totals['total_amount'];
        $total_paid = $totals['total_paid'] ?? 0;

        // Determine new status (more robustly)
        $new_status = 'Unpaid'; // Default
        if (round($total_paid, 2) >= round($total_amount, 2)) {
            $new_status = 'Paid';
        } elseif ($total_paid > 0) {
            $new_status = 'Partially Paid';
        }
        
        $sql_update = "UPDATE invoices SET status = ? WHERE id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("si", $new_status, $invoice_id);
        $stmt_update->execute();
        $stmt_update->close();

        $mysqli->commit();
        $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Payment deleted successfully.</div>";

    } catch (Exception $e) {
        $mysqli->rollback();
        $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error deleting payment: " . $e->getMessage() . "</div>";
    }
}


// Handle Remove Item Action
if (isset($_GET['action']) && $_GET['action'] === 'remove_item' && isset($_GET['item_id']) && $is_admin) {
    $item_to_remove_id = intval($_GET['item_id']);
    
    $mysqli->begin_transaction();
    try {
        // Get amount to deduct
        $amount_sql = "SELECT amount FROM shipment_payments WHERE shipment_id = ? AND payment_type = 'Billing Rate'";
        $stmt_amount = $mysqli->prepare($amount_sql);
        $stmt_amount->bind_param("i", $item_to_remove_id);
        $stmt_amount->execute();
        $item_amount = $stmt_amount->get_result()->fetch_assoc()['amount'] ?? 0;
        $stmt_amount->close();

        // Delete from invoice_items
        $delete_sql = "DELETE FROM invoice_items WHERE invoice_id = ? AND shipment_id = ?";
        $stmt_delete = $mysqli->prepare($delete_sql);
        $stmt_delete->bind_param("ii", $invoice_id, $item_to_remove_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Update invoice total
        $update_sql = "UPDATE invoices SET total_amount = total_amount - ? WHERE id = ?";
        $stmt_update = $mysqli->prepare($update_sql);
        $stmt_update->bind_param("di", $item_amount, $invoice_id);
        $stmt_update->execute();
        $stmt_update->close();

        // TODO: Need to re-evaluate invoice status here as well

        $mysqli->commit();
        $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Shipment removed successfully.</div>";
    } catch (Exception $e) {
        $mysqli->rollback();
        $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error removing shipment: " . $e->getMessage() . "</div>";
    }
}


// Fetch Invoice Details
$sql_invoice = "SELECT i.*, p.name as consignor_name FROM invoices i JOIN parties p ON i.consignor_id = p.id WHERE i.id = ?";
$stmt_invoice = $mysqli->prepare($sql_invoice);
$stmt_invoice->bind_param("i", $invoice_id);
$stmt_invoice->execute();
$invoice = $stmt_invoice->get_result()->fetch_assoc();
$stmt_invoice->close();

if (!$invoice) {
    die("Error: Invoice not found.");
}

// Fetch Invoice Items (Shipments)
$sql_items = "SELECT s.id, s.consignment_no, s.consignment_date, s.origin, s.destination, sp.amount 
              FROM invoice_items ii
              JOIN shipments s ON ii.shipment_id = s.id
              JOIN shipment_payments sp ON s.id = sp.shipment_id
              WHERE ii.invoice_id = ? AND sp.payment_type = 'Billing Rate'";
$stmt_items = $mysqli->prepare($sql_items);
$stmt_items->bind_param("i", $invoice_id);
$stmt_items->execute();
$invoice_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

// Fetch Payment History
// Added `remarks` to the select
$sql_payments = "SELECT ip.*, u.username as received_by_user FROM invoice_payments ip JOIN users u ON ip.received_by = u.id WHERE ip.invoice_id = ? ORDER BY ip.payment_date DESC";
$stmt_payments = $mysqli->prepare($sql_payments);
$stmt_payments->bind_param("i", $invoice_id);
$stmt_payments->execute();
$payment_history = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_payments->close();

$total_paid = 0.00;
$total_tds = 0.00;
foreach ($payment_history as $payment) {
    $total_paid += $payment['amount_received'];
    $total_tds += $payment['tds_amount'];
}
$balance_due = $invoice['total_amount'] - ($total_paid + $total_tds);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Details - <?php echo htmlspecialchars($invoice['invoice_no']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>

        <div class="flex flex-col flex-1 overflow-y-auto">
            <div class="flex items-center justify-between h-16 bg-white border-b border-gray-200">
                <div class="flex items-center px-4">
                    <button class="text-gray-500 focus:outline-none focus:text-gray-700 md:hidden">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
                <div class="flex items-center pr-4">
                     <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>

            <div class="p-4 md:p-8">
                <?php if(!empty($message)) echo $message; ?>
                
                <div class="bg-white p-6 md:p-8 rounded-lg shadow-md mb-8">
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Invoice Details</h2>
                            <p class="text-gray-600">Invoice No: <?php echo htmlspecialchars($invoice['invoice_no']); ?></p>
                        </div>
                        <a href="view_invoices.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50"><i class="fas fa-arrow-left mr-2"></i> Back to Invoices</a>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 border-y py-4 mb-6">
                        <div><strong class="block text-gray-500 text-sm">Consignor:</strong> <span class="text-gray-800"><?php echo htmlspecialchars($invoice['consignor_name']); ?></span></div>
                        <div><strong class="block text-gray-500 text-sm">Invoice Date:</strong> <span class="text-gray-800"><?php echo date("d M, Y", strtotime($invoice['invoice_date'])); ?></span></div>
                        <div class="text-lg"><strong class="block text-gray-500 text-sm">Total Amount:</strong> <span class="font-semibold text-gray-800">₹<?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></span></div>
                        <div class="text-lg"><strong class="block text-green-600 text-sm">Total Paid:</strong> <span class="font-semibold text-green-700">₹<?php echo htmlspecialchars(number_format($total_paid, 2)); ?></span></div>
                        <div class="text-lg"><strong class="block text-blue-600 text-sm">Total TDS:</strong> <span class="font-semibold text-blue-700">₹<?php echo htmlspecialchars(number_format($total_tds, 2)); ?></span></div>
                        <div class="text-lg"><strong class="block text-red-600 text-sm">Balance Due:</strong> <span class="font-semibold text-red-700">₹<?php echo htmlspecialchars(number_format($balance_due, 2)); ?></span></div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-800 mb-4">Included Shipments</h3>
                    <div class="overflow-x-auto mb-8">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Route</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($invoice_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><a href="view_shipment_details.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:underline"><?php echo htmlspecialchars($item['consignment_no']); ?></a></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($item['consignment_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['origin'] . ' to ' . $item['destination']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">₹<?php echo htmlspecialchars(number_format($item['amount'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <?php if ($is_admin): ?>
                                        <a href="view_invoice_details.php?action=remove_item&id=<?php echo $invoice_id; ?>&item_id=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to remove this shipment?');" title="Remove">
                                            <i class="fas fa-times-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($invoice_items)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No shipments found for this invoice.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1 bg-white p-6 md:p-8 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Add Payment</h3>
                        <form id="payment-form" method="POST" action="view_invoice_details.php?id=<?php echo $invoice_id; ?>" data-total-amount="<?php echo htmlspecialchars($invoice['total_amount']); ?>">
                            <div class="space-y-4">
                                <div>
                                    <label for="amount_received" class="block text-sm font-medium text-gray-700">Amount Received</label>
                                    <input type="number" step="0.01" name="amount_received" id="amount_received" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                
                                <div>
                                    <label for="tds_percent" class="block text-sm font-medium text-gray-700">TDS % (on Total Invoice)</label>
                                    <select id="tds_percent" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                        <option value="0">None (0%)</option>
                                        <option value="1">1%</option>
                                        <option value="2">2%</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="tds_amount" class="block text-sm font-medium text-gray-700">TDS Deducted Amount</label>
                                    <input type="number" step="0.01" name="tds_amount" id="tds_amount" value="0.00" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                
                                <div>
                                    <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date</label>
                                    <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                                </div>
                                <div>
                                    <label for="payment_mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
                                    <select name="payment_mode" id="payment_mode" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                                        <option>Bank Transfer</option><option>Cheque</option><option>Cash</option><option>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="reference_no" class="block text-sm font-medium text-gray-700">Reference / Cheque No.</label>
                                    <input type="text" name="reference_no" id="reference_no" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                
                                <div>
                                    <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"></textarea>
                                </div>
                                
                                <div>
                                    <button type="submit" name="add_payment" class="w-full py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">Record Payment</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="lg:col-span-2 bg-white p-6 md:p-8 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Payment History</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Mode</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Remarks</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase">Amount</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase">TDS</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($payment_history)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-gray-500">No payments recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($payment_history as $payment): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date("d-m-Y", strtotime($payment['payment_date'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($payment['payment_mode']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($payment['reference_no']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['remarks']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">₹<?php echo htmlspecialchars(number_format($payment['amount_received'], 2)); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">₹<?php echo htmlspecialchars(number_format($payment['tds_amount'], 2)); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if ($is_admin): ?>
                                                    <a href="manage_payments.php?action=edit&id=<?php echo $payment['id']; ?>&invoice_id=<?php echo $invoice_id; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                    <a href="view_invoice_details.php?action=delete_payment&id=<?php echo $invoice_id; ?>&payment_id=<?php echo $payment['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this payment?');" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentForm = document.getElementById('payment-form');
        const tdsPercentSelect = document.getElementById('tds_percent');
        const tdsAmountInput = document.getElementById('tds_amount');
        const amountReceivedInput = document.getElementById('amount_received');

        // Get the total invoice amount from the form's data attribute
        const totalAmount = parseFloat(paymentForm.dataset.totalAmount) || 0;

        tdsPercentSelect.addEventListener('change', function() {
            const percent = parseFloat(this.value) || 0;
            
            if (percent > 0) {
                // Calculate TDS
                const tdsCalculated = (totalAmount * percent) / 100;
                tdsAmountInput.value = tdsCalculated.toFixed(2);
                
                // Optional: Auto-fill Amount Received as Total - TDS
                // const amountReceivedCalculated = totalAmount - tdsCalculated;
                // amountReceivedInput.value = amountReceivedCalculated.toFixed(2);

            } else {
                // If 0% is selected, clear the TDS amount
                tdsAmountInput.value = '0.00';
            }
        });
    });
    </script>
    
</body>
</html>
