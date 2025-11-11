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
        $branch_filter_aliased = " AND d.branch_id = $user_branch_id";
    }

    $action = $_GET['action'];

    function handle_upload_api($file_input_name, $existing_path = '') {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
            $target_dir = "uploads/drivers/";
            if (!is_dir($target_dir)) { @mkdir($target_dir, 0755, true); }
            $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
            $new_file_name = "driver_{$file_input_name}_" . time() . ".{$file_ext}";
            $target_file = $target_dir . $new_file_name;
            if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
                if (!empty($existing_path) && file_exists($existing_path)) { @unlink($existing_path); }
                return $target_file;
            }
        }
        return $existing_path;
    }

    if ($action === 'get_drivers') {
        $page = $_GET['page'] ?? 1; $search = $_GET['search'] ?? ''; $limit = 9; $offset = ($page - 1) * $limit;
        $where_clauses = ["1=1"]; $params = []; $types = "";
        if (!empty($search)) {
            $where_clauses[] = "(d.name LIKE ? OR d.license_number LIKE ?)";
            $search_term = "%{$search}%";
            array_push($params, $search_term, $search_term);
            $types .= "ss";
        }
        $where_sql = implode(" AND ", $where_clauses);

        $total_sql = "SELECT COUNT(d.id) FROM drivers d WHERE $where_sql $branch_filter_aliased";
        $stmt_total = $mysqli->prepare($total_sql);
        if (!empty($search)) { $stmt_total->bind_param($types, ...$params); }
        $stmt_total->execute();
        $total_records = $stmt_total->get_result()->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);
        $stmt_total->close();

        $drivers = [];
        $sql = "SELECT d.id, d.name, d.contact_number, d.license_number, d.photo_path, e.employee_code 
                FROM drivers d
                LEFT JOIN employees e ON d.employee_id = e.id
                WHERE $where_sql $branch_filter_aliased ORDER BY d.name ASC LIMIT ? OFFSET ?";
        
        $types .= "ii";
        array_push($params, $limit, $offset);
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $drivers[] = $row; }
        $stmt->close();

        echo json_encode(['drivers' => $drivers, 'pagination' => ['total_records' => $total_records, 'total_pages' => $total_pages, 'current_page' => (int)$page]]);
        exit;
    }

    if ($action === 'get_details') {
        $driver_id = intval($_GET['id'] ?? 0);
        if ($driver_id === 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit; }
        
        $sql = "SELECT d.*, e.employee_code 
                FROM drivers d 
                LEFT JOIN employees e ON d.employee_id = e.id
                WHERE d.id = ? $branch_filter_aliased";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $details = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($details) { echo json_encode($details); } 
        else { http_response_code(404); echo json_encode(['error' => 'Driver not found or access denied.']); }
        exit;
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
        
        if ($id > 0) { // UPDATE
            $sql = "UPDATE drivers SET name=?, address=?, contact_number=?, license_number=?, license_expiry_date=?, aadhaar_no=?, pan_no=?, photo_path=?, license_doc_path=?, aadhaar_doc_path=?, bank_doc_path=?, is_active=? WHERE id=?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssssssssii", $name, $_POST['address'], $_POST['contact_number'], $_POST['license_number'], $_POST['license_expiry_date'], $_POST['aadhaar_no'], $_POST['pan_no'], handle_upload_api('photo_path', $_POST['existing_photo_path']), handle_upload_api('license_doc_path', $_POST['existing_license_doc_path']), handle_upload_api('aadhaar_doc_path', $_POST['existing_aadhaar_doc_path']), handle_upload_api('bank_doc_path', $_POST['existing_bank_doc_path']), $is_active, $id);
        } else { // INSERT
            $sql = "INSERT INTO drivers (name, address, contact_number, license_number, license_expiry_date, aadhaar_no, pan_no, photo_path, license_doc_path, aadhaar_doc_path, bank_doc_path, is_active, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssssssssii", $name, $_POST['address'], $_POST['contact_number'], $_POST['license_number'], $_POST['license_expiry_date'], $_POST['aadhaar_no'], $_POST['pan_no'], handle_upload_api('photo_path'), handle_upload_api('license_doc_path'), handle_upload_api('aadhaar_doc_path'), handle_upload_api('bank_doc_path'), $is_active, $branch_id);
        }
        
        if ($stmt->execute()) { echo json_encode(['success' => true]); } 
        else { echo json_encode(['success' => false, 'message' => $stmt->error]); }
        $stmt->close();
        exit;
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $request_body = json_decode(file_get_contents("php://input"), true);
        $id = intval($request_body['id'] ?? 0);
        if ($id > 0) {
            $stmt = $mysqli->prepare("DELETE d FROM drivers d WHERE d.id = ? $branch_filter_aliased");
            $stmt->bind_param("i", $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) { echo json_encode(['success' => true]); } 
            else { echo json_encode(['success' => false, 'message' => 'Could not delete driver.']); }
            $stmt->close();
        } else { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); }
        exit;
    }
}

// ===================================================================================
// --- SECTION 3: REGULAR PAGE LOAD LOGIC ---
// ===================================================================================

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
$user_role = $_SESSION['role'] ?? '';
$can_manage = in_array($user_role, ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - TMS</title>
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
                 <div class="mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16"><button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-gray-800">Manage Drivers</h1><a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a></div></div>
            </header>
            
            <main class="p-4 md:p-8" x-data="driversApp()">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                        <div class="relative w-full md:w-1/3"><input type="text" x-model.debounce.500ms="search" placeholder="Search by name or license..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>
                        <button @click="openModal()" class="w-full md:w-auto inline-flex items-center justify-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Driver</button>
                    </div>

                    <div x-show="isLoading" class="text-center py-10"><div class="spinner animate-spin w-10 h-10 border-4 rounded-full mx-auto"></div><p class="mt-2 text-gray-600">Loading drivers...</p></div>
                    
                    <div x-show="!isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="driver in drivers" :key="driver.id">
                           <div class="bg-white rounded-xl shadow-md p-6 flex flex-col justify-between border">
                                <div class="flex items-center space-x-4">
                                    <img class="h-16 w-16 rounded-full object-cover" :src="driver.photo_path || 'https://placehold.co/100x100/e2e8f0/e2e8f0'" alt="Driver photo">
                                    <div>
                                        <h3 class="font-bold text-lg text-gray-800" x-text="driver.name"></h3>
                                        <p class="text-sm text-gray-500" x-text="driver.contact_number"></p>
                                        <p x-show="driver.employee_code" class="text-xs text-indigo-600 font-semibold" x-text="`Emp Code: ${driver.employee_code}`"></p>
                                    </div>
                                </div>
                                <div class="mt-4 border-t pt-4 text-sm text-gray-600"><p><i class="fas fa-id-card w-5 mr-2 text-gray-400"></i>License: <span x-text="driver.license_number"></span></p></div>
                                <div class="mt-4 flex justify-end space-x-3 text-sm font-medium">
                                    <button @click="openViewModal(driver.id)" class="text-indigo-600 hover:text-indigo-800">Details</button>
                                    <button @click="openModal(driver.id)" class="text-green-600 hover:text-green-800">Edit</button>
                                    <button @click="deleteDriver(driver.id)" class="text-red-600 hover:text-red-800">Delete</button>
                                </div>
                           </div>
                        </template>
                        <div x-show="!isLoading && drivers.length === 0" class="md:col-span-2 lg:col-span-3 text-center py-10 text-gray-500">No drivers found.</div>
                    </div>

                    <div x-show="!isLoading && totalPages > 1" class="mt-6 flex justify-between items-center"><span class="text-sm text-gray-700">Page <strong x-text="currentPage"></strong> of <strong x-text="totalPages"></strong></span><div class="flex"><button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button><button @click="changePage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Next</button></div></div>
                </div>

                <div x-show="isViewModalOpen" x-trap.inert.noscroll="isViewModalOpen" class="fixed inset-0 z-40 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isViewModalOpen" x-transition.opacity @click="isViewModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isViewModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-2xl sm:w-full"><div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800" x-text="details.name"></h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div><p class="font-semibold text-gray-500">Employee Code</p><p x-text="details.employee_code || 'N/A (Not an Employee)'"></p></div>
                            <div><p class="font-semibold text-gray-500">Contact</p><p x-text="details.contact_number"></p></div>
                            <div><p class="font-semibold text-gray-500">License No.</p><p x-text="details.license_number"></p></div>
                            <div><p class="font-semibold text-gray-500">License Expiry</p><p x-text="details.license_expiry_date || 'N/A'"></p></div>
                            <div class="md:col-span-2"><p class="font-semibold text-gray-500">Address</p><p x-text="details.address"></p></div>
                        </div>
                        <div class="mt-4 border-t pt-4"><h4 class="font-semibold mb-2">Documents</h4><div class="flex space-x-4"><a x-show="details.photo_path" :href="details.photo_path" target="_blank" class="text-indigo-600 hover:underline">Photo</a><a x-show="details.license_doc_path" :href="details.license_doc_path" target="_blank" class="text-indigo-600 hover:underline">License</a></div></div>
                    </div><div class="bg-gray-50 px-6 py-3 flex justify-end"><button type="button" @click="isViewModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Close</button></div></div></div>
                </div>

                <div x-show="isModalOpen" x-trap.inert.noscroll="isModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isModalOpen" x-transition.opacity @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-3xl sm:w-full">
                        <form @submit.prevent="saveDriver" x-ref="saveForm">
                            <div class="px-6 py-4"><h3 class="text-lg font-medium" x-text="modalTitle"></h3><p class="text-red-600 text-sm mt-2" x-text="modalError"></p>
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto p-1">
                                    <div><label class="block text-sm">Name*</label><input type="text" name="name" x-model="formData.name" required class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div><label class="block text-sm">Contact Number*</label><input type="text" name="contact_number" x-model="formData.contact_number" required class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div class="md:col-span-2"><label class="block text-sm">Address</label><textarea name="address" x-model="formData.address" rows="2" class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                                    <div><label class="block text-sm">License Number*</label><input type="text" name="license_number" x-model="formData.license_number" required class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div><label class="block text-sm">License Expiry Date</label><input type="date" name="license_expiry_date" x-model="formData.license_expiry_date" class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div><label class="block text-sm">Aadhaar No.</label><input type="text" name="aadhaar_no" x-model="formData.aadhaar_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div><label class="block text-sm">PAN No.</label><input type="text" name="pan_no" x-model="formData.pan_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                    <div><label class="block text-sm">Photo</label><input type="file" name="photo_path" class="mt-1 w-full text-sm"><input type="hidden" name="existing_photo_path" :value="formData.photo_path"></div>
                                    <div><label class="block text-sm">License Document</label><input type="file" name="license_doc_path" class="mt-1 w-full text-sm"><input type="hidden" name="existing_license_doc_path" :value="formData.license_doc_path"></div>
                                    <div><label class="block text-sm">Aadhaar Document</label><input type="file" name="aadhaar_doc_path" class="mt-1 w-full text-sm"><input type="hidden" name="existing_aadhaar_doc_path" :value="formData.aadhaar_doc_path"></div>
                                    <div><label class="block text-sm">Bank Document</label><input type="file" name="bank_doc_path" class="mt-1 w-full text-sm"><input type="hidden" name="existing_bank_doc_path" :value="formData.bank_doc_path"></div>
                                    <div class="md:col-span-2 flex items-center"><input type="checkbox" name="is_active" value="1" x-model="formData.is_active" class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Driver</button></div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('driversApp', () => ({
        drivers: [], isLoading: true, search: '', currentPage: 1, totalPages: 1,
        isModalOpen: false, isViewModalOpen: false, modalTitle: '', modalError: '', 
        formData: {}, details: {},
        
        init() { this.fetchDrivers(); this.$watch('search', () => { this.currentPage = 1; this.fetchDrivers(); }); },
        
        async fetchDrivers() {
            this.isLoading = true;
            const params = new URLSearchParams({ search: this.search, page: this.currentPage });
            try {
                const response = await fetch(`manage_drivers.php?action=get_drivers&${params}`);
                const data = await response.json();
                this.drivers = data.drivers;
                this.totalPages = data.pagination.total_pages;
                this.currentPage = data.pagination.current_page;
            } catch (error) { console.error('Error:', error); } 
            finally { this.isLoading = false; }
        },
        
        changePage(page) { if (page > 0 && page <= this.totalPages) { this.currentPage = page; this.fetchDrivers(); } },
        
        resetForm() {
            this.formData = { id: 0, name: '', is_active: true };
            this.modalError = '';
        },
        
        async openModal(driverId = 0) {
            this.resetForm();
            if (driverId) {
                this.modalTitle = 'Edit Driver';
                const response = await fetch(`manage_drivers.php?action=get_details&id=${driverId}`);
                const data = await response.json();
                if(data) { this.formData = {...data, is_active: data.is_active == 1}; }
            } else {
                this.modalTitle = 'Add New Driver';
            }
            this.isModalOpen = true;
        },

        async openViewModal(driverId) {
            this.details = {};
            const response = await fetch(`manage_drivers.php?action=get_details&id=${driverId}`);
            this.details = await response.json();
            this.isViewModalOpen = true;
        },
        
        async saveDriver() {
            this.modalError = '';
            const formElement = this.$refs.saveForm;
            const formBody = new FormData(formElement);
            formBody.append('id', this.formData.id);
            formBody.set('is_active', this.formData.is_active ? '1' : '0');

            try {
                const response = await fetch('manage_drivers.php?action=save', { method: 'POST', body: formBody });
                const result = await response.json();
                if (result.success) { this.isModalOpen = false; this.fetchDrivers(); } 
                else { this.modalError = result.message || 'Error occurred.'; }
            } catch (error) { this.modalError = 'Network error.'; }
        },

        async deleteDriver(driverId) {
            if (confirm('Are you sure you want to delete this driver?')) {
                try {
                    const response = await fetch('manage_drivers.php?action=delete', { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: driverId }) 
                    });
                    const result = await response.json();
                    if (result.success) { this.fetchDrivers(); } 
                    else { alert(result.message); }
                } catch (error) { alert('Network error.'); }
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