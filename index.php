<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to dashboard page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
 
require_once "config.php";

// Fetch Company Details for branding
$company_details = $mysqli->query("SELECT name, logo_path FROM company_details WHERE id = 1")->fetch_assoc();
 
$username = "";
$password = "";
$login_err = "";
 
// Check for "remember me" cookie
if(isset($_COOKIE["remember_user"])) {
    $username = $_COOKIE["remember_user"];
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["username"]))){
        $login_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $login_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($login_err)){
        $sql = "SELECT id, username, password, role, branch_id, photo_path, last_login FROM users WHERE username = ? AND is_active = 1";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){                    
                    $stmt->bind_result($id, $username, $hashed_password, $role, $branch_id, $photo_path, $last_login);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            session_regenerate_id();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            $_SESSION["branch_id"] = $branch_id;
                            $_SESSION["photo_path"] = $photo_path;
                            $_SESSION["last_login"] = $last_login;

                            // Handle "Remember Me"
                            if(isset($_POST["remember"])) {
                                // Set cookie for 30 days
                                setcookie("remember_user", $username, time() + (86400 * 30), "/"); 
                            } else {
                                // Unset cookie
                                if(isset($_COOKIE["remember_user"])) {
                                    setcookie("remember_user", "", time() - 3600, "/");
                                }
                            }

                            $current_time = date("Y-m-d H:i:s");
                            $update_stmt = $mysqli->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $current_time, $id);
                            $update_stmt->execute();
                            $update_stmt->close();
                            
                            header("location: dashboard.php");
                            exit;
                        } else{
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - <?php echo htmlspecialchars($company_details['name'] ?? 'TMS'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen bg-black bg-opacity-50">
        <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-2xl shadow-2xl">
            <div class="text-center">
                <?php if(!empty($company_details['logo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($company_details['logo_path']); ?>" alt="Company Logo" class="h-20 mx-auto mb-4">
                <?php endif; ?>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    <?php echo htmlspecialchars($company_details['name'] ?? 'Sign in to your account'); ?>
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Transport Management System
                </p>
            </div>
            
            <?php 
            if(!empty($login_err)){
                echo '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">' . $login_err . '</div>';
            }        
            ?>

            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="username" class="sr-only">Username</label>
                        <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($username); ?>" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Username">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" <?php if(!empty($username)) echo "checked"; ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
