<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php
if (empty($_SESSION['login_id']) || !in_array($_SESSION['role'] ?? '', ['IT Support Specialist','IT Helpdesk Support','System Administrator','IT Supervisor', 'IT Manager', 'Administrator'])) {
    header('Location: index.php');
    exit;
}

$period    = $_GET['period'] ?? 'CUSTOM';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');
$category  = $_GET['category'] ?? 'ALL';
$status    = $_GET['status'] ?? 'ALL';

switch ($period) {
    case 'DAILY':
        $date_from = date('Y-m-d');
        $date_to   = date('Y-m-d');
        break;

    case 'WEEKLY':
        // Full week: Monday to Sunday
        $day_of_week  = (int)date('N'); // 1 (Mon) - 7 (Sun)
        $offset_start = $day_of_week - 1; // Days back to Monday
        $offset_end   = 7 - $day_of_week; // Days forward to Sunday

        $date_from = date('Y-m-d', strtotime("-{$offset_start} days"));
        $date_to = date('Y-m-d', strtotime("+{$offset_end} days"));
        break;

    case 'MONTHLY':
        $date_from = date('Y-m-01');
        $date_to   = date('Y-m-t');
        break;

    case 'YEARLY':
        $date_from = date('Y-01-01');
        $date_to   = date('Y-12-31');
        break;

    case 'CUSTOM':
    default:
        break;
}

$categories = retrieve("SELECT * FROM categories ORDER BY name", array());
$statuses   = ['Open', 'In Progress', 'Resolved', 'Closed'];

// 3. Initialize base query and bindings array (Your original WHERE builder)
$where_clauses = [];
$params = [];

// Date range filter
if (!empty($date_from)) {
    $where_clauses[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

// Category filter
if ($category !== 'ALL' && !empty($category)) {
    $where_clauses[] = "t.category_id = ?";
    $params[] = $category;
}

// Status filter
if ($status !== 'ALL' && !empty($status)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $status;
}

// Construct WHERE string dynamically
$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

?>
<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row mt-5">
                <div class="col-md-12 mb-2">
                    <div class="card">
                        <div class="card-header p-3 kaban-color text-white">
                            All Tickets
                        </div>
                        <div class="card-body">
                            <!--Filters-->
                            <form method="get" class="filter-bar p-3 mb-4 row g-2 align-items-end">
                                <div class="col-12 col-md-2">
                                    <label class="form-label small mb-1">Period / Range</label>
                                    <select name="period" id="periodSelect" class="form-control form-control-sm">
                                        <option value="CUSTOM" <?= $period === 'CUSTOM' ? 'selected' : '' ?>>Custom Range / Search</option>
                                        <option value="DAILY" <?= $period === 'DAILY' ? 'selected' : '' ?>>Daily (Today)</option>
                                        <option value="WEEKLY" <?= $period === 'WEEKLY' ? 'selected' : '' ?>>Weekly (Mon-Sun)</option>
                                        <option value="MONTHLY" <?= $period === 'MONTHLY' ? 'selected' : '' ?>>Monthly (This Month)</option>
                                        <option value="YEARLY" <?= $period === 'YEARLY' ? 'selected' : '' ?>>Yearly (This Year)</option>
                                    </select>
                                </div>

                                <!-- Date From Group -->
                                <div class="col-6 col-md-2 date-range-group" id="dateFromGroup">
                                    <label class="form-label small mb-1">From</label>
                                    <input type="date" name="date_from" id="dateFromInput" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                                </div>

                                <!-- Date To Group -->
                                <div class="col-6 col-md-2 date-range-group" id="dateToGroup">
                                    <label class="form-label small mb-1">To</label>
                                    <input type="date" name="date_to" id="dateToInput" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                                </div>
                                <!-- Ticket Category -->
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Category</label>
                                    <select name="category" class="form-control form-control-sm">
                                        <option value="ALL" <?= $category === 'ALL' ? 'selected' : '' ?>>All Categories</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= (string)$category === (string)$c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Ticket Status -->
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Status</label>
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="ALL" <?= $status === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-6 col-md-1">
                                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i>Apply</button>
                                </div>
                                <div class="col-6 col-md-1">
                                    <a href="reports.php" class="btn btn-secondary btn-sm w-100">Reset</a>
                                </div>
                            </form>
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-striped table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblAllTickets">
                                        <thead>
                                            <tr>
                                                <?php
                                                    $stud_head=explode(",","Ticket Number,Subject,Category,Priority,Status,SLA, Requested By, Assigned To, Date Created,Action");
                                                    foreach($stud_head as $stud_val)
                                                    {
                                                        echo "<th>".$stud_val."</th>";
                                                    }
                                                ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $sql = "SELECT t.id,
                                                        t.ticket_number,
                                                        t.subject, cat.name AS category,
                                                        req.name AS employee, agent.name AS it_support,
                                                        t.status, t.priority, t.resolution_due_at,
                                                        CASE
                                                            WHEN t.status = 'Deleted' THEN 'Canceled'
                                                            WHEN t.status IN ('Resolved', 'Closed') THEN 'Complete'
                                                            WHEN t.resolution_due_at < NOW() THEN 'Breached'
                                                            WHEN t.resolution_due_at <= DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 'Warning'
                                                            ELSE 'On Track'
                                                        END AS sla_status,
                                                        t.created_at
                                                    FROM tickets t
                                                    LEFT JOIN categories cat ON t.category_id = cat.id
                                                    LEFT JOIN users req ON t.created_by = req.id
                                                    LEFT JOIN users agent ON t.assigned_to = agent.id
                                                    {$where_sql}
                                                    ORDER BY t.created_at DESC;";

                                                $all_tickets = retrieve($sql, $params);
                                                foreach ($all_tickets as $tickets) {
                                                    echo "<tr>
                                                        <td>".$tickets['ticket_number']."</td>
                                                        <td>".$tickets['subject']."</td>
                                                        <td>".$tickets['category']."</td>
                                                        <td>".$tickets['priority']."</td>
                                                        <td>".$tickets['status']."</td>
                                                        <td>".$tickets['sla_status']."</td>
                                                        <td>".$tickets['employee']."</td>
                                                        <td>".(($tickets['it_support'] == true) ? $tickets['it_support'] : 'Unknown')."</td>
                                                        <td>".$tickets['created_at']."</td>
                                                        <td>
                                                            <a href='ticket_detail.php?id=".$tickets['id']."'><span class='btn btn-outline-info btn-md'>View</span></a>
                                                        </td>
                                                    </tr>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
    $("#tblAllTickets").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":10,
		"order": [],
	});

    // Dynamic visibility toggle for date range inputs
    function toggleDateInputs() {
        var selectedPeriod = $("#periodSelect").val();
        if (selectedPeriod === "CUSTOM") {
            $("#dateFromGroup, #dateToGroup").fadeIn();
        } else {
            $("#dateFromGroup, #dateToGroup").fadeOut();
        }
    }

    // 1. Run on page load (preserves visibility if CUSTOM was applied)
    toggleDateInputs();

    // 2. Run whenever user changes the dropdown selection
    $("#periodSelect").change(function () {
        toggleDateInputs();
    });
});
</script>