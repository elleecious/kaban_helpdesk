<?php
// keep however you already set $role; this is only a fallback
$role    = $role ?? ($_SESSION['role'] ?? '');
$current = basename($_SERVER['PHP_SELF']);

// [icon, label, page, optional element id]
switch (true) {
    case in_array($role, ['IT Manager', 'Administrator'], true):
        $home  = 'manager_dashboard.php';
        $links = [
            ['fa-tachometer-alt', 'Dashboard', 'manager_dashboard.php'],
            ['fa-tags', 'All Tickets', 'all_tickets.php'],
            ['fa fa-users', 'Manage Users', 'manage_users.php'],
            ['fa fa-book', 'Manage Category', 'manage_category.php'],
            ['fa fa-file-contract', 'Manage SLAs', 'manage_sla.php'],
            ['fa-chart-line', 'Reports', 'full_reports.php'],
            ['fa fa-gauge-high', 'KPI', 'kpi.php'],
            ['fa-exchange-alt', 'Change Request', 'manage_change_request.php'],
            ['fa-server', 'Server Health', 'server_health_monitor.php'],
            ['fa-history', 'Logs', 'logs.php'],
        ];
        break;
    case $role === 'IT Supervisor':
        $home  = 'supervisor_dashboard.php';
        $links = [
            ['fa-tachometer-alt', 'Dashboard', 'supervisor_dashboard.php'],
            ['fa-chart-line',     'Reports',   'full_reports.php'],
            ['fa-tags', 'All Tickets', 'all_tickets.php'],
            ['fa-exchange-alt', 'Change Request', 'manage_change_request.php'],
            ['fa fa-gauge-high', 'KPI', 'kpi.php'],
        ];
        break;
    case in_array($role, ['IT Support Specialist', 'IT Helpdesk Support', 'System Administrator'], true):
        $home  = 'support_dashboard.php';
        $links = [
            ['fa-tachometer-alt', 'Dashboard', 'support_dashboard.php'],
            ['fa-ticket-alt',   'All Tickets',    'all_tickets.php'],
            ['fa-exchange-alt', 'Change Request', 'manage_change_request.php'],
        ];
        break;
    default:
        $home  = 'employee_dashboard.php';
        $links = [
            ['fa-tachometer-alt', 'Dashboard', 'employee_dashboard.php'],
            ['fa-plus-circle', 'New Ticket', 'create_ticket.php', 'nav_new_ticket'],
            ['fa-list', 'My Tickets', 'view_all_tickets.php'],
        ];
        if ($role === 'General Manager') {
            $links[] = ['fa-exchange-alt', 'Change Request', 'manage_change_request.php'];
        }
}
?>
<header>
    <!-- Top bar -->
    <nav class="navbar fixed-top navbar-expand navbar-dark double-nav kd-navbar">
        <div class="float-left">
            <a href="#" data-activates="slide-out" class="button-collapse kd-menu-btn"
               role="button" aria-label="Open menu"><i class="fas fa-bars"></i></a>
        </div>
        <a class="navbar-brand ml-2" href="<?= $home ?>">KabanDesk</a>

        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item dropdown" id="notificationNavContainer">
                <a class="nav-link dropdown-toggle kd-notif" id="navbarDropdownMenuLink" href="#" role="button"
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell mr-2"></i><span class="mr-2 d-none d-md-inline">Notifications</span>
                    <span id="unreadBadgeContainer"></span>
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow-5 notification-dropdown-menu mt-2"
                     aria-labelledby="navbarDropdownMenuLink">
                    <div class="d-flex justify-content-between align-items-center p-3 px-4 border-bottom">
                        <h6 class="mb-0 font-weight-bold text-dark">Notifications</h6>
                        <a href="javascript:void(0);" id="markAllReadBtn" class="small text-primary font-weight-bold" style="display: none;">Mark all read</a>
                    </div>

                    <div class="d-flex px-4 py-2 border-bottom">
                        <button type="button" class="notif-tab btn btn-sm btn-outline-info rounded-pill mr-2 active" data-filter="all">All</button>
                        <button type="button" class="notif-tab btn btn-sm btn-outline-info rounded-pill" data-filter="unread">Unread</button>
                    </div>

                    <div id="notificationList" class="notification-list" style="max-height: 440px; overflow-y: auto;">
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                        </div>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="accountDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="My Account">
                    <!-- Avatar User Image -->
                    <img src="assets/img/account-avatar-profile-user.svg" alt="User Avatar" class="rounded-circle mr-1" width="28" height="28" style="object-fit: cover;">
                    <span class="d-none d-lg-inline ml-1"><?php echo $username; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                    <a class="dropdown-item" id="nightMode">
                        <span class="fa fa-moon fa-fw fa-lg mr-2"></span>
                        Dark Mode
                    </a>
                    <a class="dropdown-item <?= $current === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                        <i class="fas fa-user-circle fa-fw fa-lg mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="#" id="btnLogout">
                        <i class="fas fa-power-off fa-fw fa-lg mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <div id="slide-out" class="side-nav kd-sidenav">
        <ul class="custom-scrollbar">
            <li>
                <div class="logo-wrapper waves-light">
                    <a href="<?= $home ?>">
                        <img src="assets/img/kaban-logo-white.png" class="img-fluid"
                             alt="Kaban Hotel and Casino Boracay">
                    </a>
                </div>
            </li>
            <div><p style="font-size: 1rem;" class="text-center mt-3"><?php echo $name; ?></p></div>
            <li>
                <ul class="collapsible collapsible-accordion">
                    <?php foreach ($links as $l): ?>
                    <li>
                        <a href="<?= $l[2] ?>"<?= isset($l[3]) ? ' id="' . $l[3] . '"' : '' ?>
                           class="collapsible-header waves-effect <?= $current === $l[2] ? 'active' : '' ?>">
                            <i class="fas <?= $l[0] ?> fa-fw"></i><?= $l[1] ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        </ul>
        <div class="sidenav-bg"></div>
    </div>
</header>