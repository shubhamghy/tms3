<?php
// --- For Debugging: Temporarily add these lines to see detailed errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -----------------------------------------------------------------------

session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$form_message = "";
$search_term = trim($_GET['search'] ?? '');
$active_tab = $_GET['tab'] ?? 'booked';


// --- Handle Form Submission for Status Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_shipment_id'])) {
    $shipment_id = intval($_POST['update_shipment_id']);
    $new_status = $_POST['new_status'];
    $location = trim($_POST['location']);
    $remarks = trim($_POST['remarks']);
    $updated_by_id = $_SESSION['id'];

    $mysqli->begin_transaction();
    try {
        // 1. Insert into tracking history
        $sql_track = "INSERT INTO shipment_tracking (shipment_id, location, remarks, updated_by_id) VALUES (?, ?, ?, ?)";
        $stmt_track = $mysqli->prepare($sql_track);
        $stmt_track->bind_param("issi", $shipment_id, $location, $remarks, $updated_by_id);
        if (!$stmt_track->execute()) { throw new Exception("Error saving tracking history: " . $stmt_track->error); }
        $stmt_track->close();

        // 2. Update the main shipment status
        $sql_update = "UPDATE shipments SET status = ? WHERE id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("si", $new_status, $shipment_id);
        if (!$stmt_update->execute()) { throw new Exception("Error updating shipment status: " . $stmt_update->error); }
        $stmt_update->close();
        
        $mysqli->commit();
        // Redirect back to the active tab after successful submission
        header("location: update_tracking.php?tab=" . urlencode($active_tab) . "&search=" . urlencode($search_term));
        exit;

    } catch (Exception $e) {
        $mysqli->rollback();
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
    }
}

// --- Function to Render Shipment Cards ---
function renderCardList($shipments, $total_pages, $current_page, $page_param_name) {
    if (empty($shipments)) {
        echo '<div class="text-center py-10"><i class="fas fa-box-open fa-3x text-gray-300"></i><p class="mt-4 text-gray-500">No shipments found matching your criteria.</p></div>';
        return;
    }
    
    echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
    foreach ($shipments as $shipment) {
        $current_status = htmlspecialchars($shipment['status']);
        $consignment_no = htmlspecialchars($shipment['consignment_no']);
        $consignment_date = htmlspecialchars(date("d M, Y", strtotime($shipment['consignment_date'])));
        $origin = htmlspecialchars($shipment['origin']);
        $destination = htmlspecialchars($shipment['destination']);
        $last_location_html = '';
        
        // --- FIX: Prepare variable outside Heredoc ---
        $last_location_data = isset($shipment['last_location']) ? htmlspecialchars($shipment['last_location']) : '';

        if (isset($shipment['last_location'])) {
            $last_location = htmlspecialchars($shipment['last_location']);
            $last_updated = htmlspecialchars(date("d M Y, h:i A", strtotime($shipment['last_updated_at'])));
            $last_location_html = <<<HTML
            <div class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">
                <p><i class="fas fa-location-arrow w-5 text-center mr-3 text-gray-400"></i> Last Location: <strong>{$last_location}</strong></p>
                <p><i class="fas fa-clock w-5 text-center mr-3 text-gray-400 mt-2"></i> Last Update: {$last_updated}</p>
            </div>
HTML;
        }

        echo <<<HTML
        <div class="bg-white rounded-xl shadow-md overflow-hidden transition hover:shadow-lg">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <a href="view_shipment_details.php?id={$shipment['id']}" class="text-indigo-600 hover:text-indigo-800 font-bold text-lg">{$consignment_no}</a>
                        <p class="text-sm text-gray-500 mt-1">{$consignment_date}</p>
                    </div>
                    <button @click="openModal(\$event)" 
                            data-id="{$shipment['id']}" 
                            data-cn="{$consignment_no}"
                            data-status="{$current_status}"
                            data-origin="{$origin}"
                            data-last-location="{$last_location_data}"
                            class="text-sm font-medium bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-200">
                        Update Status
                    </button>
                </div>
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-map-marker-alt w-5 text-center mr-3 text-red-500"></i>
                        <p class="font-semibold truncate">{$origin}</p>
                        <i class="fas fa-long-arrow-alt-right mx-3 text-gray-400"></i>
                        <i class="fas fa-map-marker-alt w-5 text-center mr-3 text-green-500"></i>
                        <p class="font-semibold truncate">{$destination}</p>
                    </div>
                </div>
                {$last_location_html}
            </div>
        </div>
HTML;
    }
    echo '</div>';

    // Pagination
    $base_query = $_GET;
    // Remove all page parameters to build a clean base query
    unset($base_query['page_booked'], $base_query['page_transit'], $base_query['page_reached'], $base_query['tab']);

    echo '<div class="mt-6 flex justify-end">';
    if ($total_pages > 1) {
        
        // Prepare current parameters including search and tab
        $pagination_params = array_merge($base_query, ['tab' => $_GET['tab'] ?? 'booked']);
        
        if ($current_page > 1) {
            $prev_page_query = http_build_query(array_merge($pagination_params, [$page_param_name => $current_page - 1]));
            echo "<a href='?{$prev_page_query}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100 mr-2'><i class='fas fa-chevron-left'></i></a>";
        }
        if ($current_page < $total_pages) {
            $next_page_query = http_build_query(array_merge($pagination_params, [$page_param_name => $current_page + 1]));
            echo "<a href='?{$next_page_query}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100'><i class='fas fa-chevron-right'></i></a>";
        }
    }
    echo '</div>';
}


// --- Data Fetching & Pagination ---
function fetchShipmentsByStatus($mysqli, $statuses, $page_param, $branch_filter, $search_term, $with_last_location = false) {
    global $mysqli; 
    
    $limit = 6;
    $page = isset($_GET[$page_param]) ? (int)$_GET[$page_param] : 1;
    $offset = ($page - 1) * $limit;
    
    if (empty($statuses)) return [[], 0, 1, 0];

    $status_placeholders = implode(',', array_fill(0, count($statuses), '?'));
    
    // --- Add Search Filter ---
    $search_filter_sql = "";
    $search_params = [];
    $search_types = "";
    
    // Only apply search if a term is provided
    if (!empty($search_term)) {
        $search_filter_sql = " AND (s.consignment_no LIKE ? OR s.origin LIKE ? OR s.destination LIKE ?)";
        $like_term = "%{$search_term}%";
        $search_params = [$like_term, $like_term, $like_term];
        $search_types = "sss";
    }

    // --- 1. Count Total Records ---
    $count_sql = "SELECT COUNT(s.id) FROM shipments s WHERE status IN ($status_placeholders) $branch_filter $search_filter_sql";
    $stmt_count = $mysqli->prepare($count_sql);
    
    // Dynamically bind parameters for the COUNT query
    $count_args = array(str_repeat('s', count($statuses)) . $search_types);
    foreach($statuses as &$status) { $count_args[] = $status; }
    foreach($search_params as &$param) { $count_args[] = $param; }
    
    $stmt_count->bind_param(...$count_args);
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);
    $stmt_count->close();

    // --- 2. Fetch Paginated Records ---
    $select_cols = "s.id, s.consignment_no, s.consignment_date, s.origin, s.destination, s.vehicle_id, s.status";
    $joins = "";
    
    if ($with_last_location) {
        $select_cols .= ", lt.location as last_location, lt.created_at as last_updated_at";
        $joins = "LEFT JOIN shipment_tracking lt ON lt.id = (SELECT MAX(id) FROM shipment_tracking WHERE shipment_id = s.id)";
    }
    
    $sql = "SELECT $select_cols
            FROM shipments s $joins
            WHERE s.status IN ($status_placeholders) $branch_filter $search_filter_sql
            ORDER BY s.consignment_date DESC, s.id DESC 
            LIMIT ? OFFSET ?";
            
    $stmt = $mysqli->prepare($sql);
    
    // Prepare all parameters: status strings + search params + limit integer + offset integer
    $types = str_repeat('s', count($statuses)) . $search_types . 'ii';
    $bind_params = array_merge($statuses, $search_params, array($limit, $offset));
    
    $bind_args = array();
    $bind_args[] = $types;
    foreach ($bind_params as &$param) {
        $bind_args[] = $param;
    }
    
    $stmt->bind_param(...$bind_args);

    $stmt->execute();
    $result = $stmt->get_result();
    $shipments = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $shipments[] = $row;
        }
    }
    $stmt->close();
    
    return [$shipments, $total_pages, $page, $total_records];
}


// --- ROLE-BASED FILTERING LOGIC ---
$branch_filter_sql = "";
$user_role = $_SESSION['role'] ?? null;
if ($user_role !== 'admin' && !empty($_SESSION['branch_id'])) {
    $user_branch_id = intval($_SESSION['branch_id']);
    $branch_filter_sql = " AND s.branch_id = $user_branch_id";
}

// Pass $mysqli instance and $search_term to the function
$booked_statuses = ['Booked', 'Billed', 'Pending Payment', 'Reverify'];
list($booked_shipments, $booked_total_pages, $booked_page, $booked_count) = fetchShipmentsByStatus($mysqli, $booked_statuses, 'page_booked', $branch_filter_sql, $search_term);

list($in_transit_shipments, $in_transit_total_pages, $in_transit_page, $in_transit_count) = fetchShipmentsByStatus($mysqli, ['In Transit'], 'page_transit', $branch_filter_sql, $search_term, true);

list($reached_shipments, $reached_total_pages, $reached_page, $reached_count) = fetchShipmentsByStatus($mysqli, ['Reached'], 'page_reached', $branch_filter_sql, $search_term, true);


// Fetch last 10 delivered shipments and total delivered count
$delivered_count_sql = "SELECT COUNT(s.id) FROM shipments s WHERE s.status = 'Delivered' $branch_filter_sql";
$delivered_count_result = $mysqli->query($delivered_count_sql);
// NOTE: Delivered tab results should NOT be affected by the primary search term, 
// as it is meant to show *all* recently delivered shipments for that branch.
$delivered_count_filtered = $delivered_count_result ? $delivered_count_result->fetch_row()[0] : 0;

$last_delivered_sql = "SELECT s.id, s.consignment_no, s.destination, st.created_at as delivery_date
                       FROM shipments s
                       JOIN shipment_tracking st ON s.id = st.shipment_id
                       WHERE s.status = 'Delivered' AND st.id = (SELECT MAX(id) FROM shipment_tracking WHERE shipment_id = s.id)
                       $branch_filter_sql
                       ORDER BY st.created_at DESC
                       LIMIT 10";
$last_delivered_result = $mysqli->query($last_delivered_sql);
$last_delivered_shipments = [];
if ($last_delivered_result) {
    while($row = $last_delivered_result->fetch_assoc()) {
        $last_delivered_shipments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Tracking - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="loader" class="fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
    <div class="fas fa-spinner fa-spin fa-3x text-indigo-600"></div>
</div>
<div class="flex h-screen bg-gray-100 overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-col flex-1 relative">
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-600 md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                    <h1 class="text-xl font-semibold text-gray-800">Update Shipment Tracking</h1>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8">
            <?php if(!empty($form_message)) echo $form_message; ?>

            <div x-data="trackingApp()" x-init="init()">
                
                <div class="bg-white p-4 rounded-xl shadow-md mb-6">
                    <form method="GET" action="update_tracking.php" class="flex items-center space-x-2">
                        <input type="hidden" name="tab" :value="activeTab">
                        <input type="text" name="search" placeholder="Search by CN, Origin, or Destination..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="py-2 px-4 border rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><i class="fas fa-search"></i></button>
                        <?php if (!empty($search_term)): ?>
                            <a href="update_tracking.php?tab=<?php echo urlencode($active_tab); ?>" class="py-2 px-4 border rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <a href="#" @click.prevent="activeTab = 'booked'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'booked', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'booked' }" class="flex items-center whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            To Be Dispatched
                            <span class="ml-2 inline-block py-0.5 px-2.5 leading-none text-center whitespace-nowrap align-baseline font-bold bg-blue-100 text-blue-700 rounded-full"><?php echo $booked_count; ?></span>
                        </a>
                        <a href="#" @click.prevent="activeTab = 'transit'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'transit', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'transit' }" class="flex items-center whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            In Transit
                            <span class="ml-2 inline-block py-0.5 px-2.5 leading-none text-center whitespace-nowrap align-baseline font-bold bg-cyan-100 text-cyan-700 rounded-full"><?php echo $in_transit_count; ?></span>
                        </a>
                        <a href="#" @click.prevent="activeTab = 'reached'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'reached', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'reached' }" class="flex items-center whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Reached Destination
                            <span class="ml-2 inline-block py-0.5 px-2.5 leading-none text-center whitespace-nowrap align-baseline font-bold bg-teal-100 text-teal-700 rounded-full"><?php echo $reached_count; ?></span>
                        </a>
                        <a href="#" @click.prevent="activeTab = 'delivered'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'delivered', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'delivered' }" class="flex items-center whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Recently Delivered
                             <span class="ml-2 inline-block py-0.5 px-2.5 leading-none text-center whitespace-nowrap align-baseline font-bold bg-green-100 text-green-700 rounded-full"><?php echo $delivered_count_filtered; ?></span>
                        </a>
                    </nav>
                </div>

                <div>
                    <div x-show="activeTab === 'booked'" x-cloak>
                        <?php renderCardList($booked_shipments, $booked_total_pages, $booked_page, 'page_booked'); ?>
                    </div>
                    <div x-show="activeTab === 'transit'" x-cloak>
                        <?php renderCardList($in_transit_shipments, $in_transit_total_pages, $in_transit_page, 'page_transit'); ?>
                    </div>
                    <div x-show="activeTab === 'reached'" x-cloak>
                        <?php renderCardList($reached_shipments, $reached_total_pages, $reached_page, 'page_reached'); ?>
                    </div>
                    <div x-show="activeTab === 'delivered'" x-cloak>
                        <div class="bg-white rounded-xl shadow-md p-6">
                             <h3 class="text-lg font-semibold text-gray-800 mb-4">Last 10 Delivered Shipments</h3>
                             <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50"><tr><th class="py-2 px-3 text-left font-semibold">CN No.</th><th class="py-2 px-3 text-left font-semibold">Destination</th><th class="py-2 px-3 text-left font-semibold">Delivery Date</th></tr></thead>
                                    <tbody class="divide-y">
                                        <?php if(empty($last_delivered_shipments)): ?>
                                            <tr><td colspan="3" class="text-center py-6 text-gray-500">No recently delivered shipments found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($last_delivered_shipments as $shipment): ?>
                                            <tr><td class="py-2 px-3"><a href="view_shipment_details.php?id=<?php echo $shipment['id']; ?>" class="text-indigo-600 hover:underline"><?php echo htmlspecialchars($shipment['consignment_no']); ?></a></td><td class="py-2 px-3"><?php echo htmlspecialchars($shipment['destination']); ?></td><td class="py-2 px-3"><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($shipment['delivery_date']))); ?></td></tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                             </div>
                        </div>
                    </div>
                </div>
            
                <div x-show="isModalOpen" @keydown.escape.window="isModalOpen = false" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen px-4 text-center">
                        <div @click="isModalOpen = false" class="fixed inset-0 transition-opacity" aria-hidden="true"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="post">
                                <input type="hidden" name="update_shipment_id" :value="shipmentId">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Update Status for <span x-text="consignmentNo" class="font-bold"></span></h3>
                                    <div class="mt-4 space-y-4">
                                        <div id="update_warning" class="hidden p-3 text-sm text-yellow-800 rounded-lg bg-yellow-50"></div>
                                        <div><label for="new_status" class="block text-sm font-medium text-gray-700">New Status</label><select id="new_status" name="new_status" @change="updateLocationSuggestion()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required></select></div>
                                        <div><label for="location" class="block text-sm font-medium text-gray-700">Current Location</label><input type="text" name="location" id="location" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required></div>
                                        <div><label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label><textarea name="remarks" id="remarks" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea></div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Save Update</button>
                                    <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                                }
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </main>
    </div>
</div>

<script>
    function trackingApp() {
        return {
            activeTab: '<?php echo $active_tab; ?>',
            isModalOpen: false,
            shipmentId: null,
            consignmentNo: '',
            currentStatus: '',
            shipmentOrigin: '',
            shipmentLastLocation: '',
            
            openModal(event) {
                this.shipmentId = event.currentTarget.dataset.id;
                this.consignmentNo = event.currentTarget.dataset.cn;
                this.currentStatus = event.currentTarget.dataset.status;
                this.shipmentOrigin = event.currentTarget.dataset.origin;
                this.shipmentLastLocation = event.currentTarget.dataset.lastLocation;

                this.populateStatusDropdown(this.currentStatus);
                this.updateLocationSuggestion(); // Initial location suggestion
                this.checkTodaysUpdates();
                this.isModalOpen = true;
            },
            
            populateStatusDropdown(currentStatus) {
                const statusSelect = document.getElementById('new_status');
                statusSelect.innerHTML = '';
                let options = [];
                // Dynamically determine allowed next states
                if (['Booked', 'Billed', 'Pending Payment', 'Reverify'].includes(currentStatus)) {
                    options = ['In Transit'];
                } else if (currentStatus === 'In Transit') {
                    options = ['In Transit', 'Reached'];
                } else if (currentStatus === 'Reached') {
                    options = ['Delivered'];
                }
                options.forEach(opt => {
                    const optionEl = document.createElement('option');
                    optionEl.value = opt;
                    optionEl.textContent = opt;
                    statusSelect.appendChild(optionEl);
                });
            },
            
            updateLocationSuggestion() {
                const newStatus = document.getElementById('new_status').value;
                const locationInput = document.getElementById('location');
                
                let suggestedLocation = '';

                if (newStatus === 'In Transit' && ['Booked', 'Billed', 'Pending Payment', 'Reverify'].includes(this.currentStatus)) {
                    // First update: Suggest the origin
                    suggestedLocation = this.shipmentOrigin;
                } else if (newStatus === 'Reached' || newStatus === 'Delivered') {
                    // Final status: Suggest the destination (not explicitly provided in modal data, so user must enter)
                    suggestedLocation = ''; // Keep blank to force user input (or fetch destination if possible)
                } else if (this.shipmentLastLocation) {
                    // Subsequent updates in transit: Suggest the last known location for review/re-entry
                    suggestedLocation = this.shipmentLastLocation;
                } else {
                    suggestedLocation = ''; // Default to blank
                }
                
                locationInput.value = suggestedLocation;
            },
            
            async checkTodaysUpdates() {
                const warningDiv = document.getElementById('update_warning');
                warningDiv.classList.add('hidden');
                try {
                    const response = await fetch(`check_updates.php?shipment_id=${this.shipmentId}`);
                    const data = await response.json();
                    if (data.count >= 2) {
                        warningDiv.textContent = `Warning: This consignment has already been updated ${data.count} times today.`;
                        warningDiv.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error("Could not check for updates:", error);
                }
            },
            
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                if (tab && ['booked', 'transit', 'reached', 'delivered'].includes(tab)) {
                    this.activeTab = tab;
                }
                
                // --- NEW FIX: Watch activeTab to update URL for persistence ---
                this.$watch('activeTab', (newTab) => {
                    if (newTab !== urlParams.get('tab')) {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('tab', newTab);
                        // Clear page parameters when switching tabs
                        currentUrl.searchParams.delete('page_booked');
                        currentUrl.searchParams.delete('page_transit');
                        currentUrl.searchParams.delete('page_reached');
                        window.history.pushState({}, '', currentUrl.toString());
                    }
                });
            }
        };
    }

    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarClose = document.getElementById('sidebar-close');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }

    if (sidebarToggle) { sidebarToggle.addEventListener('click', toggleSidebar); }
    if (sidebarClose) { sidebarClose.addEventListener('click', toggleSidebar); }
    if (sidebarOverlay) { sidebarOverlay.addEventListener('click', toggleSidebar); }
</script>
<script>
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
