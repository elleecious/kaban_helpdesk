<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php

if (!isset($_GET['id'])) {
    header("location: index.php");
}

$get_kba = retrieve("SELECT kba.id AS kba_id, kba.article_number, users.name AS name, users.role AS role, cat.name AS category, kba.title AS title, kba.body, kba.created_at AS kba_created FROM kb_articles AS kba 
            INNER JOIN categories AS cat ON kba.category_id=cat.id 
            INNER JOIN users AS users ON kba.created_by=users.id WHERE kba.id=?", array($_GET['id']));

?>
<div class="container mt-5" style="max-width: 1080px;">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="text-muted mt-5 display-5">Article #<?= htmlspecialchars($get_kba[0]['article_number']); ?></h4>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8 mt-3">

        <div class="card mb-3">
            <div class="card-body">
                <h4 class="mb-3 fw-bold">Subject: <?= $get_kba[0]['title']; ?></h4>
                <p class="card-eyebrow mb-2">Body</p>
                <p class="card-text"><?= nl2br(htmlspecialchars($get_kba[0]['body'])); ?></p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <p class="card-eyebrow mb-2">Posted by</p>
                <span class="font-weight-bold"><?= $get_kba[0]['name'] ?? 'Unknown'; ?></span><br>
                <span class="text-muted"><?= $get_kba[0]['role'] ?? 'Role' ?></span>
            </div>
      </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>    