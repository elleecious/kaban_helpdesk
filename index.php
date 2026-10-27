<?php $page_title = "KabanDesk"; ?>
<?php include('includes/header.php') ?>
<link rel="stylesheet" href="assets/css/login.css">

<div class="bg-image login-page">
    <div class="mask"></div>

    <main class="login-shell">
        <section class="login-panel" aria-labelledby="loginTitle">
            <header class="login-brand">
                <img src="assets/img/kaban-logo-white.png" alt="Kaban Hotel and Casino Boracay">
                <h1 id="loginTitle">KabanDesk</h1>
                <p>Every issue tracked. Every guest experience protected.</p>
            </header>
            <form method="post" autocomplete="on" id="formLogin" novalidate>
                <div class="kd-alert" id="loginAlert" role="alert" hidden></div>
                <?php if (isset($_GET['timeout'])): ?>
                <div class="session-notice mb-4" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Your session timed out.</strong>
                    </div>
                    <div class="small">Your session has expired due to prolonged inactivity.</div>
                </div>
                <?php endif; ?>
                <div class="kd-field">
                    <label for="email">Email address</label>
                    <div class="kd-control">
                        <i class="fa fa-envelope kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default" type="email" name="email" id="email"
                               placeholder="name@kabanboracay.com" autocomplete="username" required>
                    </div>
                </div>

                <div class="kd-field">
                    <label for="password">Password</label>
                    <div class="kd-control">
                        <i class="fa fa-lock kd-icon" aria-hidden="true"></i>
                        <input class="kd-input browser-default has-toggle" type="password" name="password" id="password"
                               placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="kd-toggle" id="togglePassword"
                                aria-label="Show password" aria-pressed="false">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="kd-hint" id="capsHint" hidden>Caps Lock is on.</p>
                </div>

                <button class="kd-submit" type="submit" name="btnLogin" id="btnLogin">Log in</button>
            </form>

            <p class="login-help"><a class="white-text" href="register.php">Don't have an account yet? Register</a></p>
        </section>
    </main>
</div>
<?php include('includes/footer.php') ?>
<script>
$(document).ready(function () {

    var $email = $('#email'),
        $pw = $('#password'),
        $toggle = $('#togglePassword'),
        $caps = $('#capsHint'),
        $form = $('#formLogin'),
        $submit = $('#btnLogin');

    // Focus email field on page load
    $email.focus();

    // Toggle password visibility
    $toggle.on('click', function () {
        var show = $pw.attr('type') === 'password';

        $pw.attr('type', show ? 'text' : 'password');
        $toggle
            .attr('aria-pressed', show)
            .attr('aria-label', show ? 'Hide password' : 'Show password');

        $toggle
            .children()
            .first()
            .attr('class', show ? 'fa fa-eye-slash' : 'fa fa-eye');

        $pw.focus();
    });

    // Check Caps Lock
    function checkCaps(e) {
        var capsOn =
            e.originalEvent &&
            e.originalEvent.getModifierState &&
            e.originalEvent.getModifierState('CapsLock');

        $caps.prop('hidden', !capsOn);
    }

    $pw.on('keydown keyup', checkCaps);

    // Hide Caps Lock hint when leaving password field
    $pw.on('blur', function () {
        $caps.prop('hidden', true);
    });

    // Show loading state when submitting
    $form.on('submit', function () {
        $submit
            .addClass('is-loading')
            .attr('aria-busy', 'true');
    });

    // Remove loading state after AJAX request completes
    $(document).ajaxComplete(function () {
        $submit
            .removeClass('is-loading')
            .removeAttr('aria-busy');
    });

});
</script>