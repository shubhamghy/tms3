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

// Get the user's branch ID from the session
$user_branch_id = intval($_SESSION['branch_id'] ?? 0);
$message = "";

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $is_admin) {
    $invoice_id_to_delete = intval($_GET['id']);
    // You should add a branch check here too if non-admins can delete, but for now it's admin-only
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoice_id_to_delete);
        $stmt->execute();
        $stmt->close();
        $mysqli->commit();
        $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Invoice deleted successfully.</div>";
    } catch (Exception $e) {
        $mysqli->rollback();
        $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error deleting invoice: " . $e->getMessage() . "</div>";
    }
}


// --- Build query string for pagination to preserve any filters ---
$query_params = $_GET;
unset($query_params['page']); // Remove page from params to avoid duplication
$query_string = http_build_query($query_params);


// --- Pagination Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// --- FIX 1: Get total number of invoices (filtered by branch) ---
$sql_count = "SELECT COUNT(id) FROM invoices";
if (!$is_admin && $user_branch_id > 0) {
    $sql_count .= " WHERE branch_id = ?";
    $stmt_count = $mysqli->prepare($sql_count);
    $stmt_count->bind_param("i", $user_branch_id);
} else {
    $stmt_count = $mysqli->prepare($sql_count);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();
$total_pages = ceil($total_records / $records_per_page);

// --- FIX 2: Fetch invoices (filtered by branch) ---
$invoices_list = [];
$sql = "SELECT i.id, i.invoice_no, i.invoice_date, i.total_amount, i.status, p.name as consignor_name,
               (SELECT SUM(amount_received) FROM invoice_payments ip WHERE ip.invoice_id = i.id) as paid_amount
        FROM invoices i 
        JOIN parties p ON i.consignor_id = p.id";

$params = [];
$param_types = "";

if (!$is_admin && $user_branch_id > 0) {
    $sql .= " WHERE i.branch_id = ? "; // Note the 'i.' alias
    $params[] = $user_branch_id;
    $param_types .= "i";
}

$sql .= " ORDER BY i.invoice_date DESC, i.id DESC 
          LIMIT ?, ?";

$params[] = $offset;
$params[] = $records_per_page;
$param_types .= "ii";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $invoices_list[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Invoices - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        #printModal { z-index: 100; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="loader" class="fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
    <div class="fas fa-spinner fa-spin fa-3x text-indigo-600"></div>
</div>
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <div class="flex items-center justify-between h-16 bg-white border-b border-gray-200">
                <div class="flex items-center px-4"><button class="text-gray-500 md:hidden"><i class="fas fa-bars"></i></button></div>
                <div class="flex items-center pr-4">
                     <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
            <div class="p-4 md:p-8">
                <?php if(!empty($message)) echo $message; ?>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="flex flex-wrap items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Generated Invoices</h2>
                        <a href="manage_invoices.php" class="inline-flex items-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Generate New Invoice</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Invoice No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Consignor</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase">Total Amt</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase">Balance Due</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($invoices_list as $invoice): ?>
                                    <?php
                                        $paid_amount = $invoice['paid_amount'] ?? 0;
                                        $balance_due = $invoice['total_amount'] - $paid_amount;
                                        $status = $invoice['status'];
                                        $status_color = 'bg-gray-200 text-gray-800'; // Default
                                        if ($status === 'Paid') {
                                            $status_color = 'bg-green-100 text-green-800';
                                        } elseif ($status === 'Partially Paid') {
                                            $status_color = 'bg-yellow-100 text-yellow-800';
                                        } elseif ($balance_due > 0 && $status !== 'Partially Paid') {
                                            $status = 'Unpaid';
                                            $status_color = 'bg-red-100 text-red-800';
                                        }
                                    ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view_invoice_details.php?id=<?php echo $invoice['id']; ?>" class="text-indigo-600 hover:underline">
                                            <?php echo htmlspecialchars($invoice['invoice_no']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("d-m-Y", strtotime($invoice['invoice_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($invoice['consignor_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">&#8377;<?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 text-right">&#8377;<?php echo htmlspecialchars(number_format($balance_due, 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        
                                        <button onclick="showPrintModal(<?php echo $invoice['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-4" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </button>

                                        <?php if ($is_admin): ?>
                                        <a href="view_invoices.php?action=delete&id=<?php echo $invoice['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to permanently delete this invoice?');" title="Delete"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($invoices_list)): ?>
                                    <tr><td colspan="7" class="text-center py-4">No invoices found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results
                        </span>
                        <div class="flex">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&amp;<?php echo $query_string; ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&amp;<?php echo $query_string; ?>" class="px-4 py-2 mx-1 text-sm font-medium <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'; ?> border rounded-md hover:bg-gray-100"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&amp;<?php echo $query_string; ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="printModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden">
                <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Select Print Orientation</h3>
                    <p class="mb-6">Please choose an orientation for your PDF invoice.</p>
                    
                    <div class="flex justify-around gap-4">
                        <a id="printPortraitLink" href="#" target="_blank" onclick="hidePrintModal()" class="flex-1 text-center py-3 px-6 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-file-pdf mr-2"></i> Portrait
                        </a>
                        <a id="printLandscapeLink" href="#" target="_blank" onclick="hidePrintModal()" class="flex-1 text-center py-3 px-6 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700">
                            <i class="fas fa-file-alt mr-2"></i> Landscape
                        </a>
                    </div>

                    <button onclick="hidePrintModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
            </div>


            <?php include 'footer.php'; ?>
        </div> 
    </div>

    <script>
    window.onload = function() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    };

    const modal = document.getElementById('printModal');
    const portraitLink = document.getElementById('printPortraitLink');
    const landscapeLink = document.getElementById('printLandscapeLink');

    function showPrintModal(invoiceId) {
        portraitLink.href = `print_invoice.php?id=${invoiceId}&orientation=portrait`;
        landscapeLink.href = `print_invoice.php?id=${invoiceId}&orientation=landscape`;
        
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function hidePrintModal() {
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hidePrintModal();
            }
        });
    }
    
</script>
</body>
</html>