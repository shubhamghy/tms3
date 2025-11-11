<?php
// Start the session and check if the user is logged in
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once "config.php";
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$can_manage = in_array($user_role, ['admin', 'manager']);

// --- Form Processing ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    $type = $_POST['type'];
    $name = trim($_POST['name']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($type === 'country') {
        if ($id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE countries SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO countries (name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
    } elseif ($type === 'state') {
        $country_id = intval($_POST['country_id']);
        if ($id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE states SET name = ?, country_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $country_id, $id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO states (name, country_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $country_id);
        }
    } elseif ($type === 'city') {
        $state_id = intval($_POST['state_id']);
        if ($id > 0) { // Update
            $stmt = $mysqli->prepare("UPDATE cities SET name = ?, state_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $state_id, $id);
        } else { // Insert
            $stmt = $mysqli->prepare("INSERT INTO cities (name, state_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $state_id);
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Location saved successfully.</div>";
    } else {
        $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error saving location.</div>";
    }
    if(isset($stmt)) $stmt->close();
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $can_manage) {
    $type = $_GET['type'];
    $id = intval($_GET['id']);
    $table = '';
    if($type === 'country') $table = 'countries';
    if($type === 'state') $table = 'states';
    if($type === 'city') $table = 'cities';

    if($table){
        $stmt = $mysqli->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $message = "<div class='p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50'>Location deleted successfully.</div>";
        } else {
            $message = "<div class='p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50'>Error deleting location. It might be in use.</div>";
        }
        $stmt->close();
    }
}


// --- Data Fetching ---
$countries = $mysqli->query("SELECT * FROM countries ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$states = $mysqli->query("SELECT s.id, s.name, c.name as country_name FROM states s JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name ASC")->fetch_all(MYSQLI_ASSOC);
$cities = $mysqli->query("SELECT ci.id, ci.name, s.name as state_name FROM cities ci JOIN states s ON ci.state_id = s.id ORDER BY s.name, ci.name ASC")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations - TMS</title>
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
                 <div class="flex items-center px-4"><button class="text-gray-500 focus:outline-none focus:text-gray-700 md:hidden"><i class="fas fa-bars"></i></button></div>
                <div class="flex items-center pr-4">
                     <span class="text-gray-600 mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
            <div class="p-4 md:p-8">
                <?php echo $message; ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Countries -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold mb-4">Countries</h2>
                        <?php if($can_manage): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="type" value="country">
                            <input type="text" name="name" placeholder="New Country Name" class="w-full px-3 py-2 border rounded-md" required>
                            <button type="submit" class="mt-2 w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">Add Country</button>
                        </form>
                        <?php endif; ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach($countries as $c): ?>
                            <li class="py-2 flex justify-between items-center">
                                <?php echo htmlspecialchars($c['name']); ?>
                                <?php if($can_manage): ?>
                                <a href="?action=delete&type=country&id=<?php echo $c['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- States -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold mb-4">States</h2>
                        <?php if($can_manage): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="type" value="state">
                            <select name="country_id" class="w-full px-3 py-2 border rounded-md mb-2" required>
                                <option value="">Select Country</option>
                                <?php foreach($countries as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="name" placeholder="New State Name" class="w-full px-3 py-2 border rounded-md" required>
                            <button type="submit" class="mt-2 w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">Add State</button>
                        </form>
                        <?php endif; ?>
                         <ul class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            <?php foreach($states as $s): ?>
                            <li class="py-2 flex justify-between items-center">
                                <span><?php echo htmlspecialchars($s['name']); ?> <em class="text-gray-500 text-sm">(<?php echo htmlspecialchars($s['country_name']); ?>)</em></span>
                                <?php if($can_manage): ?>
                                <a href="?action=delete&type=state&id=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- Cities -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold mb-4">Cities</h2>
                        <?php if($can_manage): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="type" value="city">
                            <select name="state_id" class="w-full px-3 py-2 border rounded-md mb-2" required>
                                <option value="">Select State</option>
                                <?php foreach($states as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="name" placeholder="New City Name" class="w-full px-3 py-2 border rounded-md" required>
                            <button type="submit" class="mt-2 w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">Add City</button>
                        </form>
                        <?php endif; ?>
                         <ul class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            <?php foreach($cities as $ci): ?>
                            <li class="py-2 flex justify-between items-center">
                               <span><?php echo htmlspecialchars($ci['name']); ?> <em class="text-gray-500 text-sm">(<?php echo htmlspecialchars($ci['state_name']); ?>)</em></span>
                                <?php if($can_manage): ?>
                                <a href="?action=delete&type=city&id=<?php echo $ci['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
