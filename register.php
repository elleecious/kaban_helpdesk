<?php
$page_title = " KabanDesk";
require_once('library/registration_options.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['reg_csrf'])) { $_SESSION['reg_csrf'] = bin2hex(random_bytes(32)); }
include('includes/header.php');

// $roles = registration_roles();
$departments = registration_departments();
?>
<link rel="stylesheet" href="assets/css/register.css">

<div class="bg-image register-page">
    <div class="mask"></div>

    <div class="register-shell">
        <section class="register-panel" aria-labelledby="registerTitle">
            <header class="register-brand">
                <img src="assets/img/kaban-logo-white.png" alt="Kaban Hotel and Casino Boracay">
                <h1 id="registerTitle">Create your account</h1>
                <p>Register for KabanDesk to submit and track your tickets and requests.</p>
            </header>

            <form method="post" autocomplete="on" id="frmRegister">
                <div class="kd-alert" id="registerAlert" role="alert" hidden></div>

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['reg_csrf'] ?>">
                <div class="d-none" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="kd-field">
                    <label for="name">Full name</label>
                    <div class="kd-control">
                        <i class="fa fa-user kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default" type="text" name="name" id="name"
                               placeholder="Juan Dela Cruz" autocomplete="name" maxlength="100" required>
                    </div>
                </div>

                <div class="kd-field">
                    <label for="email">Email address</label>
                    <div class="kd-control">
                        <i class="fa fa-envelope kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default" type="email" name="email" id="email"
                               placeholder="name@kabanboracay.com" autocomplete="email" required>
                    </div>
                </div>

                <div class="kd-field">
                    <label for="department">Department</label>
                    <div class="kd-control">
                        <i class="fa fa-building kd-icon" aria-hidden="true"></i>
                        <select class="kd-select browser-default" name="department" id="department" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $d): $d = htmlspecialchars($d); ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="kd-row-2">

                    <!-- <div class="kd-field d-none">
                        <label for="role">Role</label>
                        <div class="kd-control">
                            <i class="fa fa-id-badge kd-icon" aria-hidden="true"></i>
                            <select class="kd-select browser-default" name="role" id="role" required>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $r): $r = htmlspecialchars($r); ?>
                                <option value="<?= $r ?>"><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div> -->
                </div>

                <div class="kd-field">
                    <label for="password">Password</label>
                    <div class="kd-control">
                        <i class="fa fa-lock kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default has-toggle" type="password" name="password" id="password"
                               placeholder="Create a password" autocomplete="new-password"
                               minlength="8" maxlength="72" required>
                        <button type="button" class="kd-toggle" data-target="#password"
                                aria-label="Show password" aria-pressed="false">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="kd-field">
                    <label for="confirm_password">Confirm password</label>
                    <div class="kd-control">
                        <i class="fa fa-lock kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default has-toggle" type="password" name="confirm_password" id="confirm_password"
                               placeholder="Re-enter your password" autocomplete="new-password"
                               minlength="8" maxlength="72" required>
                        <button type="button" class="kd-toggle" data-target="#confirm_password"
                                aria-label="Show password" aria-pressed="false">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button class="kd-submit" type="submit" name="btnRegister" id="btnRegister">Register</button>
            </form>

            <p class="register-help"><a class="white-text" href="index.php">Already have an account? Log in</a></p>
        </section>
    </div>
</div>
<?php include('includes/footer.php') ?>
<script>
$(function () {
    var $form = $('#frmRegister'),
        $submit = $('#btnRegister'),
        $alert = $('#registerAlert');

    $('#name').trigger('focus');

    // show / hide password (both fields)
    $('.kd-toggle').on('click', function () {
        var $in = $($(this).data('target')),
            show = $in.attr('type') === 'password';
        $in.attr('type', show ? 'text' : 'password');
        $(this).attr('aria-pressed', show)
            .attr('aria-label', show ? 'Hide password' : 'Show password')
            .children().first().attr('class', show ? 'fa fa-eye-slash' : 'fa fa-eye');
    });

    function showAlert(msg, ok) {
        $alert.toggleClass('is-success', !!ok).text(msg).prop('hidden', false);
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        if ($submit.hasClass('is-loading')) return;

        if ($('#password').val() !== $('#confirm_password').val()) {
            showAlert('Passwords do not match.');
            $('#confirm_password').trigger('focus');
            return;
        }

        $alert.prop('hidden', true);
        $submit.addClass('is-loading').attr('aria-busy', 'true');

        $.ajax({
            url: './api/register_users.php',
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        })
        .done(function (res) {
           if (res.status === 'success') {
                showAlert(res.message, true);
                $form[0].reset();
                $form.find('input, select').prop('disabled', true);
                setTimeout(function () { window.location.href = 'index.php'; }, 2000);
            } else {
                showAlert(res.message || 'Registration failed. Please check your details.');
            }
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
            showAlert(msg);
        })
        .always(function () {
            $submit.removeClass('is-loading').removeAttr('aria-busy');
        });
    });
});
</script>