<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>
<style>
.status-pill::before{ content:''; width:10px; height:10px; border-radius:50%; background:currentColor; }
.st-draft{ background:#f1eff4; color:#6b6377; }
.st-pending{ background:#fbf1dd; color:#8a6212; }
.st-approved{ background:#e6f2ea; color:#1e6b3e; }
.st-rejected{ background:#fbe9e9; color:#a3282c; }
.st-done{ background:#e9edfb; color:#2c3f9e; }
</style>
<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mt-5">
            <div class="row">
                <h3 class="text-center">All of your Change Request</h3>
            </div>
            <hr>
            <section>
                <div class="row">
                    <?php
                        $getAllChangeRequest = retrieve("SELECT cr.id AS crf_id, cr.crf_number, cr.change_title, cr.department, cr.change_type, 
                                                        u.name, cr.status, cr.created_at
                                                    FROM change_requests AS cr
                                                    INNER JOIN users AS u ON cr.requestor_id = u.id
                                                    WHERE cr.requestor_id=?
                                                    ORDER BY cr.created_at DESC",array($login_id));
                        $cr=$getAllChangeRequest;
                        $status = $cr['status'] ?? 'Submitted';
                        $statusClasses = [
                            'Draft'=> 'st-draft','Submitted' => 'st-pending',
                            'Under Review' => 'st-pending','Approved' => 'st-approved',
                            'Rejected' => 'st-rejected','Scheduled' => 'st-approved',
                            'Implementing' => 'st-approved','Implemented' => 'st-done',
                            'Reviewed' => 'st-done','Closed' => 'st-done',
                        ];
                        $statusClass = $statusClasses[$status] ?? 'st-pending';
                        function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }

                        if ($getAllChangeRequest) {

                            for ($i=0; $i < count($getAllChangeRequest); $i++) { 
                                echo "
                                    <div class='col-md-12'>
                                        <div class='card'>
                                            <div class='card-body'>
                                                <h5 class='card-title'>".$getAllChangeRequest[$i]['change_title']."</h5>
                                                <h2><span class='badge badge-info'>".$getAllChangeRequest[$i]['change_type']."</span></h2><br><br>
                                                <a class='text-primary' href='view_change_request.php?id=".$getAllChangeRequest[$i]['crf_id']."'>View Change Request</a>
                                            </div>
                                        </div>
                                    </div>
                                ";
                            }
                        } else {
                             echo "<div class='col-md-12'>
                                        <div class='card'>
                                            <div class='card-body text-center'>
                                                <h3 class='card-title'>No Change Request</h3>
                                                <p class='text-muted'>You haven't submitted any tickets yet.</p>
                                            </div>
                                        </div>
                                    </div>";
                        }
                    ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>