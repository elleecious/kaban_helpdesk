<?php include('includes/header.php'); ?>
<?php include('library/password_reset.php'); ?>
<?php include('includes/navbar.php'); ?>
<?php $page_title = "KabanDesk"; ?>
<?php
$token = $_GET['token'] ?? '';
$resetRow = $token ? verify_reset_token($token) : false;
?>
<section>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 mt-5">
                <div class="card">
                    <div class="card-header p-3 white-text kaban-color">
                        Reset Password
                    </div>
                    <div class="card-body mt-3">
                        <div class="row">
                            <div class="col-md-12 mx-auto">
                                <div id="form-container">
                                    <?php if (!$resetRow): ?>
                                        <p>This password reset link is invalid or has expired. Please request a new one.</p>
                                    <?php else: ?>
                                        <h2>Reset Your Password</h2>

                                        <form id="resetForm">
                                            <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">

                                            <label>New Password</label>
                                            <input type="password" id="password" required minlength="8">

                                            <label>Confirm Password</label>
                                            <input type="password" id="confirm_password" required minlength="8">

                                            <button type="submit">Reset Password</button>
                                        </form>
                                    <?php endif; ?>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>

</section>
<?php include('includes/footer.php'); ?>