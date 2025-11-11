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
        $sql = "SELECT e.id, e.full_name, e.employee_code, b.name as branch_name,
                       (SELECT COUNT(*) FROM salary_structures WHERE employee_id = e.id) as salary_set_count
                FROM employees e 
                LEFT JOIN branches b ON e.branch_id = b.id
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

    if ($action === 'get_salary_details') {
        $employee_id = intval($_GET['employee_id'] ?? 0);
        if ($employee_id === 0) { http_response_code(400); echo json_encode(['error' => 'Invalid Employee ID']); exit; }
        
        $sql = "SELECT ss.* FROM salary_structures ss JOIN employees e ON ss.employee_id = e.id WHERE ss.employee_id = ? $branch_filter_aliased";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $salary = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo json_encode($salary ?: new stdClass()); // Return empty object if no salary is set
        exit;
    }

    if ($action === 'save_salary' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        if ($employee_id === 0) { echo json_encode(['success' => false, 'message' => 'Invalid Employee ID.']); exit; }

        // Security Check: Ensure manager is not editing employee from another branch
        if ($user_role !== 'admin') {
            $check_stmt = $mysqli->prepare("SELECT id FROM employees WHERE id = ? AND branch_id = ?");
            $check_stmt->bind_param("ii", $employee_id, $branch_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']); exit;
            }
            $check_stmt->close();
        }

        $effective_date = $_POST['effective_date'];
        // All salary components
        $basic_salary = (float)($_POST['basic_salary'] ?? 0);
        $hra = (float)($_POST['hra'] ?? 0);
        $conveyance_allowance = (float)($_POST['conveyance_allowance'] ?? 0);
        $special_allowance = (float)($_POST['special_allowance'] ?? 0);
        $pf_employee_contribution = (float)($_POST['pf_employee_contribution'] ?? 0);
        $esi_employee_contribution = (float)($_POST['esi_employee_contribution'] ?? 0);
        $professional_tax = (float)($_POST['professional_tax'] ?? 0);
        $tds = (float)($_POST['tds'] ?? 0);

        // Check if a structure already exists
        $check_stmt = $mysqli->prepare("SELECT id FROM salary_structures WHERE employee_id = ?");
        $check_stmt->bind_param("i", $employee_id);
        $check_stmt->execute();
        $existing_id = $check_stmt->get_result()->fetch_assoc()['id'] ?? null;
        $check_stmt->close();

        if ($existing_id) { // UPDATE
            $sql = "UPDATE salary_structures SET effective_date=?, basic_salary=?, hra=?, conveyance_allowance=?, special_allowance=?, pf_employee_contribution=?, esi_employee_contribution=?, professional_tax=?, tds=? WHERE id=?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sddddddddi", $effective_date, $basic_salary, $hra, $conveyance_allowance, $special_allowance, $pf_employee_contribution, $esi_employee_contribution, $professional_tax, $tds, $existing_id);
        } else { // INSERT
            $sql = "INSERT INTO salary_structures (employee_id, effective_date, basic_salary, hra, conveyance_allowance, special_allowance, pf_employee_contribution, esi_employee_contribution, professional_tax, tds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isdddddddd", $employee_id, $effective_date, $basic_salary, $hra, $conveyance_allowance, $special_allowance, $pf_employee_contribution, $esi_employee_contribution, $professional_tax, $tds);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Salary structure saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employee Salary - TMS</title>
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
                 <div class="mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16"><button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-gray-800">Manage Employee Salary</h1><a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a></div></div>
            </header>
            
            <main class="p-4 md:p-8" x-data="salaryApp()">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-6">
                        <div class="relative w-full md:w-1/3"><input type="text" x-model.debounce.500ms="search" placeholder="Search for an employee..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>
                    </div>

                    <div x-show="isLoading" class="text-center py-10"><div class="spinner animate-spin w-10 h-10 border-4 rounded-full mx-auto"></div><p class="mt-2 text-gray-600">Loading employees...</p></div>
                    
                    <div x-show="!isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="employee in employees" :key="employee.id">
                            <div class="bg-gray-50 border rounded-lg p-4 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <h3 class="font-bold text-gray-800" x-text="employee.full_name"></h3>
                                        <span :class="employee.salary_set_count > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" class="px-2 py-0.5 text-xs font-semibold rounded-full" x-text="employee.salary_set_count > 0 ? 'Salary Set' : 'Not Set'"></span>
                                    </div>
                                    <p class="text-sm text-gray-500" x-text="employee.employee_code"></p>
                                    <p class="text-sm text-gray-500" x-text="employee.branch_name"></p>
                                </div>
                                <button @click="openModal(employee)" class="mt-4 w-full text-sm font-medium bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-100">Set / Edit Salary</button>
                            </div>
                        </template>
                        <div x-show="!isLoading && employees.length === 0" class="md:col-span-2 lg:col-span-3 text-center py-10 text-gray-500">No employees found.</div>
                    </div>
                    
                    <div x-show="!isLoading && totalPages > 1" class="mt-6 flex justify-between items-center"></div>
                </div>

                <div x-show="isModalOpen" x-trap.inert.noscroll="isModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isModalOpen" x-transition.opacity @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-4xl sm:w-full">
                            <form @submit.prevent="saveSalary">
                                <div class="px-6 py-4">
                                    <h3 class="text-lg font-medium">Salary Structure for <strong x-text="currentEmployee.full_name"></strong></h3>
                                    <p class="text-red-600 text-sm mt-2" x-text="modalError"></p>
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 max-h-[70vh] overflow-y-auto p-1">
                                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="md:col-span-3"><label class="block text-sm">Effective Date*</label><input type="date" name="effective_date" x-model="formData.effective_date" required class="mt-1 w-full p-2 border rounded-md"></div>
                                            <h4 class="md:col-span-3 text-md font-semibold text-gray-600 mt-2 border-b pb-2">Earnings</h4>
                                            <div><label class="block text-sm">Basic Salary</label><input type="number" step="0.01" name="basic_salary" x-model="formData.basic_salary" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div><label class="block text-sm">House Rent Allowance (HRA)</label><input type="number" step="0.01" name="hra" x-model="formData.hra" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div><label class="block text-sm">Conveyance Allowance</label><input type="number" step="0.01" name="conveyance_allowance" x-model="formData.conveyance_allowance" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div class="md:col-span-3"><label class="block text-sm">Special Allowance</label><input type="number" step="0.01" name="special_allowance" x-model="formData.special_allowance" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <h4 class="md:col-span-3 text-md font-semibold text-gray-600 mt-4 border-b pb-2">Deductions</h4>
                                            <div><label class="block text-sm">PF Contribution</label><input type="number" step="0.01" name="pf_employee_contribution" x-model="formData.pf_employee_contribution" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div><label class="block text-sm">ESI Contribution</label><input type="number" step="0.01" name="esi_employee_contribution" x-model="formData.esi_employee_contribution" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div><label class="block text-sm">Professional Tax</label><input type="number" step="0.01" name="professional_tax" x-model="formData.professional_tax" class="mt-1 w-full p-2 border rounded-md"></div>
                                            <div class="md:col-span-3"><label class="block text-sm">TDS / Income Tax</label><input type="number" step="0.01" name="tds" x-model="formData.tds" class="mt-1 w-full p-2 border rounded-md"></div>
                                        </div>
                                        <div class="md:col-span-1 bg-gray-50 p-4 rounded-lg">
                                            <h4 class="font-bold text-center mb-4">Salary Summary</h4>
                                            <div class="space-y-3 text-sm">
                                                <div class="flex justify-between"><span>Gross Earnings:</span><strong x-text="formatCurrency(grossEarnings)"></strong></div>
                                                <div class="flex justify-between"><span>Total Deductions:</span><strong x-text="formatCurrency(totalDeductions)"></strong></div>
                                                <hr>
                                                <div class="flex justify-between text-lg font-bold text-indigo-600"><span>Net Salary:</span><strong x-text="formatCurrency(netSalary)"></strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-100 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Structure</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('salaryApp', () => ({
        employees: [], isLoading: true, search: '', currentPage: 1, totalPages: 1,
        isModalOpen: false, modalError: '', currentEmployee: {},
        formData: {},
        init() { this.fetchEmployees(); this.$watch('search', () => { this.currentPage = 1; this.fetchEmployees(); }); },
        async fetchEmployees() {
            this.isLoading = true;
            const params = new URLSearchParams({ search: this.search, page: this.currentPage });
            try {
                const response = await fetch(`manage_salary.php?action=get_employees&${params}`);
                const data = await response.json();
                this.employees = data.employees;
                this.totalPages = data.pagination.total_pages;
                this.currentPage = data.pagination.current_page;
            } catch (error) { console.error('Error fetching employees:', error); } 
            finally { this.isLoading = false; }
        },
        changePage(page) { if (page > 0 && page <= this.totalPages) { this.currentPage = page; this.fetchEmployees(); } },
        resetForm() {
            this.formData = { employee_id: 0, effective_date: new Date().toISOString().slice(0, 10), basic_salary: 0, hra: 0, conveyance_allowance: 0, special_allowance: 0, pf_employee_contribution: 0, esi_employee_contribution: 0, professional_tax: 0, tds: 0 };
            this.modalError = '';
        },
        async openModal(employee) {
            this.resetForm();
            this.currentEmployee = employee;
            this.formData.employee_id = employee.id;
            try {
                const response = await fetch(`manage_salary.php?action=get_salary_details&employee_id=${employee.id}`);
                const data = await response.json();
                if (Object.keys(data).length > 0) {
                    this.formData = { ...this.formData, ...data };
                }
            } catch (error) { console.error('Could not fetch salary details', error); }
            this.isModalOpen = true;
        },
        async saveSalary() {
            this.modalError = '';
            const formBody = new URLSearchParams(this.formData);
            try {
                const response = await fetch('manage_salary.php?action=save_salary', { method: 'POST', body: formBody });
                const result = await response.json();
                if (result.success) { 
                    this.isModalOpen = false; 
                    this.fetchEmployees(); 
                } else { 
                    this.modalError = result.message || 'An unknown error occurred.'; 
                }
            } catch (error) { this.modalError = 'A network error occurred.'; }
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
        },
        get grossEarnings() {
            return (parseFloat(this.formData.basic_salary) || 0) + (parseFloat(this.formData.hra) || 0) + (parseFloat(this.formData.conveyance_allowance) || 0) + (parseFloat(this.formData.special_allowance) || 0);
        },
        get totalDeductions() {
            return (parseFloat(this.formData.pf_employee_contribution) || 0) + (parseFloat(this.formData.esi_employee_contribution) || 0) + (parseFloat(this.formData.professional_tax) || 0) + (parseFloat(this.formData.tds) || 0);
        },
        get netSalary() {
            return this.grossEarnings - this.totalDeductions;
        }
    }));
});
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