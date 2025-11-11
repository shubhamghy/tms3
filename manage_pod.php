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

// --- Image Compression Function ---
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    if (!$info) return false;

    $image = null;
    $mime = $info['mime'];
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($source);
        // Convert quality from 0-100 to 0-9 for png
        $png_quality = floor(($quality / 100) * 9);
        // Preserve transparency
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagepng($image, $destination, $png_quality);
    } else {
        return false;
    }
    
    if ($image) {
        imagedestroy($image);
        return true;
    }
    return false;
}


// --- Corrected POD Upload Handling ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pod_shipment_id'])) {
    $shipment_id = intval($_POST['pod_shipment_id']);
    $remarks = trim($_POST['pod_remarks']);
    $pod_doc_path = null;
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    try {
        // 1. Check for basic upload errors
        if (!isset($_FILES['pod_doc']) || !is_uploaded_file($_FILES['pod_doc']['tmp_name'])) {
            throw new Exception("No file was uploaded. Please select a document.");
        }

        if ($_FILES['pod_doc']['error'] !== UPLOAD_ERR_OK) {
             $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds max size defined in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
            ];
            $error_message = $upload_errors[$_FILES['pod_doc']['error']] ?? 'Unknown upload error.';
            throw new Exception($error_message);
        }
        
        // 2. File size and extension validation
        $file_info = pathinfo($_FILES["pod_doc"]["name"]);
        $file_ext = strtolower($file_info['extension'] ?? '');
        $temp_file = $_FILES["pod_doc"]["tmp_name"];
        $file_size = $_FILES["pod_doc"]["size"];

        if ($file_size > $max_file_size) {
             throw new Exception("File size exceeds the 5MB limit.");
        }
        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and PDF are allowed.");
        }

        // 3. Prepare the target directory
        $target_dir = "uploads/pod/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                throw new Exception("Failed to create the POD uploads directory.");
            }
        }

        // 4. Process and move the file
        $file_name = "pod_{$shipment_id}_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png']);
        
        if ($is_image && function_exists('imagecreatefromjpeg')) {
            if (!compressImage($temp_file, $target_file, 75)) {
                // If compression fails (e.g., corrupt image), fall back to simple move
                 if (!move_uploaded_file($temp_file, $target_file)) {
                    throw new Exception("Failed to move the uploaded file (compression fallback).");
                 }
            }
            $pod_doc_path = $target_file;
        } else {
            // Move non-image files (like PDF) or files where GD is not available
            if (!move_uploaded_file($temp_file, $target_file)) {
                throw new Exception("Failed to move the uploaded file.");
            }
            $pod_doc_path = $target_file;
        }

        // 5. Update the database if file was saved successfully
        if ($pod_doc_path) {
            $sql = "UPDATE shipments SET status = 'Completed', pod_doc_path = ?, pod_remarks = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) throw new Exception("Database prepare statement failed.");
            
            $stmt->bind_param("ssi", $pod_doc_path, $remarks, $shipment_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating shipment record: " . $stmt->error);
            }
            $stmt->close();
            $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">POD uploaded and trip marked as completed!</div>';
        } else {
            throw new Exception("File path was not set correctly after upload process.");
        }

    } catch (Exception $e) {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error: ' . $e->getMessage() . '</div>';
    }
}

// --- Function to Render Cards for Pending PODs ---
function renderPodCardList($shipments, $total_pages, $current_page, $page_param_name) {
    if (empty($shipments)) {
        echo '<div class="text-center py-10"><i class="fas fa-inbox fa-3x text-gray-300"></i><p class="mt-4 text-gray-500">No shipments are awaiting POD.</p></div>';
        return;
    }
    
    echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
    foreach ($shipments as $shipment) {
        $consignment_no = htmlspecialchars($shipment['consignment_no']);
        $destination = htmlspecialchars($shipment['destination']);
        $consignee_name = htmlspecialchars($shipment['consignee_name']);
        $delivery_date = htmlspecialchars(date("d M Y, h:i A", strtotime($shipment['delivery_date'])));

        echo <<<HTML
        <div class="bg-white rounded-xl shadow-md overflow-hidden transition hover:shadow-lg">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <a href="view_shipment_details.php?id={$shipment['id']}" class="text-indigo-600 hover:text-indigo-800 font-bold text-lg">{$consignment_no}</a>
                        <p class="text-sm text-gray-500 mt-1">To: {$destination}</p>
                    </div>
                    <button @click="openModal(\$event)" 
                            data-id="{$shipment['id']}" 
                            data-cn="{$consignment_no}"
                            data-consignee="{$consignee_name}"
                            data-delivery-date="{$delivery_date}"
                            class="text-sm font-medium bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200">
                        <i class="fas fa-upload mr-2"></i>Upload POD
                    </button>
                </div>
                <div class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">
                    <p><i class="fas fa-building w-5 text-center mr-3 text-gray-400"></i> Consignee: <strong>{$consignee_name}</strong></p>
                    <p><i class="fas fa-check-circle w-5 text-center mr-3 text-gray-400 mt-2"></i> Delivered On: <strong>{$delivery_date}</strong></p>
                </div>
            </div>
        </div>
HTML;
    }
    echo '</div>';

    // Pagination
    $base_query = $_GET;
    unset($base_query[$page_param_name]); // Remove page from params to avoid duplication
    $query_string = http_build_query($base_query);
    
    echo '<div class="mt-6 flex justify-end">';
    if ($total_pages > 1) {
        if ($current_page > 1) {
            $prev_page_query = http_build_query(array_merge($base_query, [$page_param_name => $current_page - 1]));
            echo "<a href='?{$prev_page_query}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100 mr-2'><i class='fas fa-chevron-left'></i></a>";
        }
        // Only show next if not on the last page
        if ($current_page < $total_pages) {
            $next_page_query = http_build_query(array_merge($base_query, [$page_param_name => $current_page + 1]));
            echo "<a href='?{$next_page_query}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100'><i class='fas fa-chevron-right'></i></a>";
        }
    }
    echo '</div>';
}

// --- Data Fetching ---
$limit = 6;
$branch_filter_sql = "";
$user_role = $_SESSION['role'] ?? null;
if ($user_role !== 'admin' && !empty($_SESSION['branch_id'])) {
    $user_branch_id = intval($_SESSION['branch_id']);
    $branch_filter_sql = " AND s.branch_id = $user_branch_id";
}


// Pending PODs
$page_pending = isset($_GET['page_pending']) ? (int)$_GET['page_pending'] : 1;
$offset_pending = ($page_pending - 1) * $limit;

// --- IMPROVEMENT: Filter/Search for Pending PODs ---
$search_pending = trim($_GET['search_pending'] ?? '');
$filter_where_pending = "";
$pending_params = [];
$pending_types = "";

if (!empty($search_pending)) {
    $like_term = "%{$search_pending}%";
    $filter_where_pending = " AND (s.consignment_no LIKE ? OR v.vehicle_number LIKE ?)";
    $pending_params = [$like_term, $like_term];
    $pending_types = "ss";
}

$pending_pod_base_sql = "FROM shipments s
                        JOIN parties p ON s.consignee_id = p.id
                        JOIN shipment_tracking st ON s.id = st.shipment_id
                        LEFT JOIN vehicles v ON s.vehicle_id = v.id
                        WHERE s.status = 'Delivered' 
                        AND st.id = (SELECT MAX(id) FROM shipment_tracking WHERE shipment_id = s.id)
                        {$branch_filter_sql}
                        {$filter_where_pending}";


$total_pending_sql = "SELECT COUNT(s.id) " . $pending_pod_base_sql;
$stmt_count_pending = $mysqli->prepare($total_pending_sql);
if (!empty($pending_params)) {
    $stmt_count_pending->bind_param($pending_types, ...$pending_params);
}
$stmt_count_pending->execute();
$total_pending = $stmt_count_pending->get_result()->fetch_row()[0];
$stmt_count_pending->close();
$total_pages_pending = ceil($total_pending / $limit);


$pending_pod_sql = "SELECT s.id, s.consignment_no, s.destination, p.name as consignee_name, st.created_at as delivery_date
                        " . $pending_pod_base_sql . "
                        ORDER BY st.created_at DESC
                        LIMIT ? OFFSET ?";
                        
$pending_params[] = $limit;
$pending_types .= "i";
$pending_params[] = $offset_pending;
$pending_types .= "i";

$pending_pod_result = [];
if ($stmt_pending = $mysqli->prepare($pending_pod_sql)) {
    $stmt_pending->bind_param($pending_types, ...$pending_params);
    $stmt_pending->execute();
    $result = $stmt_pending->get_result();
    $pending_pods = [];
    if ($result) { while($row = $result->fetch_assoc()) { $pending_pods[] = $row; } }
    $stmt_pending->close();
}


// Completed PODs
$search_completed = $_GET['search_completed'] ?? '';
$start_date_completed = $_GET['start_date_completed'] ?? '';
$end_date_completed = $_GET['end_date_completed'] ?? '';

$where_clauses_completed = ["s.status = 'Completed'"];
$completed_params = [];
$completed_types = "";

if (!empty($search_completed)) {
    $like_term = "%{$search_completed}%";
    $where_clauses_completed[] = "(s.consignment_no LIKE ? OR v.vehicle_number LIKE ?)";
    $completed_params = [$like_term, $like_term];
    $completed_types = "ss";
}
if (!empty($start_date_completed)) {
    $where_clauses_completed[] = "s.consignment_date >= ?";
    $completed_params[] = $start_date_completed;
    $completed_types .= "s";
}
if (!empty($end_date_completed)) {
    $where_clauses_completed[] = "s.consignment_date <= ?";
    $completed_params[] = $end_date_completed;
    $completed_types .= "s";
}
$where_sql_completed = " WHERE " . implode(' AND ', $where_clauses_completed) . $branch_filter_sql;


$page_completed = isset($_GET['page_completed']) ? (int)$_GET['page_completed'] : 1;
$offset_completed = ($page_completed - 1) * $limit;

// Count query for completed
$total_completed_sql = "SELECT COUNT(s.id) FROM shipments s LEFT JOIN vehicles v ON s.vehicle_id = v.id" . $where_sql_completed;
$stmt_count_completed = $mysqli->prepare($total_completed_sql);
if (!empty($completed_params)) {
    $stmt_count_completed->bind_param($completed_types, ...$completed_params);
}
$stmt_count_completed->execute();
$total_completed = $stmt_count_completed->get_result()->fetch_row()[0];
$stmt_count_completed->close();
$total_pages_completed = ceil($total_completed / $limit);

// List query for completed
$completed_pod_sql = "SELECT s.id, s.consignment_no, s.destination, s.pod_doc_path, s.consignment_date, v.vehicle_number FROM shipments s LEFT JOIN vehicles v ON s.vehicle_id = v.id " . $where_sql_completed . " ORDER BY s.id DESC LIMIT ? OFFSET ?";

$completed_params[] = $limit;
$completed_types .= "i";
$completed_params[] = $offset_completed;
$completed_types .= "i";

$completed_pod_result = [];
if ($stmt_completed = $mysqli->prepare($completed_pod_sql)) {
    $stmt_completed->bind_param($completed_types, ...$completed_params);
    $stmt_completed->execute();
    $result = $stmt_completed->get_result();
    $completed_pods = [];
    if ($result) { while($row = $result->fetch_assoc()) { $completed_pods[] = $row; } }
    $stmt_completed->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage POD - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style> body { font-family: 'Inter', sans-serif; } [x-cloak] { display: none; } </style>
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
                    <h1 class="text-xl font-semibold text-gray-800">Manage Proof of Delivery</h1>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8" x-data="podApp()" x-init="init()">
            <?php if(!empty($form_message)) echo $form_message; ?>

            <div x-data="{ activeTab: '<?php echo isset($_GET['search_completed']) || isset($_GET['page_completed']) ? 'completed' : 'pending'; ?>' }">
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" @click.prevent="activeTab = 'pending'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'pending'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Pending POD</a>
                        <a href="#" @click.prevent="activeTab = 'completed'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'completed'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Completed</a>
                    </nav>
                </div>
                <div>
                    <div x-show="activeTab === 'pending'" x-cloak>
                        <div class="bg-white p-4 rounded-xl shadow-md mb-6">
                             <form method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                                <input type="hidden" name="tab" value="pending">
                                <div class="lg:col-span-2">
                                    <label for="search_pending" class="block text-sm font-medium text-gray-700">Search CN or Vehicle No.</label>
                                    <input type="text" id="search_pending" name="search_pending" placeholder="Enter Consignment or Vehicle Number..." value="<?php echo htmlspecialchars($search_pending); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                                </div>
                                <div class="flex items-end space-x-2">
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">Filter</button>
                                    <a href="manage_pod.php?tab=pending" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Reset</a>
                                </div>
                            </form>
                        </div>
                        <?php renderPodCardList($pending_pods, $total_pages_pending, $page_pending, 'page_pending'); ?>
                    </div>
                    <div x-show="activeTab === 'completed'" x-cloak>
                        <div class="bg-white p-4 rounded-xl shadow-md mb-6">
                             <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                                <input type="hidden" name="tab" value="completed">
                                <div class="lg:col-span-2">
                                    <label for="search_completed" class="block text-sm font-medium text-gray-700">Search CN or Vehicle No.</label>
                                    <input type="text" id="search_completed" name="search_completed" placeholder="Enter Consignment or Vehicle Number..." value="<?php echo htmlspecialchars($search_completed); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                                </div>
                                <div>
                                    <label for="start_date_completed" class="block text-sm font-medium text-gray-700">From Date (Booking)</label>
                                    <input type="date" id="start_date_completed" name="start_date_completed" value="<?php echo htmlspecialchars($start_date_completed); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                                </div>
                                <div>
                                    <label for="end_date_completed" class="block text-sm font-medium text-gray-700">To Date (Booking)</label>
                                    <input type="date" id="end_date_completed" name="end_date_completed" value="<?php echo htmlspecialchars($end_date_completed); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm">
                                </div>
                                <div class="flex items-end space-x-2 lg:col-start-4">
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">Filter</button>
                                    <a href="manage_pod.php?tab=completed" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Reset</a>
                                </div>
                            </form>
                        </div>
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <table class="min-w-full text-sm">
                               <thead class="bg-gray-50"><tr><th class="py-2 px-3 text-left">CN No.</th><th class="py-2 px-3 text-left">Vehicle No.</th><th class="py-2 px-3 text-left">Booking Date</th><th class="py-2 px-3 text-left">Destination</th><th class="py-2 px-3 text-left">Action</th></tr></thead>
                               <tbody class="divide-y">
                                <?php if(empty($completed_pods)): ?>
                                <tr><td colspan="5" class="text-center py-6 text-gray-500">No completed PODs found for the selected criteria.</td></tr>
                                <?php else: foreach($completed_pods as $pod): ?>
                                <tr>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($pod['consignment_no']); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($pod['vehicle_number'] ?? 'N/A'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars(date("d-m-Y", strtotime($pod['consignment_date']))); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($pod['destination']); ?></td>
                                    <td class="py-2 px-3"><a href="<?php echo htmlspecialchars($pod['pod_doc_path']); ?>" target="_blank" class="text-indigo-600 hover:underline">View POD</a></td>
                                </tr>
                                <?php endforeach; endif; ?>
                               </tbody>
                            </table>
                             <div class="mt-4 flex justify-end">
                                 <?php 
                                 $base_query = $_GET;
                                 unset($base_query['page_completed']);
                                 $query_string_completed = http_build_query($base_query);

                                 if ($total_pages_completed > 1) {
                                     if ($page_completed > 1) {
                                         echo "<a href='?page_completed=" . ($page_completed - 1) . "&amp;{$query_string_completed}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100 mr-2'><i class='fas fa-chevron-left'></i></a>";
                                     }
                                     if ($page_completed < $total_pages_completed) {
                                         echo "<a href='?page_completed=" . ($page_completed + 1) . "&amp;{$query_string_completed}' class='px-3 py-2 text-gray-500 bg-white border rounded-md hover:bg-gray-100'><i class='fas fa-chevron-right'></i></a>";
                                     }
                                 }
                                 ?>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="isModalOpen" class="fixed inset-0 z-30 overflow-y-auto" x-cloak>
                 <div class="flex items-center justify-center min-h-screen">
                    <div @click="isModalOpen = false" class="fixed inset-0 bg-gray-500 opacity-75"></div>
                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="pod_shipment_id" :value="shipmentId">
                            <div class="px-6 py-4">
                                <h3 class="text-lg font-medium">Upload POD for <span x-text="consignmentNo" class="font-bold"></span></h3>
                                <div class="mt-4 bg-gray-50 p-4 rounded-lg text-sm space-y-2">
                                    <p><strong>Consignee:</strong> <span x-text="consigneeName"></span></p>
                                    <p><strong>Delivered On:</strong> <span x-text="deliveryDate"></span></p>
                                </div>
                                <div class="mt-4">
                                    <label for="pod_doc" class="block text-sm font-medium text-gray-700">POD Document (JPG, PNG, PDF)</label>
                                    <input type="file" name="pod_doc" id="pod_doc" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                                </div>
                                <div class="mt-4">
                                    <label for="pod_remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea name="pod_remarks" id="pod_remarks" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"></textarea>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                                <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-md">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Submit POD</button>
                            </div>
                        </form>
                    </div>
                 </div>
            </div>
            <?php include 'footer.php'; ?>
        </main>
    </div>
</div>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function podApp() {
        return {
            activeTab: 'pending',
            isModalOpen: false,
            shipmentId: '',
            consignmentNo: '',
            consigneeName: '',
            deliveryDate: '',
            
            openModal(event) {
                this.shipmentId = event.currentTarget.dataset.id;
                this.consignmentNo = event.currentTarget.dataset.cn;
                this.consigneeName = event.currentTarget.dataset.consignee;
                this.deliveryDate = event.currentTarget.dataset.deliveryDate;
                this.isModalOpen = true;
            },

            init() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('search_completed') || urlParams.has('page_completed') || urlParams.get('tab') === 'completed') {
                    this.activeTab = 'completed';
                } else {
                    this.activeTab = 'pending';
                }
            }
        };
    }

    // Mobile sidebar toggle script
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
