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

    $branch_filter_aliased = "";
    $branch_filter_no_alias = "";
    if ($user_role !== 'admin' && !empty($branch_id)) {
        $user_branch_id = intval($branch_id);
        $branch_filter_aliased = " AND p.branch_id = $user_branch_id";
        $branch_filter_no_alias = " AND branch_id = $user_branch_id";
    }

    $action = $_GET['action'];

    function upload_file_api($file_input_name, $party_id) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
            $target_dir = "uploads/parties/";
            if (!is_dir($target_dir)) { @mkdir($target_dir, 0755, true); }
            $file_ext = strtolower(pathinfo(basename($_FILES[$file_input_name]["name"]), PATHINFO_EXTENSION));
            $new_file_name = "party_{$party_id}_{$file_input_name}_" . time() . ".{$file_ext}";
            $target_file = $target_dir . $new_file_name;
            if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
                return $target_file;
            }
        }
        return null;
    }

    if ($action === 'get_parties') {
        $page = $_GET['page'] ?? 1; $search = $_GET['search'] ?? ''; $limit = 10; $offset = ($page - 1) * $limit;
        $where_clauses = ["1=1"]; $params = []; $types = "";
        
        if (!empty($search)) {
            $where_clauses[] = "(p.name LIKE ? OR p.city LIKE ? OR p.gst_no LIKE ?)";
            $search_term = "%{$search}%";
            array_push($params, $search_term, $search_term, $search_term);
            $types .= "sss";
        }
        
        // NEW: Add admin branch filter
        if ($user_role === 'admin' && !empty($_GET['filter_branch_id'])) {
            $where_clauses[] = "p.branch_id = ?";
            $params[] = intval($_GET['filter_branch_id']);
            $types .= "i";
        }

        $where_sql = implode(" AND ", $where_clauses);
        $total_sql = "SELECT COUNT(p.id) FROM parties p WHERE $where_sql $branch_filter_aliased";
        $stmt_total = $mysqli->prepare($total_sql);
        if (!empty($params)) { $stmt_total->bind_param($types, ...$params); }
        $stmt_total->execute();
        $total_records = $stmt_total->get_result()->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);
        $stmt_total->close();
        
        $parties = [];
        $sql = "SELECT p.id, p.name, p.party_type, p.city, p.state, p.is_active, b.name as branch_name 
                FROM parties p LEFT JOIN branches b ON p.branch_id = b.id
                WHERE $where_sql $branch_filter_aliased ORDER BY p.name ASC LIMIT ? OFFSET ?";
        
        $types .= "ii";
        array_push($params, $limit, $offset);
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $parties[] = $row; }
        $stmt->close();
        echo json_encode(['parties' => $parties, 'pagination' => ['total_records' => $total_records, 'total_pages' => $total_pages, 'current_page' => (int)$page]]);
        exit;
    }

    if ($action === 'get_details') {
        $party_id = intval($_GET['id'] ?? 0);
        if ($party_id === 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit; }
        $stmt = $mysqli->prepare("SELECT p.*, b.name as branch_name FROM parties p LEFT JOIN branches b ON p.branch_id = b.id WHERE p.id = ? $branch_filter_aliased");
        $stmt->bind_param("i", $party_id);
        $stmt->execute();
        $party = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($party) { echo json_encode($party); } 
        else { http_response_code(404); echo json_encode(['error' => 'Party not found or access denied.']); }
        exit;
    }
    
    // MODIFIED: Added credit_limit to the save logic
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$can_manage) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permission Denied']); exit; }
        
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']); $party_type = trim($_POST['party_type']); $address = trim($_POST['address']);
        $gst_no = trim($_POST['gst_no']); $pan_no = trim($_POST['pan_no']); $contact_number = trim($_POST['contact_number']);
        $contact_person = trim($_POST['contact_person']); $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
        $credit_limit = (float)($_POST['credit_limit'] ?? 0.00); // NEW FIELD

        $country_id = intval($_POST['country_id'] ?? 0); $state_id = intval($_POST['state_id'] ?? 0); $city_id = intval($_POST['city_id'] ?? 0);
        $country_name = ''; if ($country_id > 0) { $country_name = $mysqli->query("SELECT name FROM countries WHERE id = $country_id")->fetch_assoc()['name'] ?? ''; }
        $state_name = ''; if ($state_id > 0) { $state_name = $mysqli->query("SELECT name FROM states WHERE id = $state_id")->fetch_assoc()['name'] ?? ''; }
        $city_name = ''; if ($city_id > 0) { $city_name = $mysqli->query("SELECT name FROM cities WHERE id = $city_id")->fetch_assoc()['name'] ?? ''; }
        
        if (empty($name) || empty($party_type)) { echo json_encode(['success' => false, 'message' => 'Party Name and Type are required.']); exit; }
        
        if ($id > 0) { // UPDATE
            $sql = "UPDATE parties SET name=?, party_type=?, address=?, city=?, state=?, country=?, gst_no=?, pan_no=?, contact_number=?, contact_person=?, is_active=?, credit_limit=? WHERE id=? $branch_filter_no_alias";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssssssiidi", $name, $party_type, $address, $city_name, $state_name, $country_name, $gst_no, $pan_no, $contact_number, $contact_person, $is_active, $credit_limit, $id);
        } else { // INSERT
            // MODIFIED: Check if admin is inserting for a specific branch
            $insert_branch_id = $branch_id; // Default to user's session branch
            if ($user_role === 'admin' && isset($_POST['admin_branch_id'])) {
                 // If admin sent 'admin_branch_id' (even if empty string for 'All Branches'), use it.
                 $insert_branch_id = !empty($_POST['admin_branch_id']) ? intval($_POST['admin_branch_id']) : null;
            }

            $sql = "INSERT INTO parties (name, party_type, address, city, state, country, gst_no, pan_no, contact_number, contact_person, is_active, credit_limit, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            // MODIFIED: use $insert_branch_id
            $stmt->bind_param("sssssssssiidi", $name, $party_type, $address, $city_name, $state_name, $country_name, $gst_no, $pan_no, $contact_number, $contact_person, $is_active, $credit_limit, $insert_branch_id);
        }

        if ($stmt->execute()) {
            $party_id = ($id > 0) ? $id : $stmt->insert_id;
            $gst_doc_path = upload_file_api('gst_doc', $party_id);
            $pan_doc_path = upload_file_api('pan_doc', $party_id);
            if ($gst_doc_path) { $mysqli->query("UPDATE parties SET gst_doc_path = '{$mysqli->real_escape_string($gst_doc_path)}' WHERE id = {$party_id}"); }
            if ($pan_doc_path) { $mysqli->query("UPDATE parties SET pan_doc_path = '{$mysqli->real_escape_string($pan_doc_path)}' WHERE id = {$party_id}"); }
            
            echo json_encode(['success' => true, 'message' => 'Party saved successfully!']);
        } else { echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]); }
        $stmt->close();
        exit;
    }
    
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$can_manage) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permission Denied']); exit; }
        $request_body = json_decode(file_get_contents("php://input"), true);
        $id = intval($request_body['id'] ?? 0);
        if ($id > 0) {
            $stmt = $mysqli->prepare("DELETE FROM parties WHERE id = ? $branch_filter_no_alias");
            $stmt->bind_param("i", $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) { echo json_encode(['success' => true, 'message' => 'Party deleted successfully.']); } 
            else { echo json_encode(['success' => false, 'message' => 'Could not delete party. It may not exist or you lack permission.']); }
            $stmt->close();
        } else { echo json_encode(['success' => false, 'message' => 'Invalid ID provided.']); }
        exit;
    }
}

// ===================================================================================
// --- SECTION 3: REGULAR PAGE LOAD LOGIC ---
// ===================================================================================

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
$user_role = $_SESSION['role'] ?? '';
$can_manage = in_array($user_role, ['admin', 'manager']);
$countries = $mysqli->query("SELECT id, name FROM countries ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
// NEW: Fetch branches for admin filter
$all_branches = [];
if ($user_role === 'admin') {
    $all_branches = $mysqli->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parties - TMS</title>
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
                 <div class="mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16"><button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-gray-800">Manage Parties</h1><a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a></div></div>
            </header>
            
            <main class="p-4 md:p-8" x-data="partiesApp()">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                        <div class="relative w-full md:w-1/3"><input type="text" x-model.debounce.500ms="search" placeholder="Search by name, city, GST..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>
                        
                        <?php if ($user_role === 'admin'): ?>
                        <div class="relative w-full md:w-1/3">
                            <select x-model="filterBranchId" class="w-full pl-3 pr-8 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white appearance-none">
                                <option value="">Filter by Branch...</option>
                                <template x-for="branch in allBranches" :key="branch.id">
                                    <option :value="branch.id" x-text="branch.name"></option>
                                </template>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                        <?php endif; ?>

                        <?php if ($can_manage): ?>
                        <button @click="openModal()" class="w-full md:w-auto inline-flex items-center justify-center py-2 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i> Add New Party</button>
                        <?php endif; ?>
                    </div>
                    <div x-show="isLoading" class="text-center py-10"><div class="spinner animate-spin w-10 h-10 border-4 rounded-full mx-auto"></div><p class="mt-2 text-gray-600">Loading parties...</p></div>
                    <div x-show="!isLoading" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium uppercase">Name</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Location</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Branch</th><th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th><th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th></tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="party in parties" :key="party.id"><tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="party.name"></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="party.party_type"></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="`${party.city || ''}, ${party.state || ''}`"></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="party.branch_name || 'Global'"></td><td class="px-6 py-4 whitespace-nowrap text-sm"><span :class="party.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" x-text="party.is_active == 1 ? 'Active' : 'Inactive'"></span></td><td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4"><button @click="openViewModal(party.id)" class="text-gray-600 hover:text-indigo-900">Details</button><?php if ($can_manage): ?><button @click="openModal(party.id)" class="text-indigo-600 hover:text-indigo-900">Edit</button><button @click="deleteParty(party.id)" class="text-red-600 hover:text-red-900">Delete</button><?php endif; ?></td></tr></template>
                                <tr x-show="!isLoading && parties.length === 0"><td colspan="6" class="text-center py-10 text-gray-500">No parties found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="!isLoading && totalPages > 1" class="mt-6 flex justify-between items-center"><span class="text-sm text-gray-700">Page <strong x-text="currentPage"></strong> of <strong x-text="totalPages"></strong></span><div class="flex"><button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button><button @click="changePage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 mx-1 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Next</button></div></div>
                </div>

                <div x-show="isViewModalOpen" x-trap.inert.noscroll="isViewModalOpen" class="fixed inset-0 z-40 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isViewModalOpen" x-transition.opacity @click="isViewModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isViewModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full"><div class="px-6 py-4"><h3 class="text-lg font-medium" x-text="viewData.name"></h3><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm max-h-[60vh] overflow-y-auto pr-2"><div><p class="font-semibold text-gray-500">Party Type</p><p x-text="viewData.party_type"></p></div><div><p class="font-semibold text-gray-500">Contact Person</p><p x-text="viewData.contact_person || 'N/A'"></p></div><div class="md:col-span-2"><p class="font-semibold text-gray-500">Address</p><p x-text="viewData.address"></p></div><div><p class="font-semibold text-gray-500">Location</p><p x-text="`${viewData.city}, ${viewData.state}, ${viewData.country}`"></p></div><div><p class="font-semibold text-gray-500">Contact Number</p><p x-text="viewData.contact_number || 'N/A'"></p></div><div><p class="font-semibold text-gray-500">GST No.</p><p x-text="viewData.gst_no || 'N/A'"></p></div><div><p class="font-semibold text-gray-500">PAN No.</p><p x-text="viewData.pan_no || 'N/A'"></p></div>
                                        
                                        <div><p class="font-semibold text-gray-500">Credit Limit</p><p x-text="`₹${parseFloat(viewData.credit_limit || 0).toFixed(2)}`"></p></div>

                                        <div><p class="font-semibold text-gray-500">GST Doc</p><a x-show="viewData.gst_doc_path" :href="viewData.gst_doc_path" target="_blank" class="text-indigo-600 hover:underline">View Document</a><span x-show="!viewData.gst_doc_path">Not Uploaded</span></div><div><p class="font-semibold text-gray-500">PAN Doc</p><a x-show="viewData.pan_doc_path" :href="viewData.pan_doc_path" target="_blank" class="text-indigo-600 hover:underline">View Document</a><span x-show="!viewData.pan_doc_path">Not Uploaded</span></div></div></div><div class="bg-gray-50 px-6 py-3 flex justify-end"><button type="button" @click="isViewModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Close</button></div></div></div>
                </div>

                <div x-show="isModalOpen" x-trap.inert.noscroll="isModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen"><div x-show="isModalOpen" x-transition.opacity @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div><div x-show="isModalOpen" x-transition class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full">
                            <form @submit.prevent="saveParty" x-ref="saveForm">
                                <div class="px-6 py-4"><h3 class="text-lg font-medium" x-text="modalTitle"></h3><p class="text-red-600 text-sm mt-2" x-text="modalError"></p>
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto p-1">
                                        <div class="md:col-span-2"><label class="block text-sm">Party Name*</label><input type="text" name="name" x-model="formData.name" required class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">Party Type*</label><select name="party_type" x-model="formData.party_type" required class="mt-1 w-full p-2 border rounded-md bg-white"><option>Consignor</option><option>Consignee</option><option>Both</option></select></div>
                                        <div><label class="block text-sm">Contact Person</label><input type="text" name="contact_person" x-model="formData.contact_person" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div class="md:col-span-2"><label class="block text-sm">Address</label><textarea name="address" x-model="formData.address" rows="2" class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                                        <div><label class="block text-sm">Country</label><select name="country_id" x-model="formData.country_id" @change="fetchStates()" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">Select Country</option><template x-for="country in countries" :key="country.id"><option :value="country.id" x-text="country.name"></option></template></select></div>
                                        <div><label class="block text-sm">State</label><select name="state_id" x-model="formData.state_id" @change="fetchCities()" :disabled="!formData.country_id || states.length === 0" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">Select State</option><template x-for="state in states" :key="state.id"><option :value="state.id" x-text="state.name"></option></template></select></div>
                                        <div><label class="block text-sm">City</label><select name="city_id" x-model="formData.city_id" :disabled="!formData.state_id || cities.length === 0" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">Select City</option><template x-for="city in cities" :key="city.id"><option :value="city.id" x-text="city.name"></option></template></select></div>
                                        
                                        <div><label class="block text-sm">Credit Limit (₹)</label><input type="number" step="0.01" name="credit_limit" x-model="formData.credit_limit" class="mt-1 w-full p-2 border rounded-md"></div>
                                        
                                        <div><label class="block text-sm">Contact Number</label><input type="text" name="contact_number" x-model="formData.contact_number" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">GST No.</label><input type="text" name="gst_no" x-model="formData.gst_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">PAN No.</label><input type="text" name="pan_no" x-model="formData.pan_no" class="mt-1 w-full p-2 border rounded-md"></div>
                                        <div><label class="block text-sm">GST Document</label><input type="file" name="gst_doc" class="mt-1 w-full text-sm"></div>
                                        <div><label class="block text-sm">PAN Document</label><input type="file" name="pan_doc" class="mt-1 w-full text-sm"></div>
                                        <div class="md:col-span-2 flex items-center"><input type="checkbox" name="is_active" value="1" x-model="formData.is_active" class="h-4 w-4 text-indigo-600 rounded"><label class="ml-2 block text-sm">Is Active</label></div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Party</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('partiesApp', () => ({
        // State Properties
        parties: [], isLoading: true, search: '', currentPage: 1, totalPages: 1,
        isModalOpen: false, modalTitle: '', modalError: '', formData: {},
        isViewModalOpen: false, viewData: {},
        countries: <?php echo json_encode($countries); ?>,
        allBranches: <?php echo json_encode($all_branches); ?>, // NEW
        states: [], cities: [],
        filterBranchId: '', // NEW
        
        // Initializer
        init() { 
            this.fetchParties(); 
            this.$watch('search', () => { this.currentPage = 1; this.fetchParties(); }); 
            this.$watch('filterBranchId', () => { this.currentPage = 1; this.fetchParties(); }); // NEW
        },
        
        // Data Fetching Methods
        async fetchParties() {
            this.isLoading = true;
            const params = new URLSearchParams({ search: this.search, page: this.currentPage });
            
            // NEW
            if (this.filterBranchId) {
                params.append('filter_branch_id', this.filterBranchId);
            }

            try {
                const response = await fetch(`manage_parties.php?action=get_parties&${params}`);
                if (!response.ok) throw new Error('Network response was not ok.');
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                this.parties = data.parties;
                this.totalPages = data.pagination.total_pages;
                this.currentPage = data.pagination.current_page;
            } catch (error) { console.error('Error fetching parties:', error); alert('Could not fetch party data.'); } 
            finally { this.isLoading = false; }
        },
        changePage(page) { if (page > 0 && page <= this.totalPages) { this.currentPage = page; this.fetchParties(); } },

        // Form & Modal Methods
        resetForm() {
            // MODIFIED: Added credit_limit to reset/init
            this.formData = { id: 0, name: '', party_type: 'Consignor', address: '', country_id: 1, state_id: '', city_id: '', city:'', state:'', country:'', gst_no: '', pan_no: '', contact_number: '', contact_person: '', credit_limit: 0.00, is_active: true };
            this.states = []; this.cities = []; this.modalError = '';
        },
        async openModal(partyId = 0) {
            this.resetForm();
            if (partyId) {
                this.modalTitle = 'Edit Party';
                try {
                    const response = await fetch(`manage_parties.php?action=get_details&id=${partyId}`);
                    if (!response.ok) throw new Error('Party not found or access denied.');
                    const data = await response.json();
                    this.formData = { ...data, is_active: data.is_active == 1 };
                    await this.preselectLocations(data.country, data.state, data.city);
                } catch (error) { alert(error.message); return; }
            } else {
                this.modalTitle = 'Add New Party';
                await this.fetchStates();
            }
            this.isModalOpen = true;
        },
        async openViewModal(partyId) {
            this.viewData = {};
            try {
                const response = await fetch(`manage_parties.php?action=get_details&id=${partyId}`);
                if (!response.ok) throw new Error('Party not found or access denied.');
                this.viewData = await response.json();
                this.isViewModalOpen = true;
            } catch (error) { alert(error.message); }
        },

        // Location Dropdown Methods
        async preselectLocations(countryName, stateName, cityName) {
            const country = this.countries.find(c => c.name === countryName);
            if (country) {
                this.formData.country_id = country.id;
                await this.fetchStates(stateName, cityName);
            }
        },
        async fetchStates(stateToSelect = null, cityToSelect = null) {
            this.states = []; this.cities = [];
            if (!this.formData.country_id) return;
            try {
                const response = await fetch(`get_locations.php?get=states&country_id=${this.formData.country_id}`);
                this.states = await response.json();
                if (stateToSelect) {
                    await this.$nextTick();
                    const state = this.states.find(s => s.name === stateToSelect);
                    if (state) { this.formData.state_id = state.id; await this.fetchCities(cityToSelect); }
                }
            } catch (error) { console.error('Could not fetch states', error); }
        },
        async fetchCities(cityToSelect = null) {
            this.cities = [];
            if (!this.formData.state_id) return;
            try {
                const response = await fetch(`get_locations.php?get=cities&state_id=${this.formData.state_id}`);
                this.cities = await response.json();
                if (cityToSelect) {
                    await this.$nextTick();
                    const city = this.cities.find(c => c.name === cityToSelect);
                    if (city) { this.formData.city_id = city.id; }
                }
            } catch (error) { console.error('Could not fetch cities', error); }
        },

        // Action Methods: Save and Delete
        async saveParty() {
            this.modalError = '';
            const formElement = this.$refs.saveForm;
            const formBody = new FormData(formElement);
            
            formBody.append('id', this.formData.id);
            formBody.set('is_active', this.formData.is_active ? '1' : '0');
            // MODIFIED: Ensure credit limit is set even if blank in form (to 0)
            formBody.set('credit_limit', this.formData.credit_limit || 0); 

            // NEW: Pass the filter branch id if admin is inserting
            if (this.allBranches.length > 0 && this.formData.id == 0) {
                formBody.append('admin_branch_id', this.filterBranchId);
            }

            try {
                const response = await fetch('manage_parties.php?action=save', { method: 'POST', body: formBody });
                const result = await response.json();
                if (result.success) { this.isModalOpen = false; this.fetchParties(); } 
                else { this.modalError = result.message || 'An unknown error occurred.'; }
            } catch (error) { this.modalError = 'A network error occurred.'; }
        },
        async deleteParty(partyId) {
            if (confirm('Are you sure you want to delete this party? This action cannot be undone.')) {
                try {
                    const response = await fetch('manage_parties.php?action=delete', { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: partyId }) 
                    });
                    const result = await response.json();
                    if (result.success) { this.fetchParties(); } 
                    else { alert(result.message); }
                } catch (error) { alert('A network error occurred.'); }
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