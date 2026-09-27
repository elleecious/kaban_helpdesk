<nav class="mb-1 navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #431765;">
    <a class="navbar-brand" href="#">
        KabanDesk
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#basicExampleNav"
    aria-controls="basicExampleNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    </button>
    <div class="collapse navbar-collapse" id="basicExampleNav">
        <ul class="navbar-nav mr-auto">
            <?php
                if($role == "IT Manager") {
            ?>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-dashboard fa-lg hvr-pop text-white"></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="full_reports.php">
                    <span class="fa fa-tags fa-lg hvr-pop text-white"></span>
                    <span>Reports</span>
                </a>
            </li>
            <?php } else if($role == "IT Supervisor") {  ?>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-dashboard fa-lg hvr-pop text-white"></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="full_reports.php">
                    <span class="fa fa-line-chart fa-lg hvr-pop text-white"></span>
                    <span>Reports</span>
                </a>
            </li>
            <?php } else if ($role == "IT Support Specialist") { ?>
            <li class="nav-item">
                <a class="nav-link" href="all_tickets.php">
                    <span class="fa fa-tags fa-lg hvr-pop text-white"></span>
                    <span>All Tickets</span>
                </a>
            </li>
            <li class="nav-item">
               <a class="nav-link" href="manage_change_request.php">
                    <span class="fa fa-exchange-alt fa-lg hvr-pop text-white"></span>
                    <span>Change Request</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-book-open fa-lg hvr-pop text-white"></span>
                    <span>Knowledge Base</span>
                </a>
            </li>
            <?php } else {?>
                <li class="nav-item">
                    <a class="nav-link" href="create_ticket.php" id="nav_new_ticket">
                        <span class="fa fa-tag fa-lg hvr-pop text-white"></span>
                        <span>New ticket</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_all_tickets.php">
                        <span class="fa fa-moon fa-lg hvr-pop text-white"></span>
                        <span>My Tickets</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="knowledge_base_articles.php">
                        <span class="fa fa-book-open fa-lg hvr-pop text-white"></span>
                        <span>Knowledge Base</span>
                    </a>
                </li>
                <?php if( $role == "General Manager" ): ?>
                    <li class="nav-item"></li>
                        <a class="nav-link" href="manage_change_request.php">
                            <span class="fa fa-exchange-alt fa-lg hvr-pop text-white"></span>
                            <span>Change Request</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php } ?>
        </ul>
        <ul class="navbar-nav ml-auto flex-row justify-content-end">
            <li class="nav-item dropdown" id="notificationNavContainer">
                <a 
                    class="nav-link dropdown-toggle waves-effect waves-light d-flex align-items-center bg-white px-3 py-2 rounded-pill z-depth-1 text-dark" 
                    id="navbarDropdownMenuLink" 
                    data-toggle="dropdown" 
                    aria-haspopup="true" 
                    aria-expanded="false"
                >
                    <i class="fas fa-bolt text-dark mr-2"></i>
                    <span class="font-weight-bold mr-2">Notifications</span>
                    <span id="unreadBadgeContainer"></span>
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow-5 notification-dropdown-menu mt-2" 
                    aria-labelledby="navbarDropdownMenuLink">
                    
                    <div class="d-flex justify-content-between align-items-center p-3 px-4 border-bottom">
                        <h6 class="mb-0 font-weight-bold text-dark">Notifications</h6>
                        <a href="javascript:void(0);" id="markAllReadBtn" class="small text-primary font-weight-bold" style="display: none;">Mark all read</a>
                    </div>
                    
                    <div id="notificationList" class="notification-list" style="max-height: 440px; overflow-y: auto;">
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                        </div>
                    </div>

                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <span class="fa fa-user-circle fa-xl hvr-pop text-white"></span>
                    <span>My Account</span>
                </a>
            <li class>
                <a class="nav-link text-white" id="btnLogout">
                    <span class="fas fa-power-off"></span>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>