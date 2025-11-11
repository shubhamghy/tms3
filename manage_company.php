<?php
// --- START: ADDED ANTI-CACHING HEADERS ---
// These headers command the browser to always fetch a fresh copy of the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
// --- END: ADDED ANTI-CACHING HEADERS ---

session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($user_role !== 'admin') {
    header("location: dashboard.php");
    exit;
}

// Display messages based on the status from the URL
$form_message = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $form_message = '<div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">Company details updated successfully!</div>';
    } elseif ($_GET['status'] == 'error') {
        $form_message = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">Error updating details.</div>';
    }
}

// Helper function for logo upload
function upload_logo($file_input_name) {
    $target_dir = "uploads/company/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file_name = "logo." . strtolower(pathinfo($_FILES[$file_input_name]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $target_file;
        }
    }
    return null;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "UPDATE company_details SET name=?, slogan=?, address=?, gst_no=?, fssai_no=?, pan_no=?, email=?, website=?, contact_number_1=?, contact_number_2=? WHERE id=1";
    
    if($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ssssssssss", 
            $_POST['name'], $_POST['slogan'], $_POST['address'], $_POST['gst_no'], $_POST['fssai_no'], 
            $_POST['pan_no'], $_POST['email'], $_POST['website'], $_POST['contact_number_1'], $_POST['contact_number_2']
        );

        if ($stmt->execute()) {
            $logo_path = upload_logo('logo');
            if ($logo_path) {
                $logo_stmt = $mysqli->prepare("UPDATE company_details SET logo_path = ? WHERE id = 1");
                $logo_stmt->bind_param("s", $logo_path);
                $logo_stmt->execute();
                $logo_stmt->close();
            }
            
            // --- START: ADDED TIMESTAMP TO REDIRECT ---
            // This makes the URL unique, forcing the browser to reload
            $timestamp = time();
            header("Location: manage_company.php?status=success&t=" . $timestamp);
            exit;
            // --- END: ADDED TIMESTAMP TO REDIRECT ---

        } else {
            header("Location: manage_company.php?status=error");
            exit;
        }
        $stmt->close();
    }
}

// Fetch current company details
$company_details = $mysqli->query("SELECT * FROM company_details WHERE id = 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Company Details - TMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
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
                <?php if(!empty($form_message)) echo $form_message; ?>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Company Details</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-medium">Company Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($company_details['name'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Slogan</label><input type="text" name="slogan" value="<?php echo htmlspecialchars($company_details['slogan'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div class="md:col-span-2"><label class="block text-sm font-medium">Address</label><textarea name="address" rows="3" class="mt-1 block w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($company_details['address'] ?? ''); ?></textarea></div>
                            <div><label class="block text-sm font-medium">Contact Number 1</label><input type="text" name="contact_number_1" value="<?php echo htmlspecialchars($company_details['contact_number_1'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Contact Number 2</label><input type="text" name="contact_number_2" value="<?php echo htmlspecialchars($company_details['contact_number_2'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($company_details['email'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">Website</label><input type="text" name="website" value="<?php echo htmlspecialchars($company_details['website'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">GST Number</label><input type="text" name="gst_no" value="<?php echo htmlspecialchars($company_details['gst_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">PAN Number</label><input type="text" name="pan_no" value="<?php echo htmlspecialchars($company_details['pan_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div><label class="block text-sm font-medium">FSSAI Number</label><input type="text" name="fssai_no" value="<?php echo htmlspecialchars($company_details['fssai_no'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border rounded-md"></div>
                            <div>
                                <label class="block text-sm font-medium">Company Logo</label>
                                <input type="file" name="logo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <?php if (!empty($company_details['logo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($company_details['logo_path']); ?>" alt="Current Logo" class="mt-4 h-16">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-6 text-right">
                            <button type="submit" class="py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Details</button>
                        </div>
                    </form>
                </div>
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>
</body>
</html>