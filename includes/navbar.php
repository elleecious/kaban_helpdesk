<nav class="mb-1 navbar navbar-expand-lg navbar-dark" style="background-color: #431765;">
    <a class="navbar-brand" href="#">
        Kaban Helpdesk
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
                <a class="nav-link">
                    <span class="fa fa-tags fa-lg hvr-pop text-white"></span>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-file fa-lg hvr-pop text-white"></span>
                    <span>Admin Settings</span>
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
                <a class="nav-link">
                    <span class="fa fa-tags fa-lg hvr-pop text-white"></span>
                    <span>All Tickets</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-file fa-lg hvr-pop text-white"></span>
                    <span>Reports</span>
                </a>
            </li>
            <?php } else if ($role == "IT Support Specialist") { ?>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-users-line fa-lg hvr-pop text-white"></span>
                    <span>My Queue</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link">
                    <span class="fa fa-tags fa-lg hvr-pop text-white"></span>
                    <span>All Tickets</span>
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
                    <a class="nav-link">
                        <span class="fa fa-moon fa-lg hvr-pop text-white"></span>
                        <span>My Tickets</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link">
                        <span class="fa fa-book-open fa-lg hvr-pop text-white"></span>
                        <span>Knowledge Base</span>
                    </a>
                </li>
            <?php } ?>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <span class="fa fa-user-circle fa-xl hvr-pop text-white"></span>
                    <span>Profile</span>
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