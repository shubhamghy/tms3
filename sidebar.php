<?php
// This check is to prevent errors in case sidebar is loaded standalone
if (file_exists("config.php")) {
    require_once "config.php";
    $company_details = $mysqli->query("SELECT name, logo_path FROM company_details WHERE id = 1")->fetch_assoc();
}
?>
<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 bg-gray-800 text-white w-64 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-200 ease-in-out z-30 flex flex-col">
    <!-- Header Section -->
    <div>
        <!-- Close button (mobile only) -->
        <div class="flex justify-end p-2 md:hidden">
            <button id="close-sidebar-btn" class="text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <!-- Company Info -->
        <div class="flex items-center justify-center h-20 border-b border-gray-700 px-4">
            <?php if(!empty($company_details['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($company_details['logo_path']); ?>" alt="Logo" class="h-12 mr-3">
            <?php endif; ?>
            <span class="font-bold text-lg truncate"><?php echo htmlspecialchars($company_details['name'] ?? 'TMS'); ?></span>
        </div>
        <!-- User Profile -->
        <div class="flex items-center p-4 mt-4">
            <?php if(!empty($_SESSION['photo_path'])): ?>
                <img src="<?php echo htmlspecialchars($_SESSION['photo_path']); ?>" alt="User" class="h-12 w-12 rounded-full mr-4 object-cover">
            <?php else: ?>
                <span class="h-12 w-12 rounded-full bg-gray-700 flex items-center justify-center mr-4">
                    <i class="fas fa-user text-2xl text-gray-400"></i>
                </span>
            <?php endif; ?>
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                <p class="text-xs text-gray-400">
                    Last login: <?php echo isset($_SESSION['last_login']) && $_SESSION['last_login'] ? date("d M, Y h:i A", strtotime($_SESSION['last_login'])) : 'First login'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-2 py-4 overflow-y-auto">
        <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $user_role = $_SESSION['role'] ?? '';
            $can_manage = in_array($user_role, ['admin', 'manager']);
            $is_admin = ($user_role === 'admin');
            
            $is_fleet_page = in_array($current_page, ['manage_fuel_logs.php', 'manage_maintenance.php', 'manage_tyres.php']);
            $is_accounting_page = in_array($current_page, ['manage_expenses.php', 'accounts_ledger.php', 'reports.php', 'reports_ar_aging.php']);
            // MODIFIED: Added manage_reconciliation.php
            $is_billing_page = in_array($current_page, ['manage_payments.php', 'manage_invoices.php', 'view_invoices.php', 'manage_reconciliation.php', 'unbilled_consignments.php', 'vehicle_settlements.php']);
            $is_manage_page = in_array($current_page, ['manage_parties.php', 'manage_brokers.php', 'manage_drivers.php', 'manage_vehicles.php', 'manage_locations.php', 'manage_branches.php', 'manage_users.php', 'manage_company.php']);
        ?>
        <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-md <?php echo ($current_page == 'dashboard.php') ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-tachometer-alt fa-fw mr-3"></i> Dashboard</a>
        <a href="booking.php" class="flex items-center px-4 py-2 mt-2 rounded-md <?php echo ($current_page == 'booking.php') ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-plus-circle fa-fw mr-3"></i> New Booking</a>
        <a href="view_bookings.php" class="flex items-center px-4 py-2 mt-2 rounded-md <?php echo ($current_page == 'view_bookings.php') ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-book-open fa-fw mr-3"></i> View Bookings</a>
        <a href="update_tracking.php" class="flex items-center px-4 py-2 mt-2 rounded-md <?php echo ($current_page == 'update_tracking.php') ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-truck-loading mr-3"></i> Update Tracking</a>
        <a href="manage_pod.php" class="flex items-center px-4 py-2 mt-2 rounded-md <?php echo ($current_page == 'manage_pod.php') ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-file-signature fa-fw mr-3"></i> Manage POD</a>
        
        <?php if ($can_manage): ?>
        
         <!-- Billing & Invoicing Dropdown -->
        <div>
            <button id="billing-dropdown-btn" class="w-full flex items-center justify-between px-4 py-2 mt-2 rounded-md <?php echo $is_billing_page ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                <span><i class="fas fa-calculator fa-fw mr-3"></i> Billing & Invoicing</span>
                <i id="billing-dropdown-icon" class="fas fa-chevron-down transition-transform <?php echo $is_billing_page ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="billing-dropdown-menu" class="pl-4 mt-2 space-y-2 <?php echo $is_billing_page ? '' : 'hidden'; ?>">
                <a href="manage_payments.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_payments.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-file-invoice-dollar fa-fw mr-3"></i> Manage Payments</a>
                <a href="manage_invoices.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_invoices.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-file-alt fa-fw mr-3"></i> Generate Invoice</a>
                <a href="view_invoices.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'view_invoices.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-list-alt fa-fw mr-3"></i> View Invoices</a>
                <!-- NEW NAVIGATION LINK -->
                <a href="manage_reconciliation.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_reconciliation.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-hand-holding-dollar fa-fw mr-3"></i> Reconciliation</a>
                 <a href="unbilled_consignments.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'unbilled_consignments.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-file-invoice mr-3"></i> Unbilled Report</a>
                <a href="vehicle_settlements.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'vehicle_settlements.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-truck mr-3"></i> Vehicle Settlements</a>
           
            </div>
        </div>

        <!-- Accounting Dropdown -->
        <div>
            <button id="accounting-dropdown-btn" class="w-full flex items-center justify-between px-4 py-2 mt-2 rounded-md <?php echo $is_accounting_page ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                <span><i class="fas fa-coins fa-fw mr-3"></i> Accounting</span>
                <i id="accounting-dropdown-icon" class="fas fa-chevron-down transition-transform <?php echo $is_accounting_page ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="accounting-dropdown-menu" class="pl-4 mt-2 space-y-2 <?php echo $is_accounting_page ? '' : 'hidden'; ?>">
                <a href="manage_expenses.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_expenses.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-receipt fa-fw mr-3"></i> Manage Expenses</a>
                <a href="accounts_ledger.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'accounts_ledger.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-book fa-fw mr-3"></i> Party Ledger</a>
                <a href="reports.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'reports.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-chart-line fa-fw mr-3"></i> P&L Report</a>
                <a href="reports_ar_aging.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'reports_ar_aging.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-chart-line fa-fw mr-3"></i> A/R Aging Report</a>
                
            </div>
        </div>
        
        <!-- Fleet Management Dropdown -->
        <div>
            <button id="fleet-dropdown-btn" class="w-full flex items-center justify-between px-4 py-2 mt-2 rounded-md <?php echo $is_fleet_page ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                <span><i class="fas fa-truck-moving fa-fw mr-3"></i> Fleet Management</span>
                <i id="fleet-dropdown-icon" class="fas fa-chevron-down transition-transform <?php echo $is_fleet_page ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="fleet-dropdown-menu" class="pl-4 mt-2 space-y-2 <?php echo $is_fleet_page ? '' : 'hidden'; ?>">
                <a href="manage_fuel_logs.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_fuel_logs.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-gas-pump fa-fw mr-3"></i> Fuel Logs</a>
                <a href="manage_maintenance.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_maintenance.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-tools fa-fw mr-3"></i> Maintenance</a>
                <a href="manage_tyres.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_tyres.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-dot-circle fa-fw mr-3"></i> Tyre Management</a>
            </div>
        </div>
        

       
        <?php endif; ?>

        <!-- Manage Dropdown -->
        <div>
            <button id="manage-dropdown-btn" class="w-full flex items-center justify-between px-4 py-2 mt-2 rounded-md <?php echo $is_manage_page ? 'text-white bg-gray-700' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                <span><i class="fas fa-cogs fa-fw mr-3"></i> Manage</span>
                <i id="manage-dropdown-icon" class="fas fa-chevron-down transition-transform <?php echo $is_manage_page ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="manage-dropdown-menu" class="pl-4 mt-2 space-y-2 <?php echo $is_manage_page ? '' : 'hidden'; ?>">
                <a href="manage_locations.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_locations.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-map-marker-alt mr-3"></i> Locations</a>
                <a href="manage_parties.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_parties.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-users fa-fw mr-3"></i> Parties</a>
                <a href="manage_brokers.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_brokers.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-handshake fa-fw mr-3"></i> Brokers</a>
                <a href="manage_drivers.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_drivers.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-id-card fa-fw mr-3"></i> Drivers</a>
                <a href="manage_vehicles.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_vehicles.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-truck fa-fw mr-3"></i> Vehicles</a>
                <?php if ($is_admin): ?>
                <a href="manage_branches.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_branches.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-code-branch fa-fw mr-3"></i> Branches</a>
                <a href="manage_users.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_users.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-user-cog fa-fw mr-3"></i> Users</a>
                <a href="manage_company.php" class="flex items-center px-4 py-2 text-sm rounded-md <?php echo ($current_page == 'manage_company.php') ? 'text-white bg-gray-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>"><i class="fas fa-building fa-fw mr-3"></i> Company Details</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupDropdown(btnId, menuId, iconId) {
                const btn = document.getElementById(btnId);
                const menu = document.getElementById(menuId);
                const icon = document.getElementById(iconId);
                if (btn) {
                    btn.addEventListener('click', function() {
                        menu.classList.toggle('hidden');
                        icon.classList.toggle('rotate-180');
                    });
                }
            }
            setupDropdown('fleet-dropdown-btn', 'fleet-dropdown-menu', 'fleet-dropdown-icon');
            setupDropdown('accounting-dropdown-btn', 'accounting-dropdown-menu', 'accounting-dropdown-icon');
            setupDropdown('billing-dropdown-btn', 'billing-dropdown-menu', 'billing-dropdown-icon');
            setupDropdown('manage-dropdown-btn', 'manage-dropdown-menu', 'manage-dropdown-icon');
        });
    </script>
</div>
