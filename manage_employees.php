<?php
session_start();
require_once "config.php";

// ===================================================================================
// --- SECTION 1: API LOGIC (Handles background AJAX requests) ---
// ===================================================================================

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        http_response_code(403); echo json_encode(['error' => 'Access Denied']); exit;
    }
    
    $user_role = $_SESSION['role'] ?? null;
    $branch_id = $_SESSION['branch_id'] ?? null;
    $can_manage = in_array($user_role, ['admin', 'manager']);

    if (!$can_manage) {
        http_response_code(403); echo json_encode(['error' => 'Permission Denied']); exit;
    }

    $branch_filter_aliased = "";
    if ($user_role !== 'admin' && !empty($branch_id)) {
        $user_branch_id = intval($branch_id);
        $branch_filter_aliased = " AND e.branch_id = $user_branch_id";
    }

    $action = $_GET['action'];

    if ($action === 'get_employees') {
        $page = $_GET['page'] ?? 1; $search = $_GET['search'] ?? ''; $limit = 9; $offset = ($page - 1) * $limit;
        $where_clauses = ["e.status = 'Active'"]; $params = []; $types = "";

        if (!empty($search)) {
            $where_clauses[] = "(e.full_name LIKE ? OR e.employee_code LIKE ?)";
            $search_term = "%{$search}%";
            array_push($params, $search_term, $search_term);
            $types .= "ss";
        }
        $where_sql = implode(" AND ", $where_clauses);

        $total_sql = "SELECT COUNT(e.id) FROM employees e WHERE $where_sql $branch_filter_aliased";
        $stmt_total = $mysqli->prepare($total_sql);
        if (!empty($search)) { $stmt_total->bind_param($types, ...$params); }
        $stmt_total->execute();
        $total_records = $stmt_total->get_result()->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);
        $stmt_total->close();

        $employees = [];
        $sql = "SELECT e.id, e.full_name, e.employee_code, b.name as branch_name, u.username, e.designation, e.status,
                       (SELECT COUNT(*) FROM salary_structures WHERE employee_id = e.id) as salary_set_count
                FROM employees e 
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN users u ON e.user_id = u.id
                WHERE $where_sql $branch_filter_aliased 
                ORDER BY e.full_name ASC LIMIT ? OFFSET ?";
        
        $types .= "ii";
        array_push($params, $limit, $offset);
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $employees[] = $row; }
        $stmt->close();

        echo json_encode(['employees' => $employees, 'pagination' => ['total_records' => $total_records, 'total_pages' => $total_pages, 'current_page' => (int)$page]]);
        exit;
    }

    if ($action === 'get_details') {
        $employee_id = intval($_GET['id'] ?? 0);
        if ($employee_id === 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit; }
        
        $sql = "SELECT e.*, d.id as driver_id, d.license_number, d.license_expiry_date 
                FROM employees e 
                LEFT JOIN drivers d ON e.id = d.employee_id
                WHERE e.id = ? $branch_filter_aliased";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($employee) { echo json_encode($employee); } 
        else { http_response_code(404); echo json_encode(['error' => 'Employee not found or access denied.']); }
        exit;
    }
    
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $mysqli->begin_transaction();
        try {
            $id = intval($_POST['id'] ?? 0);
            $full_name = trim($_POST['full_name']);
            $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
            $branch_id_form = ($user_role === 'admin') ? intval($_POST['branch_id']) : $branch_id;
            $status = trim($_POST['status']);
            $is_driver = isset($_POST['is_driver']);

            if (empty($full_name)) { throw new Exception('Full Name is required.'); }
            
            if ($id > 0) { // UPDATE
                $sql = "UPDATE employees SET user_id=?, branch_id=?, full_name=?, employee_code=?, designation=?, department=?, date_of_joining=?, pan_no=?, aadhaar_no=?, bank_account_no=?, bank_ifsc_code=?, status=? WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("iissssssssssi", $user_id, $branch_id_form, $full_name, $_POST['employee_code'], $_POST['designation'], $_POST['department'], $_POST['date_of_joining'], $_POST['pan_no'], $_POST['aadhaar_no'], $_POST['bank_account_no'], $_POST['bank_ifsc_code'], $status, $id);
            } else { // INSERT
                $sql = "INSERT INTO employees (user_id, branch_id, full_name, employee_code, designation, department, date_of_joining, pan_no, aadhaar_no, bank_account_no, bank_ifsc_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("iissssssssss", $user_id, $branch_id_form, $full_name, $_POST['employee_code'], $_POST['designation'], $_POST['department'], $_POST['date_of_joining'], $_POST['pan_no'], $_POST['aadhaar_no'], $_POST['bank_account_no'], $_POST['bank_ifsc_code'], $status);
            }

            if (!$stmt->execute()) { throw new Exception("Database error saving employee: " . $stmt->error); }
            $employee_id = ($id > 0) ? $id : $stmt->insert_id;
            $stmt->close();

            $driver_id = intval($_POST['driver_id'] ?? 0);
            if ($is_driver) {
                $license_number = trim($_POST['license_number']);
                $license_expiry = !empty($_POST['license_expiry_date']) ? $_POST['license_expiry_date'] : null;
                if (empty($license_number)) { throw new Exception("License number is required for a driver."); }
                
                if ($driver_id > 0) { // Update existing driver record
                    $driver_sql = "UPDATE drivers SET name=?, license_number=?, license_expiry_date=?, branch_id=? WHERE id=? AND employee_id=?";
                    $driver_stmt = $mysqli->prepare($driver_sql);
                    $driver_stmt->bind_param("sssiii", $full_name, $license_number, $license_expiry, $branch_id_form, $driver_id, $employee_id);
                } else { // Insert new driver record
                    $driver_sql = "INSERT INTO drivers (name, license_number, license_expiry_date, branch_id, employee_id) VALUES (?, ?, ?, ?, ?)";
                    $driver_stmt = $mysqli->prepare($driver_sql);
                    $driver_stmt->bind_param("sssii", $full_name, $license_number, $license_expiry, $branch_id_form, $employee_id);
                }
                if (!$driver_stmt->execute()) { throw new Exception("Database error saving driver profile: " . $driver_stmt->error); }
                $driver_stmt->close();
            } else {
                if ($driver_id > 0) {
                    $mysqli->query("UPDATE drivers SET employee_id = NULL WHERE id = $driver_id");
                }
            }

            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Employee saved successfully!']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// ===================================================================================
// --- SECTION 2: REGULAR PAGE LOAD LOGIC ---
// ===================================================================================

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
$user_role = $_SESSION['role'] ?? '';
$can_manage = in_array($user_role, ['admin', 'manager']);
if (!$can_manage) { header("location: dashboard.php"); exit; }

$branches = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$unassigned_users = $mysqli->query("SELECT u.id, u.username FROM users u LEFT JOIN employees e ON u.id = e.user_id WHERE e.id IS NULL")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style> 
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none; }
        .spinner { border-top-color: #4f46e5; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <header class="bg-white shadow-sm border-b border-gray-200">
                 <div class="mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16"><button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-gray-800">Manage Employees</h1><a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a></div></div>
            </header>
            
            <main class="p-4 md:p-8" x-data="employeesApp()">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                        <div class="relative w-full md:w-1/3"><input type="text" x-model.debounce.500ms="search" placeholder="Search by name or code..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>
                        <button @click="openModal()" class="w-full md:w-auto inline-flex items-center justify-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Employee</button>
                    </div>

                    <div x-show="isLoading" class="text-center py-10"><div class="spinner animate-spin w-10 h-10 border-4 rounded-full mx-auto"></div><p class="mt-2 text-gray-600">Loading employees...</p></div>

                    <div x-show="!isLoading" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">Full Name</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Employee Code</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Branch</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Designation</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="employee in employees" :key="employee.id"><tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><p x-text="employee.full_name"></p><p class="text-xs text-gray-500" x-text="employee.username ? `(@${employee.username})` : ''"></p></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="employee.employee_code"></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="employee.branch_name"></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="employee.designation"></td><td class="px-6 py-4 whitespace-nowrap text-sm"><span :class="{ 'bg-green-100 text-green-800': employee.status === 'Active', 'bg-red-100 text-red-800': employee.status !== 'Active' }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" x-text="employee.status"></span></td><td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4"><button @click="openModal(employee.id)" class="text-indigo-600 hover:text-indigo-900">Edit</button></td></tr></template>
                                <tr x-show="!isLoading && employees.length === 0"><td colspan="6" class="text-center py-10 text-gray-500">No employees found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="!isLoading && totalPages > 1" class="mt-6 flex justify-between items-center"><span class="text-sm text-gray-700">Page <strong x-text="currentPage"></strong> of <strong x-text="totalPages"></strong></span><div class="flex"><button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button><button @click="changePage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Next</button></div></div>
                </div>

                <div x-show="isModalOpen" x-trap.inert.noscroll="isModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isModalOpen" x-transition.opacity @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-3xl sm:w-full">
                            <form @submit.prevent="saveEmployee" x-ref="saveForm">
                                <div class="px-6 py-4"><h3 class="text-lg font-medium" x-text="modalTitle"></h3><p class="text-red-600 text-sm mt-2" x-text="modalError"></p>
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 max-h-[70vh] overflow-y-auto p-1">
                                        <div class="md:col-span-3"><hr> <h4 class="font-semibold text-gray-700">Employee Details</h4><hr></div>
                                        <div class="md:col-span-2"><label class="block text-sm">Full Name*</label><input type="text" name="full_name" x-model="formData.full_name" required class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Employee Code</label><input type="text" name="employee_code" x-model="formData.employee_code" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Designation</label><input type="text" name="designation" x-model="formData.designation" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Department</label><input type="text" name="department" x-model="formData.department" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Date of Joining</label><input type="date" name="date_of_joining" x-model="formData.date_of_joining" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <?php if ($user_role === 'admin'): ?>
                                            <div><label class="block text-sm">Branch</label><select name="branch_id" x-model="formData.branch_id" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">Select Branch</option><template x-for="branch in branches"><option :value="branch.id" x-text="branch.name"></option></template></select></div>
                                        <?php else: ?>
                                            <div><label class="block text-sm">Branch</label><input type="text" value="<?php echo htmlspecialchars($_SESSION['branch_name'] ?? ''); ?>" disabled class="mt-1 w-full p-2 border rounded-md bg-gray-100"></div>
                                        <?php endif; ?>
                                        <div><label class="block text-sm">Link to User Account</label><select name="user_id" x-model="formData.user_id" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">None</option><template x-if="formData.user_id && !unassignedUsers.some(u => u.id == formData.user_id)"><option :value="formData.user_id" x-text="`Linked User #${formData.user_id}`"></option></template><template x-for="user in unassignedUsers"><option :value="user.id" x-text="user.username"></option></template></select></div>
                                        <div><label class="block text-sm">Employment Status</label><select name="status" x-model="formData.status" class="mt-1 w-full p-2 border rounded-md bg-white"><option>Active</option><option>Resigned</option><option>Terminated</option></select></div>
                                        
                                        <div class="md:col-span-3"><hr> <h4 class="font-semibold text-gray-700">Financial Details</h4><hr></div>
                                        <div><label class="block text-sm">PAN Number</label><input type="text" name="pan_no" x-model="formData.pan_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Aadhaar Number</label><input type="text" name="aadhaar_no" x-model="formData.aadhaar_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div class="md:col-span-2"><label class="block text-sm">Bank Account No.</label><input type="text" name="bank_account_no" x-model="formData.bank_account_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Bank IFSC Code</label><input type="text" name="bank_ifsc_code" x-model="formData.bank_ifsc_code" class="mt-1 w-full p-2 border rounded-md"></div>

                                        <div class="md:col-span-3 mt-4">
                                            <div class="flex items-center"><input type="checkbox" name="is_driver" id="is_driver" x-model="isDriver" class="h-4 w-4 text-indigo-600 rounded"><label for="is_driver" class="ml-2 block text-sm font-medium">This employee is also a Driver</label></div>
                                        </div>
                                        <template x-if="isDriver">
                                            <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
                                                <input type="hidden" name="driver_id" :value="formData.driver_id || 0">
                                                <div class="md:col-span-2"><label class="block text-sm">License Number*</label><input type="text" name="license_number" x-model="formData.license_number" :required="isDriver" class="mt-1 w-full p-2 border rounded-md"></div>
                                                <div><label class="block text-sm">License Expiry Date</label><input type="date" name="license_expiry_date" x-model="formData.license_expiry_date" class="mt-1 w-full p-2 border rounded-md"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Employee</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('employeesApp', () => ({
        employees: [], isLoading: true, search: '', currentPage: 1, totalPages: 1,
        isModalOpen: false, modalError: '', formData: {},
        isDriver: false,
        branches: <?php echo json_encode($branches); ?>,
        unassignedUsers: <?php echo json_encode($unassigned_users); ?>,
        
        init() { this.fetchEmployees(); this.$watch('search', () => { this.currentPage = 1; this.fetchEmployees(); }); },
        async fetchEmployees() {
            this.isLoading = true;
            const params = new URLSearchParams({ search: this.search, page: this.currentPage });
            try {
                const response = await fetch(`manage_employees.php?action=get_employees&${params}`);
                const data = await response.json();
                this.employees = data.employees;
                this.totalPages = data.pagination.total_pages;
                this.currentPage = data.pagination.current_page;
            } catch (error) { console.error('Error fetching employees:', error); } 
            finally { this.isLoading = false; }
        },
        changePage(page) { if (page > 0 && page <= this.totalPages) { this.currentPage = page; this.fetchEmployees(); } },
        resetForm() {
            this.formData = { id: 0, full_name: '', user_id: '', branch_id: '<?php echo $user_role !== 'admin' ? $_SESSION['branch_id'] : ''; ?>', status: 'Active', license_number: '', license_expiry_date: '', driver_id: 0 };
            this.isDriver = false;
            this.modalError = '';
        },
        async openModal(employeeId = 0) {
            this.resetForm();
            if (employeeId) {
                this.modalTitle = 'Edit Employee';
                try {
                    const response = await fetch(`manage_employees.php?action=get_details&id=${employeeId}`);
                    if (!response.ok) throw new Error('Employee not found or access denied.');
                    const data = await response.json();
                    this.formData = data;
                    this.isDriver = !!data.driver_id;
                    if (data.user_id && !this.unassignedUsers.some(u => u.id == data.user_id)) {
                        const userResponse = await fetch(`manage_users.php?action=get_user_details&id=${data.user_id}`);
                        const userData = await userResponse.json();
                        if (userData) this.unassignedUsers.push({ id: userData.id, username: userData.username });
                    }
                } catch (error) { alert(error.message); return; }
            } else {
                this.modalTitle = 'Add New Employee';
            }
            this.isModalOpen = true;
        },
        async saveEmployee() {
            this.modalError = '';
            const formElement = this.$refs.saveForm;
            const formBody = new FormData(formElement);
            formBody.append('id', this.formData.id);
            try {
                const response = await fetch('manage_employees.php?action=save', { method: 'POST', body: formBody });
                const result = await response.json();
                if (result.success) { 
                    this.isModalOpen = false; 
                    // Refresh unassigned users list as one might have been linked
                    const userResponse = await fetch('manage_users.php?action=get_unassigned');
                    this.unassignedUsers = await userResponse.json();
                    this.fetchEmployees(); 
                } else { 
                    this.modalError = result.message || 'An unknown error occurred.'; 
                }
            } catch (error) { 
                this.modalError = 'A network error occurred.'; 
            }
        }
    }));
});
// Mobile sidebar toggle script
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    function toggleSidebar() { if (sidebar && sidebarOverlay) { sidebar.classList.toggle('-translate-x-full'); sidebarOverlay.classList.toggle('hidden'); } }
    if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
    if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
});
</script>
</body>
</html>