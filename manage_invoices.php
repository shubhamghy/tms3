<?php
session_start();
require_once "config.php";

// Authenticate user
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Authorize user
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$current_branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0; // Get branch_id
if (!in_array($user_role, ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

// --- Branch Filtering Logic ---
$branch_sql_filter_parties = "";
$branch_sql_filter_shipments = "";
$branch_param_type = "";
$branch_param_value = null;

if ($user_role !== 'admin' && $current_branch_id > 0) {
    $branch_sql_filter_parties = " AND p.branch_id = ?"; 
    $branch_sql_filter_shipments = " AND s.branch_id = ?";
    $branch_param_type = "i"; 
    $branch_param_value = $current_branch_id;
}
// --- END Branch Filtering ---


$parties = [];
$shipments = [];
$form_message = "";
$suggested_invoice_no = ""; // Will be generated later

// Default to 'Consignor', but check POST/GET for persistence
$billing_party_type = (isset($_REQUEST['billing_party_type']) && $_REQUEST['billing_party_type'] == 'Consignee') ? 'Consignee' : 'Consignor';
$selected_party_id = isset($_REQUEST['party_id']) ? intval($_REQUEST['party_id']) : null;
$from_date = isset($_REQUEST['from_date']) ? $_REQUEST['from_date'] : date('Y-m-01');
$to_date = isset($_REQUEST['to_date']) ? $_REQUEST['to_date'] : date('Y-m-t');


// --- Fetch Parties based on selected type ---
$sql = "SELECT p.id, p.name FROM parties p WHERE p.party_type = ? AND p.is_active = 1 $branch_sql_filter_parties ORDER BY p.name";
if ($stmt_parties = $mysqli->prepare($sql)) {
    if ($branch_param_value !== null) {
        $stmt_parties->bind_param("s" . $branch_param_type, $billing_party_type, $branch_param_value);
    } else {
        $stmt_parties->bind_param("s", $billing_party_type);
    }
    $stmt_parties->execute();
    $result = $stmt_parties->get_result();
    while ($row = $result->fetch_assoc()) {
        $parties[] = $row;
    }
    $stmt_parties->close();
}
// --- END Fetch Parties ---


// --- Handle form submission for fetching shipments ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['party_id']) && !isset($_POST['create_invoice'])) {
    
    // Dynamic column for party ID
    $party_column = ($billing_party_type == 'Consignee') ? 's.consignee_id' : 's.consignor_id';

    $sql = "SELECT s.id, s.consignment_no, s.consignment_date, s.origin, s.destination, 
                   p_consignor.name as consignor_name, p_consignee.name as consignee_name, 
                   sp.amount as billing_amount
            FROM shipments s
            JOIN parties p_consignor ON s.consignor_id = p_consignor.id
            JOIN parties p_consignee ON s.consignee_id = p_consignee.id
            LEFT JOIN shipment_payments sp ON s.id = sp.shipment_id AND sp.payment_type = 'Billing Rate'
            WHERE $party_column = ? 
            AND s.consignment_date BETWEEN ? AND ?
            AND s.status IN ('Delivered', 'Completed') 
            AND s.id NOT IN (SELECT shipment_id FROM invoice_items)
            $branch_sql_filter_shipments 
            ORDER BY s.consignment_date ASC";

    if ($stmt = $mysqli->prepare($sql)) {
        if ($branch_param_value !== null) {
            $stmt->bind_param("iss" . $branch_param_type, $selected_party_id, $from_date, $to_date, $branch_param_value);
        } else {
            $stmt->bind_param("iss", $selected_party_id, $from_date, $to_date);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $shipments[] = $row;
        }
        if (empty($shipments)) {
            $form_message = '<div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50">No unbilled shipments found for the selected criteria.</div>';
        }
        $stmt->close();
    } else {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error preparing shipment query: ' . $mysqli->error . '</div>';
    }
}
// --- END Fetch Shipments ---


// --- Handle invoice creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_invoice'])) {
    $party_id = intval($_POST['selected_party_id']); 
    $invoice_date = $_POST['invoice_date'];
    $invoice_no = trim($_POST['invoice_no']); 
    $from_date = $_POST['selected_from_date'];
    $to_date = $_POST['selected_to_date'];
    $shipment_ids = isset($_POST['shipment_ids']) ? $_POST['shipment_ids'] : [];
    $created_by_id = $_SESSION['id'];
    $total_amount = 0;

    // Repopulate form fields on error
    $selected_party_id = $party_id;
    $billing_party_type = (isset($_POST['selected_billing_party_type']) && $_POST['selected_billing_party_type'] == 'Consignee') ? 'Consignee' : 'Consignor';

    if (empty($party_id) || empty($shipment_ids) || empty($invoice_no)) {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: Party, Invoice Number, or shipment list is empty.</div>';
    } else {
        // Calculate total amount
        $total_sql = "SELECT SUM(amount) as total FROM shipment_payments WHERE payment_type = 'Billing Rate' AND shipment_id IN (" . implode(',', array_fill(0, count($shipment_ids), '?')) . ")";
        $stmt_total = $mysqli->prepare($total_sql);
        $types = str_repeat('i', count($shipment_ids));
        $stmt_total->bind_param($types, ...$shipment_ids);
        $stmt_total->execute();
        $total_amount = (float)$stmt_total->get_result()->fetch_assoc()['total'];
        $stmt_total->close();

        // Start transaction
        $mysqli->begin_transaction();
        try {
            
            // --- MODIFIED: Insert invoice WITH branch_id ---
            $insert_sql = "INSERT INTO invoices (invoice_no, invoice_date, from_date, to_date, consignor_id, total_amount, created_by_id, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_sql);
            // The `consignor_id` column is used to store the $party_id (which can be consignor or consignee)
            // The `branch_id` (from the session) is now added
            $insert_stmt->bind_param("ssssidii", $invoice_no, $invoice_date, $from_date, $to_date, $party_id, $total_amount, $created_by_id, $current_branch_id);
            
            if (!$insert_stmt->execute()) {
                if ($mysqli->errno == 1062) { // Check for duplicate entry
                    throw new Exception("Error: Invoice Number '$invoice_no' already exists.");
                }
                throw new Exception("Error creating invoice: " . $insert_stmt->error);
            }
            $invoice_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            // Insert invoice items
            $item_sql = "INSERT INTO invoice_items (invoice_id, shipment_id) VALUES (?, ?)";
            $item_stmt = $mysqli->prepare($item_sql);
            foreach ($shipment_ids as $shipment_id) {
                $item_stmt->bind_param("ii", $invoice_id, $shipment_id);
                if (!$item_stmt->execute()) {
                    throw new Exception("Error adding shipment to invoice: " . $item_stmt->error);
                }
            }
            $item_stmt->close();

            $mysqli->commit();
            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Invoice <a href="view_invoice_details.php?id='.$invoice_id.'" class="font-bold underline">'.htmlspecialchars($invoice_no).'</a> created successfully!</div>';
            // Clear selections
            $selected_party_id = null;
            $shipments = [];

        } catch (Exception $e) {
            $mysqli->rollback();
            $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
// --- END Handle invoice creation ---


// --- Auto-generate suggested Invoice Number if shipments are found ---
if (!empty($shipments)) {
    // Generate Invoice Number (Example: STC/GHY/2526/001)
    $branch_code = 'INV'; // Default
    if ($current_branch_id > 0) {
        $branch_code_sql = "SELECT name FROM branches WHERE id = ?";
        $stmt_branch = $mysqli->prepare($branch_code_sql);
        $stmt_branch->bind_param("i", $current_branch_id);
        $stmt_branch->execute();
        $result = $stmt_branch->get_result();
        if ($result->num_rows > 0) {
            $branch_name = $result->fetch_assoc()['name'];
            $branch_code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branch_name), 0, 3));
        }
        $stmt_branch->close();
    }

    $year = date('y', strtotime($from_date)); // Use 'from_date' for financial year
    $next_year = $year + 1;
    $fin_year = $year . $next_year;
    
    $invoice_prefix = "STC/$branch_code/$fin_year/";

    // --- MODIFIED: Count based on prefix AND branch_id (now that it exists) ---
    $count_sql = "SELECT COUNT(*) as inv_count FROM invoices WHERE invoice_no LIKE ? AND branch_id = ?";
    $like_param = $invoice_prefix . "%";
    $stmt_count = $mysqli->prepare($count_sql);
    $stmt_count->bind_param("si", $like_param, $current_branch_id);
    $stmt_count->execute();
    $inv_count = $stmt_count->get_result()->fetch_assoc()['inv_count'] + 1;
    $stmt_count->close();
    
    $suggested_invoice_no = $invoice_prefix . str_pad($inv_count, 4, '0', STR_PAD_LEFT);
}
// --- END ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Generate Invoice - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        
        /* --- Styles to make Tom Select match Tailwind --- */
        
        /* --- THIS IS THE FIX: Apply margin-top to the wrapper --- */
        .ts-wrapper {
            margin-top: 0.25rem; /* This is tailwind 'mt-1' */
        }

        .ts-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem; /* py-2 px-3 */
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #111827; /* gray-900 */
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #D1D5DB; /* gray-300 */
            -webkit-appearance: none;
               -moz-appearance: none;
                    appearance: none;
            border-radius: 0.375rem; /* rounded-md */
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); /* shadow-sm */
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .ts-control .ts-input {
            padding: 0;
            font-size: inherit;
            line-height: inherit;
            color: #111827;
        }
        .ts-control.focus {
            border-color: #4F46E5; /* indigo-500 */
            outline: 0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05), 0 0 0 1px #4F46E5; /* shadow-sm + ring-indigo-500 */
        }
        .ts-dropdown {
             border: 1px solid #D1D5DB; /* gray-300 */
             border-radius: 0.375rem;
             box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); /* shadow-sm */
        }
        .ts-dropdown .option {
            padding: 0.5rem 0.75rem; /* py-2 px-3 */
        }
        .ts-dropdown .option.active {
            background-color: #4F46E5; /* indigo-500 */
            color: #fff;
        }
        /* --- End Tom Select Styles --- */
    </style>
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
                        <h1 class="text-xl font-semibold text-gray-800">Generate Invoice</h1>
                        <div class="flex items-center pr-4">
                             <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                            <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Step 1: Select Party and Date Range</h2>
                    <form method="POST" id="select-party-form">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label for="billing_party_type" class="block text-sm font-medium text-gray-700">Bill To Type</label>
                                <select id="billing_party_type" name="billing_party_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" onchange="document.getElementById('select-party-form').submit()">
                                    <option value="Consignor" <?php echo ($billing_party_type == 'Consignor') ? 'selected' : ''; ?>>Consignor</option>
                                    <option value="Consignee" <?php echo ($billing_party_type == 'Consignee') ? 'selected' : ''; ?>>Consignee</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="party_id" class="block text-sm font-medium text-gray-700">Party</label>
                                <select id="party_id" name="party_id" required>
                                    <option value="">-- Select Party --</option>
                                    <?php foreach ($parties as $party): ?>
                                        <option value="<?php echo $party['id']; ?>" <?php echo ($selected_party_id == $party['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($party['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                                <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>
                        </div>
                        <div class="text-right mt-6">
                            <button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-search mr-2"></i>Fetch Unbilled Shipments
                            </button>
                        </div>
                    </form>
                </div>

                <?php if (!empty($shipments)): ?>
                <form method="POST">
                    <input type="hidden" name="create_invoice" value="1">
                    <input type="hidden" name="selected_party_id" value="<?php echo htmlspecialchars($selected_party_id); ?>">
                    <input type="hidden" name="selected_billing_party_type" value="<?php echo htmlspecialchars($billing_party_type); ?>">
                    <input type="hidden" name="selected_from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    <input type="hidden" name="selected_to_date" value="<?php echo htmlspecialchars($to_date); ?>">

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Step 2: Select Shipments to Invoice</h2>
                        
                        <div class="md:flex md:justify-between md:items-start mb-4 space-y-4 md:space-y-0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1 md:mr-6">
                                <div>
                                    <label for="invoice_no" class="block text-sm font-medium text-gray-700">Invoice No. <span class="text-red-500">*</span></label>
                                    <input type="text" id="invoice_no" name="invoice_no" value="<?php echo htmlspecialchars($suggested_invoice_no); ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label for="invoice_date" class="block text-sm font-medium text-gray-700">Invoice Date <span class="text-red-500">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 md:mt-6">
                                <i class="fas fa-file-invoice mr-2"></i>Create Invoice
                            </button>
                        </div>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LR No.</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consignor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consignee</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php $total_payable = 0; ?>
                                    <?php foreach ($shipments as $shipment): ?>
                                        <?php $total_payable += (float)$shipment['billing_amount']; ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input type="checkbox" name="shipment_ids[]" value="<?php echo $shipment['id']; ?>" class="shipment-check rounded border-gray-300 text-indigo-600">
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($shipment['consignment_no']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($shipment['consignment_date'])); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['origin'] . ' to ' . $shipment['destination']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['consignor_name']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($shipment['consignee_name']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium"><?php echo number_format($shipment['billing_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-gray-100">
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-right text-sm font-bold text-gray-700">Total Selected:</td>
                                        <td id="selected-total" class="px-4 py-3 text-right text-sm font-bold text-gray-900">0.00</td>
                                    </tr>
                                
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- TOM SELECT INITIALIZATION ---
        new TomSelect("#party_id", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        // --- END: TOM SELECT ---


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

        // --- Checkbox logic ---
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.shipment-check');
        const selectedTotalEl = document.getElementById('selected-total');

        function calculateTotal() {
            let total = 0;
            checkboxes.forEach(box => {
                if (box.checked) {
                    const row = box.closest('tr');
                    const amountCell = row.cells[row.cells.length - 1];
                    total += parseFloat(amountCell.textContent.replace(/,/g, '')) || 0;
                }
            });
            if (selectedTotalEl) {
                selectedTotalEl.textContent = total.toFixed(2);
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes.forEach(box => {
                    box.checked = e.target.checked;
                });
                calculateTotal();
            });
        }

        checkboxes.forEach(box => {
            box.addEventListener('change', () => {
                if (selectAll) {
                    if (!box.checked) {
                        selectAll.checked = false;
                    } else if (Array.from(checkboxes).every(b => b.checked)) {
                        selectAll.checked = true;
                    }
                }
                calculateTotal();
            });
        });

        // Initial calculation in case of page reload with selections
        calculateTotal();
    });

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