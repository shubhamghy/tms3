<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$can_manage = in_array($user_role, ['admin', 'manager']);

if (!$can_manage) {
    header("location: dashboard.php");
    exit;
}

// Fetch Company Details
$company_details = $mysqli->query("SELECT * FROM company_details WHERE id = 1")->fetch_assoc();

$form_message = "";
$settle_mode = false;
$view_details_mode = false;
$shipment_data = [];
$payment_summary = [];
$balance_amount = 0;
$balance_payment_details = [];

// Handle Form Submission for Settling Payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['shipment_id'])) {
    $shipment_id = intval($_POST['shipment_id']);
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $transaction_ref = trim($_POST['remarks']); // Remarks field now used for ref
    $amount_paid = (float)$_POST['balance_amount'];
    $damage_deduction = (float)($_POST['damage_deduction'] ?? 0);
    $shortage_deduction = (float)($_POST['shortage_deduction'] ?? 0);
    $created_by_id = $_SESSION['id'];

    $mysqli->begin_transaction();
    try {
        // Insert deduction records if they exist
        $deduction_sql = "INSERT INTO shipment_payments (shipment_id, payment_type, amount, payment_date, created_by_id) VALUES (?, ?, ?, ?, ?)";
        $deduction_stmt = $mysqli->prepare($deduction_sql);
        if ($damage_deduction > 0) {
            $type = 'Damage Deduction';
            $deduction_stmt->bind_param("isdsi", $shipment_id, $type, $damage_deduction, $payment_date, $created_by_id);
            if (!$deduction_stmt->execute()) { throw new Exception("Error saving damage deduction."); }
        }
        if ($shortage_deduction > 0) {
            $type = 'Shortage Deduction';
            $deduction_stmt->bind_param("isdsi", $shipment_id, $type, $shortage_deduction, $payment_date, $created_by_id);
            if (!$deduction_stmt->execute()) { throw new Exception("Error saving shortage deduction."); }
        }
        $deduction_stmt->close();

        // Insert the final settlement record
        $sql_payment = "INSERT INTO shipment_payments (shipment_id, payment_type, amount, payment_date, remarks, created_by_id) VALUES (?, 'Balance Payment', ?, ?, ?, ?)";
        $stmt_payment = $mysqli->prepare($sql_payment);
        $stmt_payment->bind_param("idssi", $shipment_id, $amount_paid, $payment_date, $transaction_ref, $created_by_id);
        if (!$stmt_payment->execute()) { throw new Exception("Error saving final payment record."); }
        $stmt_payment->close();

        // Update the vehicle payment status in the shipments table
        $sql_update = "UPDATE shipments SET vehicle_payment_status = 'Paid' WHERE id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("i", $shipment_id);
        if (!$stmt_update->execute()) { throw new Exception("Error updating shipment status."); }
        $stmt_update->close();

        $mysqli->commit();
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Vehicle payment settled successfully!</div>';

    } catch (Exception $e) {
        $mysqli->rollback();
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
    }
}


// Handle GET request for viewing or settling
if (isset($_GET['action']) && isset($_GET['id'])) {
    $shipment_id = intval($_GET['id']);
    
    if ($_GET['action'] == 'settle') {
        $settle_mode = true;
        $sql = "SELECT s.id, s.consignment_no, s.pod_doc_path, v.vehicle_number, b.name as broker_name 
                FROM shipments s 
                LEFT JOIN vehicles v ON s.vehicle_id = v.id
                LEFT JOIN brokers b ON s.broker_id = b.id
                WHERE s.id = ? AND s.status = 'Completed' AND s.vehicle_payment_status = 'Pending'";
    } elseif ($_GET['action'] == 'view_details') {
        $view_details_mode = true;
         $sql = "SELECT s.id, s.consignment_no, s.pod_doc_path, s.pod_remarks, v.vehicle_number, b.name as broker_name 
                FROM shipments s 
                LEFT JOIN vehicles v ON s.vehicle_id = v.id
                LEFT JOIN brokers b ON s.broker_id = b.id
                WHERE s.id = ? AND s.vehicle_payment_status = 'Paid'";
    }

    if (isset($sql)) {
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $shipment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $shipment_data = $result->fetch_assoc();
                
                // Fetch and calculate payment summary for both modes
                $payment_result = $mysqli->query("SELECT payment_type, amount, payment_date, remarks FROM shipment_payments WHERE shipment_id = $shipment_id");
                while($row = $payment_result->fetch_assoc()){
                    $payment_summary[$row['payment_type']] = $row;
                }
                
                $lorry_hire = (float)($payment_summary['Lorry Hire']['amount'] ?? 0);
                $total_advances = (float)($payment_summary['Advance Cash']['amount'] ?? 0) + (float)($payment_summary['Advance Diesel']['amount'] ?? 0) + (float)($payment_summary['Labour Charge']['amount'] ?? 0) + (float)($payment_summary['Dala Charge']['amount'] ?? 0);
                $balance_amount = $lorry_hire - $total_advances;

            } else {
                $settle_mode = false;
                $view_details_mode = false;
                $form_message = '<div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50">Shipment not found or status has changed.</div>';
            }
        }
    }
}

// --- Pagination & Data Fetching for Lists ---
$records_per_page = 10;

// Pending Settlements Pagination
$page_pending = isset($_GET['page_pending']) && is_numeric($_GET['page_pending']) ? (int)$_GET['page_pending'] : 1;
$offset_pending = ($page_pending - 1) * $records_per_page;
$total_pending_res = $mysqli->query("SELECT COUNT(*) FROM shipments WHERE status = 'Completed' AND vehicle_payment_status = 'Pending'");
$total_pending = $total_pending_res->fetch_row()[0];
$total_pages_pending = ceil($total_pending / $records_per_page);

$pending_settlements = [];
if (!$settle_mode && !$view_details_mode) {
    $sql_pending = "SELECT s.id, s.consignment_no, v.vehicle_number, b.name as broker_name
            FROM shipments s
            LEFT JOIN vehicles v ON s.vehicle_id = v.id
            LEFT JOIN brokers b ON s.broker_id = b.id
            WHERE s.status = 'Completed' AND s.vehicle_payment_status = 'Pending'
            ORDER BY s.consignment_date DESC LIMIT ?, ?";
    if ($stmt = $mysqli->prepare($sql_pending)) {
        $stmt->bind_param("ii", $offset_pending, $records_per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pending_settlements[] = $row;
        }
        $stmt->close();
    }
}

// Settled Payments Pagination
$page_settled = isset($_GET['page_settled']) && is_numeric($_GET['page_settled']) ? (int)$_GET['page_settled'] : 1;
$offset_settled = ($page_settled - 1) * $records_per_page;
$total_settled_res = $mysqli->query("SELECT COUNT(*) FROM shipments WHERE vehicle_payment_status = 'Paid'");
$total_settled = $total_settled_res->fetch_row()[0];
$total_pages_settled = ceil($total_settled / $records_per_page);

$settled_payments = [];
if (!$settle_mode && !$view_details_mode) {
    $sql_settled = "SELECT s.id, s.consignment_no, v.vehicle_number, b.name as broker_name
            FROM shipments s
            LEFT JOIN vehicles v ON s.vehicle_id = v.id
            LEFT JOIN brokers b ON s.broker_id = b.id
            WHERE s.vehicle_payment_status = 'Paid'
            ORDER BY s.consignment_date DESC LIMIT ?, ?";
    if ($stmt = $mysqli->prepare($sql_settled)) {
        $stmt->bind_param("ii", $offset_settled, $records_per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $settled_payments[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Settlements - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- PDF Generation Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 1rem; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <div class="flex items-center justify-between h-16 bg-white border-b border-gray-200 no-print">
                <div class="flex items-center px-4"><button class="text-gray-500 md:hidden"><i class="fas fa-bars"></i></button></div>
                <div class="flex items-center pr-4">
                     <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
            <div class="p-4 md:p-8">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($settle_mode): ?>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Settle Vehicle Payment</h2>
                    <p class="text-gray-600 mb-6">For LR No: <strong><?php echo htmlspecialchars($shipment_data['consignment_no']); ?></strong></p>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6 text-sm">
                        <h3 class="font-bold mb-2">Payment Summary</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><strong class="block text-gray-500">Lorry Hire:</strong> <?php echo number_format($lorry_hire, 2); ?></div>
                            <div><strong class="block text-gray-500">Total Advances:</strong> <?php echo number_format($total_advances, 2); ?></div>
                            <div class="font-bold text-lg"><strong class="block text-gray-500">Initial Balance:</strong> ₹<?php echo number_format($balance_amount, 2); ?></div>
                             <div><strong class="block text-gray-500">POD Document:</strong> <a href="<?php echo htmlspecialchars($shipment_data['pod_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View POD</a></div>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="shipment_id" value="<?php echo $shipment_data['id']; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium">Damage Deduction</label><input type="number" step="0.01" name="damage_deduction" id="damage_deduction" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Shortage Deduction</label><input type="number" step="0.01" name="shortage_deduction" id="shortage_deduction" class="balance-calc mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Final Balance to Pay</label><input type="number" step="0.01" id="balance_amount" name="balance_amount" value="<?php echo $balance_amount; ?>" class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100" readonly></div>
                            <div><label class="block text-sm font-medium">Payment Date</label><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                            <div><label class="block text-sm font-medium">Payment Mode</label><select name="payment_mode" class="mt-1 block w-full px-3 py-2 border rounded-md bg-white"><option>Bank Transfer</option><option>Cash</option><option>Cheque</option></select></div>
                            <div><label class="block text-sm font-medium">Transaction Ref / Remarks</label><input type="text" name="remarks" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                        </div>
                        <div class="mt-6 flex justify-end gap-4">
                            <a href="vehicle_settlements.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">Cancel</a>
                            <button type="submit" class="py-2 px-6 border rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">Mark as Paid</button>
                        </div>
                    </form>
                </div>
                <?php elseif ($view_details_mode): ?>
                <div class="bg-white p-8 rounded-lg shadow-md print-area" id="settlement-details">
                    <header class="grid grid-cols-2 gap-4 pb-4 border-b-2 border-black mb-6">
                        <div>
                            <?php if(!empty($company_details['logo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($company_details['logo_path']); ?>" alt="Company Logo" class="h-20">
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <h1 class="text-2xl font-bold text-red-600 mt-2"><?php echo htmlspecialchars($company_details['name'] ?? ''); ?></h1>
                            <p class="text-xs"><?php echo htmlspecialchars($company_details['address'] ?? ''); ?></p>
                        </div>
                    </header>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Settlement Details</h2>
                        <div class="no-print flex items-center gap-2">
                             <button onclick="window.print()" class="text-blue-600 hover:text-blue-900 p-2 rounded-md bg-gray-100" title="Print Details"><i class="fas fa-print"></i></button>
                             <button id="download-pdf-btn" class="text-green-600 hover:text-green-900 p-2 rounded-md bg-gray-100" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                             <a href="vehicle_settlements.php" class="py-2 px-4 border rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50 ml-4"><i class="fas fa-arrow-left mr-2"></i> Back to Lists</a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                         <div><strong class="block text-gray-500">LR No:</strong> <?php echo htmlspecialchars($shipment_data['consignment_no']); ?></div>
                         <div><strong class="block text-gray-500">Vehicle No:</strong> <?php echo htmlspecialchars($shipment_data['vehicle_number']); ?></div>
                         <div><strong class="block text-gray-500">Broker/Owner:</strong> <?php echo htmlspecialchars($shipment_data['broker_name'] ?? 'N/A'); ?></div>
                         <div><strong class="block text-gray-500">POD Document:</strong> <a href="<?php echo htmlspecialchars($shipment_data['pod_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View POD</a></div>
                         <div class="md:col-span-4"><strong class="block text-gray-500">POD Remarks:</strong> <?php echo htmlspecialchars($shipment_data['pod_remarks'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="border-t mt-6 pt-6">
                        <h3 class="font-bold mb-2">Payment Breakdown</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><strong class="block text-gray-500">Lorry Hire:</strong> <?php echo number_format($payment_summary['Lorry Hire']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Advance Cash:</strong> -<?php echo number_format($payment_summary['Advance Cash']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Advance Diesel:</strong> -<?php echo number_format($payment_summary['Advance Diesel']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Labour Charge:</strong> -<?php echo number_format($payment_summary['Labour Charge']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Dala Charge:</strong> -<?php echo number_format($payment_summary['Dala Charge']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Damage Deduction:</strong> -<?php echo number_format($payment_summary['Damage Deduction']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Shortage Deduction:</strong> -<?php echo number_format($payment_summary['Shortage Deduction']['amount'] ?? 0, 2); ?></div>
                            <div class="font-bold text-lg"><strong class="block text-gray-500">Final Amount Paid:</strong> ₹<?php echo number_format($payment_summary['Balance Payment']['amount'] ?? 0, 2); ?></div>
                            <div><strong class="block text-gray-500">Payment Date:</strong> <?php echo date("d M, Y", strtotime($payment_summary['Balance Payment']['payment_date'] ?? '')); ?></div>
                            <div class="md:col-span-2"><strong class="block text-gray-500">Reference/Remarks:</strong> <?php echo htmlspecialchars($payment_summary['Balance Payment']['remarks'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Pending Vehicle Settlements</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Vehicle No.</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_settlements as $shipment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['vehicle_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><a href="vehicle_settlements.php?action=settle&id=<?php echo $shipment['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Settle Payment</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($pending_settlements)): ?><tr><td colspan="3" class="text-center py-4">No payments are pending settlement.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <?php for ($i = 1; $i <= $total_pages_pending; $i++): ?>
                                <a href="?page_pending=<?php echo $i; ?>" class="px-3 py-1 mx-1 text-sm font-medium <?php echo $i == $page_pending ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Settled Payments</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">LR No.</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Vehicle No.</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($settled_payments as $shipment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['vehicle_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><a href="vehicle_settlements.php?action=view_details&id=<?php echo $shipment['id']; ?>" class="text-gray-600 hover:text-indigo-900">View Details</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($settled_payments)): ?><tr><td colspan="3" class="text-center py-4">No payments have been settled yet.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-4 flex justify-end">
                            <?php for ($i = 1; $i <= $total_pages_settled; $i++): ?>
                                <a href="?page_settled=<?php echo $i; ?>" class="px-3 py-1 mx-1 text-sm font-medium <?php echo $i == $page_settled ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('balance_amount')) {
                const initialBalance = parseFloat(document.getElementById('balance_amount').value) || 0;
                const balanceInputs = document.querySelectorAll('.balance-calc');
                const balanceAmountField = document.getElementById('balance_amount');

                function calculateBalance() {
                    let totalDeductions = 0;
                    balanceInputs.forEach(input => {
                        totalDeductions += parseFloat(input.value) || 0;
                    });
                    const finalBalance = initialBalance - totalDeductions;
                    balanceAmountField.value = finalBalance.toFixed(2);
                }

                balanceInputs.forEach(input => {
                    input.addEventListener('keyup', calculateBalance);
                });
            }

            if (document.getElementById('download-pdf-btn')) {
                document.getElementById('download-pdf-btn').addEventListener('click', function () {
                    const element = document.getElementById('settlement-details');
                    const lrNumber = "<?php echo htmlspecialchars($shipment_data['consignment_no'] ?? 'settlement'); ?>";
                    
                    html2canvas(element, { scale: 2 }).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const { jsPDF } = window.jspdf;
                        
                        const pdf = new jsPDF('p', 'mm', 'a4');
                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = pdf.internal.pageSize.getHeight();
                        
                        const canvasWidth = canvas.width;
                        const canvasHeight = canvas.height;
                        const canvasAspectRatio = canvasWidth / canvasHeight;
                        
                        let imgWidth = pdfWidth - 20;
                        let imgHeight = imgWidth / canvasAspectRatio;

                        if (imgHeight > pdfHeight - 20) {
                            imgHeight = pdfHeight - 20;
                            imgWidth = imgHeight * canvasAspectRatio;
                        }
                        
                        const x = (pdfWidth - imgWidth) / 2;
                        const y = 10;

                        pdf.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);
                        pdf.save(`Settlement-${lrNumber}.pdf`);
                    });
                });
            }
        });
    </script>
</body>
</html>
