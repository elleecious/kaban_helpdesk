(function ($) {
  const CFG = {
  idleMs: 59 * 60 * 1000,
  countdownSec: 60,
  keepAliveUrl: './api/keepalive.php',
  logoutUrl: './api/logout.php'
};

  let idleTimer, countdownTimer, remaining, modal, warning = false;

  const html = `
  <div class="modal fade" id="sessionModal" tabindex="-1" data-mdb-backdrop="static" data-mdb-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Session Inactivity</h5>
        </div>
        <div class="modal-body">
          <p>For your security, you will automatically be signed out due to inactivity.</p>
          <p>To extend your session, select <strong>Continue</strong>.</p>
          <p class="text-muted small mb-0">Signing out in <span id="sessionCountdown"></span>s</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" id="sessionSignOut">Sign Out</button>
          <button type="button" class="btn btn-dark btn-sm" id="sessionContinue">Continue</button>
        </div>
      </div>
    </div>
  </div>`;

    function resetIdle() {
        if (warning) return;                       // ignore activity once the warning is up
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showWarning, CFG.idleMs);
        localStorage.setItem('kd_last_activity', Date.now());   // sync across tabs
    }
    
    function showWarning() {
        // another tab may have been active recently
        const last = +localStorage.getItem('kd_last_activity') || 0;
        if (Date.now() - last < CFG.idleMs - 1000) return resetIdle();

        warning = true;
        remaining = CFG.countdownSec;
        $('#sessionCountdown').text(remaining);
        modal.show();
        countdownTimer = setInterval(() => {
        $('#sessionCountdown').text(--remaining);
        if (remaining <= 0) signOut();
        }, 1000);
    }

    function extend() {
        $.getJSON(CFG.keepAliveUrl)
        .done(() => {
            clearInterval(countdownTimer);
            warning = false;
            modal.hide();
            resetIdle();
        })
        .fail(signOut(true));                          // server says session already dead
    }


    function signOut(auto) {
        clearInterval(countdownTimer);
        const isAuto = auto === true;
        $.getJSON(isAuto ? CFG.logoutUrl + '?reason=timeout' : CFG.logoutUrl)
            .always(() => {
            window.location.href = isAuto ? './index.php?timeout=1' : './index.php';
            });
    }


  $(function () {
    $('body').append(html);
    modal = new mdb.Modal(document.getElementById('sessionModal'));
    $('#sessionContinue').on('click', extend);
    $('#sessionSignOut').on('click', signOut);

    $(document).on('mousemove keydown click scroll touchstart', resetIdle);
    resetIdle();
  });
})(jQuery);