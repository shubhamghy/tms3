<?php
session_start();
require_once "config.php";

// Access Control: Admin and Manager only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("location: dashboard.php");
    exit;
}

// --- Page State & Data ---
$form_message = "";
$edit_mode = false;
$add_mode = false;
$expense_data = ['id' => '', 'expense_date' => date('Y-m-d'), 'category' => '', 'amount' => '', 'paid_to' => '', 'shipment_id' => null, 'vehicle_id' => null, 'employee_id' => null, 'description' => ''];
$expense_categories = ['Fuel', 'Maintenance', 'Toll', 'Salary', 'Office Rent', 'Utilities', 'Repair', 'Other'];

// --- Form Submission (Add/Edit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $expense_date = $_POST['expense_date'];
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $paid_to = trim($_POST['paid_to']);
    $shipment_id = !empty($_POST['shipment_id']) ? intval($_POST['shipment_id']) : null;
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    // ✅ ADDED: Get employee_id from the form
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
    $description = trim($_POST['description']);
    $branch_id = $_SESSION['branch_id'];
    $created_by = $_SESSION['id'];

    if ($id > 0) { // Update
        $sql = "UPDATE expenses SET expense_date=?, category=?, amount=?, paid_to=?, shipment_id=?, vehicle_id=?, employee_id=?, description=? WHERE id=? AND branch_id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssdsiiisii", $expense_date, $category, $amount, $paid_to, $shipment_id, $vehicle_id, $employee_id, $description, $id, $branch_id);
    } else { // Insert
        $sql = "INSERT INTO expenses (expense_date, category, amount, paid_to, shipment_id, vehicle_id, employee_id, description, branch_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssdsiiisii", $expense_date, $category, $amount, $paid_to, $shipment_id, $vehicle_id, $employee_id, $description, $branch_id, $created_by);
    }

    if ($stmt->execute()) {
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Expense saved successfully!</div>';
    } else {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error saving expense: '. $stmt->error .'</div>';
    }
    $stmt->close();
}

// --- Handle GET Actions (Edit, Delete) ---
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] == 'add') { $add_mode = true; }
    elseif ($_GET['action'] == 'edit' && $id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $expense_data = $result->fetch_assoc();
            $edit_mode = true;
        }
        $stmt->close();
    } elseif ($_GET['action'] == 'delete' && $id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $form_message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Expense deleted successfully.</div>";
        } else {
            $form_message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error deleting expense.</div>";
        }
        $stmt->close();
    }
}

// --- Data Fetching for Lists/Dropdowns ---
$expenses_list = [];
$shipments = [];
$vehicles = [];
$employees = [];

// ✅ ADDED: Fetch employees for the dropdown
$branch_filter_clause = ($_SESSION['role'] !== 'admin') ? " WHERE branch_id = " . intval($_SESSION['branch_id']) : "";
$employees = $mysqli->query("SELECT id, full_name, employee_code FROM employees{$branch_filter_clause} ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

if ($add_mode || $edit_mode) {
    // Fetch active shipments and vehicles for dropdowns
    $shipments = $mysqli->query("SELECT id, consignment_no FROM shipments WHERE status != 'Completed' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    $vehicles = $mysqli->query("SELECT id, vehicle_number FROM vehicles WHERE is_active = 1 ORDER BY vehicle_number ASC")->fetch_all(MYSQLI_ASSOC);
} else {
    // Fetch expenses list with pagination and search
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 9;
    $offset = ($page - 1) * $records_per_page;
    
    $search_term = trim($_GET['search'] ?? '');
    $where_sql = " WHERE e.branch_id = ?";
    $params = [$_SESSION['branch_id']];
    $types = "i";
    
    if (!empty($search_term)) {
        $like_term = "%{$search_term}%";
        $where_sql .= " AND (e.category LIKE ? OR e.paid_to LIKE ? OR s.consignment_no LIKE ? OR v.vehicle_number LIKE ?)";
        array_push($params, $like_term, $like_term, $like_term, $like_term);
        $types .= "ssss";
    }

    $count_sql = "SELECT COUNT(e.id) FROM expenses e LEFT JOIN shipments s ON e.shipment_id = s.id LEFT JOIN vehicles v ON e.vehicle_id = v.id" . $where_sql;
    $stmt_count = $mysqli->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_records / $records_per_page);

    $list_sql = "SELECT e.*, s.consignment_no, v.vehicle_number 
                 FROM expenses e 
                 LEFT JOIN shipments s ON e.shipment_id = s.id 
                 LEFT JOIN vehicles v ON e.vehicle_id = v.id" . $where_sql . " 
                 ORDER BY e.expense_date DESC, e.id DESC LIMIT ? OFFSET ?";
    
    $params[] = $records_per_page;
    $types .= "i";
    $params[] = $offset;
    $types .= "i";
    
    $stmt_list = $mysqli->prepare($list_sql);
    $bind_params = [];
    $bind_params[] = $types;
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt_list, 'bind_param'], $bind_params);
    
    $stmt_list->execute();
    $expenses_list = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses - TMS</title>
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
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="page-loader" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 relative">
             <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>
             <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                         <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                        <h1 class="text-xl font-semibold text-gray-800">Manage Expenses</h1>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 [--webkit-overflow-scrolling:touch]" x-data="{ expenseCategory: '<?php echo htmlspecialchars($expense_data['category'] ?: ''); ?>' }">
                <?php if(!empty($form_message)) echo $form_message; ?>

                <?php if ($add_mode || $edit_mode): ?>
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $edit_mode ? 'Edit Expense' : 'Add New Expense'; ?></h2>
                        <a href="manage_expenses.php" class="py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</a>
                    </div>
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="id" value="<?php echo $expense_data['id']; ?>">
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Expense Details</legend>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                                <div><label class="block text-sm font-medium">Expense Date <span class="text-red-500">*</span></label><input type="date" name="expense_date" value="<?php echo htmlspecialchars($expense_data['expense_date']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                                <div>
                                    <label class="block text-sm font-medium">Category <span class="text-red-500">*</span></label>
                                    <select name="category" x-model="expenseCategory" class="mt-1 block w-full px-3 py-2 border rounded-md bg-white" required>
                                        <?php foreach($expense_categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div><label class="block text-sm font-medium">Amount <span class="text-red-500">*</span></label><input type="number" step="0.01" name="amount" value="<?php echo htmlspecialchars($expense_data['amount']); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md" required></div>
                                
                                <div class="md:col-span-3" x-show="expenseCategory !== 'Salary'" x-cloak>
                                    <label class="block text-sm font-medium">Paid To</label>
                                    <input type="text" name="paid_to" value="<?php echo htmlspecialchars($expense_data['paid_to']); ?>" placeholder="e.g., Indian Oil, NHAI Toll Plaza" class="mt-1 block w-full px-3 py-2 border rounded-md">
                                </div>
                                
                                <div class="md:col-span-3" x-show="expenseCategory === 'Salary'" x-cloak>
                                    <label class="block text-sm font-medium">Select Employee*</label>
                                    <select name="employee_id" class="searchable-select mt-1 block w-full">
                                        <option value="">Select an Employee</option>
                                        <?php foreach($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php if($expense_data['employee_id'] == $emp['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($emp['full_name'] . ($emp['employee_code'] ? ' (' . $emp['employee_code'] . ')' : '')); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="border p-4 rounded-lg"><legend class="text-lg font-semibold px-2">Association (Optional)</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                <div><label class="block text-sm font-medium">Link to Shipment (LR No.)</label><select name="shipment_id" class="searchable-select mt-1 block w-full"><option value="">None</option><?php foreach($shipments as $s): ?><option value="<?php echo $s['id']; ?>" <?php if($expense_data['shipment_id'] == $s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['consignment_no']); ?></option><?php endforeach; ?></select></div>
                                <div><label class="block text-sm font-medium">Link to Vehicle</label><select name="vehicle_id" class="searchable-select mt-1 block w-full"><option value="">None</option><?php foreach($vehicles as $v): ?><option value="<?php echo $v['id']; ?>" <?php if($expense_data['vehicle_id'] == $v['id']) echo 'selected'; ?>><?php echo htmlspecialchars($v['vehicle_number']); ?></option><?php endforeach; ?></select></div>
                                <div class="md:col-span-2"><label class="block text-sm font-medium">Description / Remarks</label><textarea name="description" rows="3" class="mt-1 block w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($expense_data['description']); ?></textarea></div>
                            </div>
                        </fieldset>
                        <div class="mt-6 flex justify-end"><button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><?php echo $edit_mode ? 'Update Expense' : 'Save Expense'; ?></button></div>
                    </form>
                </div>

                <?php else: ?>
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">Expenses</h2>
                        <a href="manage_expenses.php?action=add" class="inline-flex items-center justify-center sm:w-auto w-full py-2 px-4 border rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Expense</a>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <form method="GET" action="manage_expenses.php">
                            <div class="flex items-center space-x-2">
                                <input type="text" name="search" placeholder="Search category, paid to, LR no..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                                <button type="submit" class="py-2 px-4 border rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-search"></i></button>
                                <a href="manage_expenses.php" class="py-2 px-4 border rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>
                            </div>
                        </form>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php if (empty($expenses_list)): ?>
                            <div class="md:col-span-2 xl:col-span-3 text-center py-10"><i class="fas fa-dollar-sign fa-3x text-gray-300"></i><p class="mt-4 text-gray-500">No expenses found. Click 'Add New Expense' to get started.</p></div>
                        <?php else: ?>
                            <?php foreach ($expenses_list as $expense): ?>
                            <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800"><?php echo htmlspecialchars($expense['category']); ?></p>
                                            <h3 class="font-bold text-2xl text-gray-800 mt-2">₹<?php echo number_format($expense['amount'], 2); ?></h3>
                                        </div>
                                        <p class="text-sm text-gray-500"><?php echo date("d M, Y", strtotime($expense['expense_date'])); ?></p>
                                    </div>
                                    <div class="mt-4 border-t pt-4 text-sm text-gray-600 space-y-2">
                                        <p><strong class="font-medium text-gray-900">Paid To:</strong> <?php echo htmlspecialchars($expense['paid_to'] ?: 'N/A'); ?></p>
                                        <?php if($expense['consignment_no']): ?><p><strong class="font-medium">LR No:</strong> <?php echo htmlspecialchars($expense['consignment_no']); ?></p><?php endif; ?>
                                        <?php if($expense['vehicle_number']): ?><p><strong class="font-medium">Vehicle:</strong> <?php echo htmlspecialchars($expense['vehicle_number']); ?></p><?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t flex justify-end space-x-3 text-sm font-medium">
                                    <a href="manage_expenses.php?action=edit&id=<?php echo $expense['id']; ?>" class="text-green-600 hover:text-green-800">Edit</a>
                                    <a href="manage_expenses.php?action=delete&id=<?php echo $expense['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-6 flex justify-between items-center">
                        <span class="text-sm text-gray-700">Showing <?php echo $total_records > 0 ? ($offset + 1) : 0; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results</span>
                        <div class="flex">
                             <?php 
                                $query_params = [];
                                if (!empty($search_term)) { $query_params['search'] = $search_term; }
                            ?>
                            <?php if ($page > 1): ?>
                                <?php $query_params['page'] = $page - 1; ?>
                                <a href="?<?php echo http_build_query($query_params); ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Previous</a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <?php $query_params['page'] = $page + 1; ?>
                                <a href="?<?php echo http_build_query($query_params); ?>" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php include 'footer.php'; ?>
            </main>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        // --- Sidebar Toggle ---
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
        
        // --- Select2 ---
        $('.searchable-select').select2({ width: '100%' });
    });

    // Page Loader
    window.addEventListener('load', function() {
        document.getElementById('page-loader').style.display = 'none';
    });
    </script>
</body>
</html>
