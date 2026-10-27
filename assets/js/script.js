// Helper function to prevent XSS attacks
const ESC_MAP = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
};

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, c => ESC_MAP[c]);
}

function formatDate(dateString, defaultText = '—') {
    if (!dateString) return defaultText;
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return defaultText;

    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // hour '0' becomes '12'
    const formattedHours = String(hours).padStart(2, '0');

    return `${yyyy}-${mm}-${dd} ${formattedHours}:${minutes}:${seconds} ${ampm}`;
}

function parseLogDate(str) {
    if (!str) return null;

    const m = String(str).trim().match(
        /^(\d{4})-(\d{2})-(\d{2})[ T](\d{1,2}):(\d{2}):(\d{2})\s*([ap]m)?$/i
    );
    if (!m) {
        const d = new Date(str);
        return isNaN(d) ? null : d;
    }

    let hour = parseInt(m[4], 10);
    const meridiem = m[7] ? m[7].toLowerCase() : null;

    if (meridiem === 'pm' && hour < 12) hour += 12;
    if (meridiem === 'am' && hour === 12) hour = 0;

    return new Date(+m[1], +m[2] - 1, +m[3], hour, +m[5], +m[6]);
}

function timeAgo(dateString) {
    const then = parseLogDate(dateString);
    if (!then) return dateString || '';

    const now = new Date();
    const seconds = Math.floor((now - then) / 1000);

    if (seconds < 60)     return 'Just now';   // also covers small clock drift

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60)     return minutes + (minutes === 1 ? ' minute ago' : ' minutes ago');

    const hours = Math.floor(minutes / 60);
    if (hours < 24)       return hours + (hours === 1 ? ' hour ago' : ' hours ago');

    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const startOfThen  = new Date(then.getFullYear(), then.getMonth(), then.getDate());
    const days = Math.round((startOfToday - startOfThen) / 86400000);

    if (days === 1)       return 'Yesterday';
    if (days < 7)         return days + ' days ago';

    return then.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

let overdueTimer = null;
let lastOverdueHtml = null;

function loadOverdueAlert(isInitial = false) {
    clearTimeout(overdueTimer);
    const $container = $("#overdueAlertContainer");

    $.ajax({
        url: "./api/get_overdue_tickets.php",
        type: "GET",
        dataType: "json",
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial && !$container.html().trim()) {
                $container.html('<div class="alert alert-info" role="alert"><span class="fa fa-spinner fa-spin"></span> Checking overdue tickets...</div>');
            }
        },
        success: function (response) {
            let html = '';

            if (response.status === 'success' && Array.isArray(response.tickets) && response.tickets.length > 0) {
                const count = response.tickets.length;
                const parts = response.tickets.map(function (t) {
                    const ticketId = encodeURIComponent(t.id);
                    const ticketNum = esc(t.ticket_number);
                    const priority = esc(t.priority);
                    const subject = esc(t.subject);
                    const breachType = esc(String(t.breach_type || '').toLowerCase());

                    return `<a href="ticket_detail.php?id=${ticketId}" class="alert-link">#${ticketNum}</a> (${priority}, ${subject}, missed ${breachType} SLA)`;
                });

                html = `
                    <div class="alert alert-danger" role="alert">
                        <strong><span class="fa fa-warning"></span> ${count} ticket${count > 1 ? 's are' : ' is'} overdue.</strong> 
                        ${parts.join('; ')}
                    </div>`;
            } else {
                html = `
                    <div class="alert alert-success" role="alert">
                        <strong><span class="fa fa-info-circle"></span> No overdue tickets right now.</strong>
                    </div>`;
            }

            // Update DOM only if content has changed
            if (html !== lastOverdueHtml) {
                $container.html(html);
                lastOverdueHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error("Overdue Alert Fetch Failed:", status, error);
            if (isInitial) {
                $container.html('<div class="alert alert-danger" role="alert">Failed to load overdue ticket alerts.</div>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                overdueTimer = setTimeout(() => loadOverdueAlert(false), 5000);
            }
        }
    });
}

let attentionTimer = null;
let lastAttentionHtml = null;

/** Attention Summary Alert */
function loadAttentionAlert(isInitial = false) {
    clearTimeout(attentionTimer);
    const $container =$("#attentionAlertContainer");

    $.ajax({
        url: "./api/get_attention_summary.php",
        type: "GET",
        dataType: "json",
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            const currentContent = $container.html();
            if (isInitial && (!currentContent || !currentContent.trim())) {
                $container.html('<div class="alert alert-info" role="alert"><span class="fa fa-spinner fa-spin"></span> Checking attention summary...</div>');
            }
        },
        success: function (response) {
            let html = '';
            const unassigned = Number(response.unassigned_count) || 0;
            const escalated = Number(response.escalated_count) || 0;
            const total = unassigned + escalated;

            if (total === 0) {
                html = `
                    <div class="alert alert-success" role="alert">
                        ✅ Nothing needs your attention right now.
                    </div>`;
            } else {
                const parts = [];
                if (unassigned > 0) {
                    parts.push(`${unassigned} unassigned ticket${unassigned > 1 ? 's' : ''} waiting &gt; 1 hour`);
                }
                if (escalated > 0) {
                    parts.push(`${escalated} ticket${escalated > 1 ? 's' : ''} escalated by an agent`);
                }

                html = `
                    <div class="alert alert-warning" role="alert">
                        <strong>${total} ticket${total > 1 ? 's' : ''} need${total > 1 ? '' : 's'} attention:</strong> 
                        ${parts.join(', ')}.
                    </div>`;
            }

            // Update DOM only if content has changed
            if (html !== lastAttentionHtml) {
                $container.html(html);
                lastAttentionHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error("Attention Alert Fetch Failed:", status, error);
            if (isInitial) {
                $container.html('<div class="alert alert-danger" role="alert">Failed to load attention summary.</div>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                attentionTimer = setTimeout(() => loadAttentionAlert(false), 3000);
            }
        }
    });
}


let recentLogsTimer = null;
function getRecentLogs(isInitial = false) {
    clearTimeout(recentLogsTimer);
    $.ajax({
        url: './api/get_recent_logs.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        success: function (res) {
            const $tbody = $('#recentActivityLogs').empty();

            if (res.status !== 'success' || !Array.isArray(res.data) || res.data.length === 0) {
                $tbody.append(
                    $('<tr>').append(
                        $('<td>', { colspan: 3, class: 'text-center text-muted py-3' })
                            .text('No recent activity')
                    )
                );
                return;
            }

            res.data.forEach(function (log) {
                $tbody.append(
                    $('<tr>').append(
                        $('<td>', { class: 'ps-4' }).text(log.action),
                        $('<td>').text(log.username),
                        $('<td>', { class: 'pe-4', title: log.date }).text(timeAgo(log.date))
                    )
                );
            });
        },
        error: function (xhr, status) {
            if (isInitial) {
                $('#recentActivityLogs').empty().append(
                    $('<tr>').append(
                        $('<td>', { colspan: 3, class: 'text-center text-danger py-3' })
                            .text(status === 'timeout' ? 'Request timed out' : 'Failed to load activity')
                    )
                 );
            }
        },
        complete: function () {
            if (!document.hidden) {
                recentLogsTimer = setTimeout(() => getRecentLogs(false), 3000);
            }
        }
    });
}

getRecentLogs();

let unassignedTimer = null;
let lastUnassignedHtml = null;

function loadUnassignedTickets(isInitial = false) {
    clearTimeout(unassignedTimer);
    const $body = $('#unassignedTicketsBody');

    $.ajax({
        url: './api/get_open_tickets.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial) {
                $body.html('<tr><td colspan="5" class="text-center">Loading unassigned tickets...</td></tr>');
            }
        },
        success: function (res) {
            if (res.status !== 'success') {
                if (isInitial) {
                    $body.html(`<tr><td colspan="5" class="text-center text-danger">Error: ${esc(res.message)}</td></tr>`);
                }
                return;
            }

            let html;
            if (res.data.length === 0) {
                html = '<tr><td colspan="5" class="text-center">No unassigned tickets found.</td></tr>';
            } else {
               html = res.data.map(t => `
                <tr>
                    <td>${esc(t.ticket_number)}</td>
                    <td>${t.subject}</td>
                    <td class="font-weight-bold ${esc(t.priority_class)}">${esc(t.priority)}</td>
                    <td>${esc(t.waiting_time)}</td>
                    <td>
                        <a class="btn btn-outline-info btn-sm" href="ticket_detail.php?id=${encodeURIComponent(t.id)}">View</a>
                        <a class="btn btn-outline-primary btn-sm assign_ticket"
                        data-id="${esc(t.id)}"
                        data-ticket-number="${esc(t.ticket_number)}"
                        data-subject="${t.subject}"
                        data-toggle="modal"
                        data-target="#assignTicketModal">Assign</a>
                        ${res.role === "IT Supervisor" ? `
                        <a class="btn btn-outline-purple btn-sm takeover-ticket ml-1"
                        data-id="${esc(t.id)}"
                        data-ticket-number="${esc(t.ticket_number)}">Take Over</a>
                        ` : ''}
                    </td>
                </tr>`).join('');
            }

            // Only touch the DOM if something actually changed
            if (html !== lastUnassignedHtml) {
                $body.html(html);
                lastUnassignedHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error('Unassigned tickets fetch failed:', status, error);
            // Only replace the table on first load; otherwise keep the last good data
            if (isInitial) {
                $body.html('<tr><td colspan="5" class="text-center text-danger">Failed to fetch data.</td></tr>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                unassignedTimer = setTimeout(() => loadUnassignedTickets(false), 3000);
            }
        }
    });
}

loadUnassignedTickets(true);


let changeRequestTimer = null;
let lastChangeRequestHtml = null;

function loadChangeRequests(isInitial = false) {
    clearTimeout(changeRequestTimer);
    const $body = $('#changeRequestsBody');

    $.ajax({
        url: './api/get_change_requests.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial) {
                $body.html('<tr><td colspan="8" class="text-center">Loading change requests...</td></tr>');
            }
        },
        success: function (response) {
            if (response.status !== 'success') {
                if (isInitial) {
                    $body.html(`<tr><td colspan="8" class="text-center text-danger">Error: ${esc(response.message)}</td></tr>`);
                }
                return;
            }

            let html;
            if (response.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center">No change requests found.</td></tr>';
            } else {
                html = response.data.map(cr => `
                    <tr>
                        <td>${esc(cr.crf_number)}</td>
                        <td>${esc(cr.change_title)}</td>
                        <td>${esc(cr.department)}</td>
                        <td>${esc(cr.change_type)}</td>
                        <td>${esc(cr.status)}</td>
                        <td>${esc(cr.created_by)}</td>
                        <td>${esc(cr.created_at)}</td>
                        <td>
                            <a class="btn btn-outline-primary btn-md"
                               href="view_change_request.php?id=${encodeURIComponent(cr.id)}">View</a>
                        </td>
                    </tr>`).join('');
            }

            if (html !== lastChangeRequestHtml) {
                $body.html(html);
                lastChangeRequestHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.log("Status: " + status);
            console.log("Error: " + error);
            console.error('Change requests fetch failed:', status, error);
            if (isInitial) {
                $body.html('<tr><td colspan="8" class="text-center text-danger">Failed to fetch data.</td></tr>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                changeRequestTimer = setTimeout(() => loadChangeRequests(false), 3000);
            }
        }
    });
}

loadChangeRequests(true);


let queueTimer = null;
let lastQueueHtml = null;

function loadTicketQueue(isInitial = false) {
    clearTimeout(queueTimer);
    const $body = $('#ticketQueueBody');

    $.ajax({
        url: './api/get_tickets.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial) {
                $body.html('<tr><td colspan="7" class="text-center">Loading tickets...</td></tr>');
            }
        },
        success: function (response) {
            if (response.status !== 'success') {
                if (isInitial) {
                    $body.html(`<tr><td colspan="7" class="text-center text-danger">Error: ${esc(response.message)}</td></tr>`);
                }
                return;
            }

            let html;
            if (response.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center">No open tickets assigned.</td></tr>';
            } else {
                html = response.data.map(t => `
                    <tr>
                        <td>#${esc(t.ticket_number)}</td>
                        <td>${t.subject}</td>
                        <td>${esc(t.priority)}</td>
                        <td>${esc(t.sla_display)}</td>
                        <td><h5><span class="badge ${esc(t.status_badge)}">${esc(t.status)}</span></h5></td>
                        <td>${esc(t.requester_name)}</td>
                        <td>
                            ${t.status === 'Open'
                                ? `<a href="ticket_detail.php?id=${encodeURIComponent(t.id || t.ticket_id)}" class="btn btn-sm btn-outline-secondary ml-2">View</a>`
                                : ``
                            }
                        </td>
                    </tr>
                    `).join('');
            }

            if (html !== lastQueueHtml) {
                $body.html(html);
                lastQueueHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error('Ticket queue fetch failed:', status, error);
            if (isInitial) {
                $body.html('<tr><td colspan="7" class="text-center text-danger">Failed to fetch data from server.</td></tr>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                queueTimer = setTimeout(() => loadTicketQueue(false), 3000);
            }
        }
    });
}

loadTicketQueue(true);

let pickupTicketsTimer = null;
let lastPickupTicketsHtml = null;

function loadPickupTickets(isInitial = false) {
    clearTimeout(pickupTicketsTimer);
    const $body =$('#pickupTicketsBody');

    $.ajax({
        url: './api/get_pickup_tickets.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial) {
                $body.html('<tr><td colspan="5" class="text-center">Loading pickup tickets...</td></tr>');
            }
        },
        success: function (response) {
            if (response.status !== 'success') {
                if (isInitial) {
                    $body.html(`<tr><td colspan="5" class="text-center text-danger">Error: ${esc(response.message)}</td></tr>`);
                }
                return;
            }

            let html;
            if (response.data.length === 0) {
                html = `
                    <tr>
                        <td colspan="5" class="text-center">
                            <h3 class="alert alert-warning mb-0"><span class="fa fa-info-circle"></span> No tickets right now.</h3>
                        </td>
                    </tr>`;
            } else {
                html = response.data.map(ticket => `
                    <tr>
                        <td>${esc(ticket.ticket_number)}</td>
                        <td>${ticket.subject}</td>
                        <td>${esc(ticket.priority)}</td>
                        <td>${esc(ticket.waiting_time)}</td>
                        <td>
                            <a class="btn btn-sm btn-primary btn-claim" data-id="${esc(ticket.id)}">Claim</a>
                        </td>
                    </tr>`).join('');
            }

            if (html !== lastPickupTicketsHtml) {
                $body.html(html);
                lastPickupTicketsHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error('Pickup tickets fetch failed:', status, error);
            if (isInitial) {
                $body.html('<tr><td colspan="5" class="text-center text-danger">Failed to fetch data.</td></tr>');
            }
        },
        complete: function () {
            if (!document.hidden) {
                pickupTicketsTimer = setTimeout(() => loadPickupTickets(false), 3000);
            }
        }
    });
}

// Initial trigger
loadPickupTickets(true);

let resolvedTicketsTimer = null;
let lastResolvedTicketsHtml = null;

function loadResolvedTickets(isInitial = false) {
    clearTimeout(resolvedTicketsTimer);
    const $body =$('#resolvedTicketsBody');

    $.ajax({
        url: './api/get_resolved_tickets.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        beforeSend: function () {
            if (isInitial) {
                $body.html('<tr><td colspan="4" class="text-center">Loading resolved tickets...</td></tr>');
            }
        },
        success: function (response) {
            if (response.status !== 'success') {
                if (isInitial) {
                    $body.html(`<tr><td colspan="4" class="text-center text-danger">Error: ${esc(response.message)}</td></tr>`);
                }
                return;
            }

            let html;
            if (response.data.length === 0) {
                html = '<tr><td colspan="4" class="text-center">No resolved tickets found.</td></tr>';
            } else {
                html = response.data.map(ticket => `
                    <tr>
                        <td>${esc(ticket.ticket_number)}</td>
                        <td>${ticket.subject}</td>
                        <td>${esc(ticket.name)}</td>
                        <td>${esc(ticket.time_ago)}</td>
                    </tr>`).join('');
            }

            // Only update DOM if the rendered HTML content has changed
            if (html !== lastResolvedTicketsHtml) {
                $body.html(html);
                lastResolvedTicketsHtml = html;
            }
        },
        error: function (xhr, status, error) {
            console.error('Resolved tickets fetch failed:', status, error);
            if (isInitial) {
                $body.html('<tr><td colspan="4" class="text-center text-danger">Failed to fetch data.</td></tr>');
            }
        },
        complete: function () {
            // Resume 5-second polling only if tab is visible
            if (!document.hidden) {
                resolvedTicketsTimer = setTimeout(() => loadResolvedTickets(false), 3000);
            }
        }
    });
}

// Initial trigger
loadResolvedTickets(true);

const $badge = $('#openTicketBadge');
let last = parseInt($badge.text(), 10) || 0;

function refreshCount() {
    $.ajax({url: './api/open_ticket_count.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function (data) {
            console.log(data);
            if (data.count !== last) {
                $badge.text(data.count);
                last = data.count;
            }
        },
        error: function () {
        /* ignore network hiccups */
        }
    });
}
setInterval(refreshCount, 5000);

// Pause when tab is hidden, refresh immediately when it returns
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        loadTicketQueue(false);
        loadUnassignedTickets(false);
        loadChangeRequests(false);
        loadResolvedTickets(false);
        loadPickupTickets(false);
        loadOverdueAlert(false);
        loadAttentionAlert(false);
        pollServerMonitor();
        refreshCount();
    }
});


const STATUS_LABELS = {
  online: 'Online',
  degraded: 'Degraded',
  unreachable: 'Offline'
};

function usageLevel(percent) {
  if (percent == null || Number.isNaN(percent)) return 'level-ok';
  if (percent >= 90) return 'level-crit';
  if (percent >= 70) return 'level-warn';
  return 'level-ok';
}

function setMetricBar(barSel, wrapSel, percent) {
  const p = Math.max(0, Math.min(100, Number(percent) || 0));
  const $bar = $(barSel);
  const $wrap = $(wrapSel);
  $bar.css('width', p + '%');
  $bar.removeClass('level-ok level-warn level-crit').addClass(usageLevel(p));
  $wrap.attr('aria-valuenow', Math.round(p));
}

/** CPU load average → approximate % of capacity (load / cores × 100, capped). */
function cpuLoadToPercent(load, cores) {
  const l = Number(load);
  const c = Number(cores) || 1;
  if (Number.isNaN(l)) return null;
  return Math.min(100, (l / c) * 100);
}

function setStatusBadge(state) {
  const $badge = $('#server-status-badge');
  const label = STATUS_LABELS[state] || STATUS_LABELS.unreachable;

  $badge.removeClass('is-online is-degraded is-unreachable')
        .addClass('is-' + state);
  $('#server-status-text').text(label);
}

let monitorTimer = null;
let monitorFailures = 0;
let monitorPollIntervalMs = 30000;
let monitorHistoryMax = 0;

const DEGRADED_REASON_LABELS = {
  cpu: 'High CPU load',
  ram: 'High RAM usage',
  disk: 'Low disk space'
};

function formatMonitorTime(checkedAt) {
  if (checkedAt) {
    const parsed = new Date(String(checkedAt).replace(' ', 'T'));
    if (!Number.isNaN(parsed.getTime())) {
      return parsed.toLocaleTimeString();
    }
  }
  return new Date().toLocaleTimeString();
}

function setDegradedReasons(reasons) {
  const $wrap = $('#server-degraded-wrap');
  if (!$wrap.length) return;

  if (Array.isArray(reasons) && reasons.length) {
    const text = reasons
      .map((r) => DEGRADED_REASON_LABELS[r] || r)
      .join(' · ');
    $wrap.removeClass('d-none').text('Degraded: ' + text);
  } else {
    $wrap.addClass('d-none').text('');
  }
}

function appendMonitorHistoryRow(state, data, note) {
  const $tbody = $('#server-monitor-history');
  if (!$tbody.length || monitorHistoryMax <= 0) return;

  $('#server-monitor-history-empty').remove();

  const fmt = (v, suffix = '') =>
    (v === undefined || v === null) ? 'N/A' : esc(v) + suffix;

  const cpuText = fmt(data.cpu_load) + (data.cpu_cores ? ` (${data.cpu_cores})` : '');
  const statusClass = state === 'online' ? 'text-success'
                    : state === 'degraded' ? 'text-warning'
                    : 'text-secondary';

  const row = `<tr>
    <td class="ps-3">${esc(formatMonitorTime(data.checked_at))}</td>
    <td class="${statusClass} fw-semibold">${esc(STATUS_LABELS[state] || state)}</td>
    <td>${cpuText}</td>
    <td>${fmt(data.ram_percent, '%')}</td>
    <td>${fmt(data.disk_percent, '%')}</td>
    <td class="pe-3 small text-muted">${esc(note || '')}</td>
  </tr>`;

  $tbody.prepend(row);
  $tbody.find('tr:gt(' + (monitorHistoryMax - 1) + ')').remove();
}

function applyServerMonitorData(data, state, note) {
  $('#server-health-card').removeClass('is-loading');
  setStatusBadge(state);
  setDegradedReasons(data.degraded_reasons);

  const fmt = (v, suffix = '') =>
    (v === undefined || v === null) ? 'N/A' : esc(v) + suffix;

  $('#server-cpu').text(
    fmt(data.cpu_load) + (data.cpu_cores ? ` (${data.cpu_cores} cores)` : '')
  );
  $('#server-ram').text(fmt(data.ram_percent, '%'));
  $('#server-disk').text(fmt(data.disk_percent, '%'));

  const cpuForBar = data.cpu_percent != null
    ? data.cpu_percent
    : cpuLoadToPercent(data.cpu_load, data.cpu_cores);

  setMetricBar('#server-cpu-bar', '#server-cpu-bar-wrap', cpuForBar);
  setMetricBar('#server-ram-bar', '#server-ram-bar-wrap', data.ram_percent);
  setMetricBar('#server-disk-bar', '#server-disk-bar-wrap', data.disk_percent);

  $('#server-updated').text(`Updated ${formatMonitorTime(data.checked_at)}`);
  appendMonitorHistoryRow(state, data, note);
}

function initServerMonitor() {
  const $page = $('#server-monitor-page');
  const $card = $('#server-health-card');
  if (!$page.length && !$card.length) return;

  if ($page.length) {
    monitorPollIntervalMs = parseInt($page.data('poll-interval'), 10) || 10000;
    monitorHistoryMax = parseInt($page.data('history-max'), 10) || 30;
  } else {
    monitorPollIntervalMs = parseInt($card.data('poll-interval'), 10) || 30000;
    monitorHistoryMax = 0;
  }

  pollServerMonitor();
}

function pollServerMonitor() {
  clearTimeout(monitorTimer);

  $.ajax({
    url: './api/server_monitor.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    timeout: 10000,
    success: function (data) {
      monitorFailures = 0;

      const state = data.status === 'online' ? 'online'
                  : data.status === 'degraded' ? 'degraded'
                  : 'unreachable';

      applyServerMonitorData(data, state, '');
    },
    error: function (xhr) {
      $('#server-health-card').removeClass('is-loading');
      setDegradedReasons([]);

      if (xhr.status === 401) {
        setStatusBadge('unreachable');
        $('#server-updated').text('Session expired');
        appendMonitorHistoryRow('unreachable', {}, 'Session expired');
        return;
      }

      monitorFailures++;
      if (monitorFailures >= 2) {
        setStatusBadge('unreachable');
        $('#server-updated').text('Failed to reach server');
        appendMonitorHistoryRow('unreachable', {}, 'Failed to reach server');
      }
    },
    complete: function () {
      if (!document.hidden) {
        monitorTimer = setTimeout(pollServerMonitor, monitorPollIntervalMs);
      }
    }
  });
}

initServerMonitor();

$(document).ready(function() {

    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }

	// popovers Initialization
	$('[data-toggle="popover"]').popover();
    
	// sidenav initialization
	$(".button-collapse").sideNav({
        closeOnClick: true,
        breakpoint: 1200
    });

    var $html = $('html'), $btn = $('#sidebarCollapse');

    function setState(collapsed) {
        $html.toggleClass('kd-sidebar-collapsed', collapsed);
        $btn.attr('aria-expanded', !collapsed)
            .attr('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        try { localStorage.setItem('kdSidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) {}

        // let charts and tables re-measure after the animation
        setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 320);
    }

    // sync the button state with what the head script applied
    $btn.attr('aria-expanded', !$html.hasClass('kd-sidebar-collapsed'));

    $btn.on('click', function () {
        setState(!$html.hasClass('kd-sidebar-collapsed'));
    });

    loadAttentionAlert();
    loadOverdueAlert();
    
    $('.mdb-select').materialSelect();
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();

    var currentPage = window.location.pathname.split("/").pop();

    $(".nav-link").each(function () {
        var href = $(this).attr("href");

        if (href === currentPage) {
            $(this).addClass("active");
        }
    });

    $("#btnEscalate").click(function() {
        var reason = $("#escalationReason").val().trim();
        if (reason === '') {
            Swal.fire('Reason Required', 'Please explain why this ticket needs escalation.', 'warning');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Escalating...');

        $.ajax({
            url: "./api/escalate_ticket.php",
            type: "POST",
            data: { ticket_id: $btn.data('ticket-id'), reason: reason },
            dataType: 'JSON',
            success: function(response) {
                $btn.prop('disabled', false).text('Escalate to Supervisor');
                if (response.status === 'success') {
                    toastr.success('This ticket has been flagged for Supervisor review.', 'Escalated!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to escalate ticket.', 'Error!');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Escalate to Supervisor');
                toastr.error('Something went wrong.', 'Error!');
            }
        });
    });

function renderComment(c) {
    const itRoles = ['IT Support Specialist', 'IT Helpdesk Support', 'System Administrator', 'IT Supervisor','IT Manager'];
    const isIT = itRoles.includes(c.author_role);
    const time = new Date(c.created_at.replace(' ', 'T')).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

    return `
        <div class="d-flex gap-3 mb-4">
          <div>
            ${isIT ? '<span class="fa-solid fa-user-gear fa-xl"></span>' : '<span class="fa-solid fa-user-circle fa-xl"></span>'}
          </div>
          <div>
            <p class="mb-1 ml-1" style="font-size:0.85rem;">
              <span class="fw-semibold${isIT ? ' text-primary' : ''}">${$('<div>').text(c.author_name).html()}</span>
              <span class="text-muted">commented &middot; ${time}</span>
            </p>
            <p class="mb-0">${$('<div>').text(c.body).html().replace(/\n/g, '<br>')}</p>
          </div>
        </div>`;
}

let lastCommentId = parseInt($('#commentsThread').data('last-comment-id'), 10) || 0;
const ticketId = $('#btnComment').data('ticket-id');

function pollComments() {
    $.ajax({
        url: './api/get_comments.php',
        type: 'GET',
        data: { ticket_id: ticketId },
        dataType: 'json',
        success: function (res) {
            if (res.success && res.comments.length) {
                const newOnes = res.comments.filter(c => c.id > lastCommentId);
                newOnes.forEach(c => {
                    $('#commentsThread').append(renderComment(c));
                });
                if (newOnes.length) {
                    lastCommentId = res.comments[res.comments.length - 1].id;
                }
            }
        },
        error: function (xhr, status, error) {
            console.log("Status: " + status);
            console.log("Error: " + error);
            console.log("Ajax Error: ", xhr.responseText);
            console.error('Failed to fetch comments:', error);
        },
        complete: function () {
            // Poll again 3s after request completes (success or error)
            setTimeout(pollComments, 3000);
        }
    });
}

    // Start polling 5s after initial page load
    setTimeout(pollComments, 3000);

    var currentAssignTicketId = null;
    var currentAssignTicketNumber = null;


    $(document).on('click', '.assign_ticket', function(e) {
        e.preventDefault();

        currentAssignTicketId = $(this).data('id');
        currentAssignTicketNumber = $(this).data('ticket-number');
        
        $("#assignTicketNumber").text('#' + $(this).data('ticket-number'));
        $("#assignTicketSubject").text($(this).data('subject'));

        $("#assignAgentSelect").empty().append(
            $('<option>', { value: '', text: 'Loading...' })
        );

        $.ajax({
            url: "./api/get_agents.php",
            type: "GET",
            dataType: 'JSON',
            success: function(response) {
                var $select = $("#assignAgentSelect").empty();
                $select.append($('<option>', { value: '', text: '-- Select IT Support --' }));

                if (!response.agents || response.agents.length === 0) {
                    $select.append($('<option>', { value: '', text: 'No agents available', disabled: true }));
                    return;
                }

                response.agents.forEach(function(a) {
                    $select.append(
                        $('<option>', {
                            value: a.id,
                            text: a.name + ' (' + a.open_count + ' open)'
                        })
                    );
                });
            },
            error: function(xhr, status, error){
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                $("#assignAgentSelect").empty().append(
                    $('<option>', { value: '', text: 'Failed to load agents' })
                );
            }
        });
    });

// Confirm assignment - IT Supervisor assigns the ticket to the IT Support
    $("#btnConfirmAssign").click(function() {

        var agentId = $("#assignAgentSelect").val();


        if (!agentId) {
            Swal.fire('Select an agent', 'Please choose an IT Support agent first.', 'warning');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Assigning...');

        $.ajax({
            url: "./api/assign_ticket.php",
            type: "POST",
            data: { 
                    ticket_id: currentAssignTicketId, 
                    agent_id: agentId 
            },
            dataType: 'JSON',
            success: function(response) {
                console.log(response);
                $btn.prop('disabled', false).text('Confirm Assign');

                if (response.status === 'success') {
                    $('#assignTicketModal').modal('hide');
                    Swal.fire('Assigned!', 'Ticket #' + response.ticket_number + ' has been assigned.', 'success')
                        .then(function() { location.reload(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr, status, error){
                $btn.prop('disabled', false).text('Confirm Assign');
                Swal.fire('Error!', 'Something went wrong assigning this ticket.', 'error');
                console.log(xhr.responseText); // helps you see the actual PHP error while debugging
            }
        });
    });


    // Claim button — IT Support claims the ticket
    $(".btn-claim").on("click", function(e) {
        
        e.preventDefault();

        var ticketId = $(this).data('id');
        var $row = $(this).closest('tr');
        var $btn = $(this);

        $btn.prop('disabled', true).text('Claiming...');

        $.ajax({
            url: "./api/pickup_ticket.php",
            type: "POST",
            data: { ticket_id: ticketId },
            dataType: 'JSON',
            success: function(response) {
                if (response.status === 'success') {
                    console.log(response)
                    Swal.fire({
                        title: 'Ticket Claimed!',
                        text: 'Ticket #' + response.ticket_number + ' has been assigned to you.',
                        icon: 'success',
                        confirmButtonText: 'Okay'
                    }).then(function() {
                        window.location.reload();
                    });
                } else if (response.status === 'already_claimed') {
                    Swal.fire('Too Late!', 'Someone already claimed this ticket.', 'info');
                    $row.fadeOut(300, function() { $(this).remove(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $btn.prop('disabled', false).text('Claim');
                }
            },
            error: function(xhr, status, error){
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                Swal.fire('Error!', 'Something went wrong claiming this ticket.', 'error');
                $btn.prop('disabled', false).text('Claim');
            }
        });
    });

    $(document).on('click', '.btn-start', function () {
        var $btn = $(this);

        // Works for both the queue list (data-id) and the detail page (data-ticket-id)
        var ticketId = $btn.data('ticket-id') || $btn.data('id');

        if (!ticketId || $btn.hasClass('disabled')) return;

        var originalText = $btn.text();

        Swal.fire({
            title: 'Accept this ticket?',
            text: 'This ticket will be assigned to you and marked as started.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, accept it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            reverseButtons: true
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.addClass('disabled').prop('disabled', true).text('Starting...');

            $.ajax({
                url: './api/start_ticket.php',
                type: 'POST',
                data: { ticket_id: ticketId },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Ticket accepted!',
                            icon: 'success',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.href = 'ticket_detail.php?id=' + encodeURIComponent(ticketId);
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                        $btn.removeClass('disabled').prop('disabled', false).text(originalText);
                    }
                },
                error: function (xhr, status, error) {
                    console.log('Ajax Error:', status, error, xhr.responseText);
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                    $btn.removeClass('disabled').prop('disabled', false).text(originalText);
                }
            });
        });
    });

    $(document).on('click', '.takeover-ticket', function() {
        var $btn = $(this);
        var ticketId = $btn.data('id');
        var ticketNumber = $btn.data('ticket-number');

        // Keep SweetAlert for the confirmation prompt
        Swal.fire({
            title: 'Take over ticket #' + ticketNumber + '?',
            text: 'This assigns it to you directly — IT Support will no longer be able to pick it up.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, take it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $btn.addClass('disabled').text('Taking over...');

            $.ajax({
                url: "./api/pickup_ticket.php",
                type: "POST",
                data: { ticket_id: ticketId },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success('Ticket #' + ticketNumber + ' is now assigned to you.', 'Taken!', {
                            timeOut: 2000
                        });
                        if (typeof loadUnassignedTickets === 'function') {
                            loadUnassignedTickets();
                        }
                    } else if (response.status === 'already_claimed') {
                        toastr.info('Someone already claimed this ticket.', 'Too Late!');
                        if (typeof loadUnassignedTickets === 'function') {
                            loadUnassignedTickets();
                        }
                    } else {
                        toastr.error(response.message || 'Failed to take over ticket.', 'Error!');
                        $btn.removeClass('disabled').text('Take Over');
                    }
                },
                error: function() {
                    toastr.error('Something went wrong.', 'Error!');
                    $btn.removeClass('disabled').text('Take Over');
                }
            });
        });
    });

    $(document).on('click', '.btn-reopen', function () {
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');

        Swal.fire({
            title: 'Reopen this ticket?',
            text: 'Tell IT what is still not working.',
            icon: 'info',
            input: 'textarea',
            inputPlaceholder: 'What is still not working? (optional)',
            showCancelButton: true,
            confirmButtonText: 'Yes, reopen',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).text('Reopening...');

            $.ajax({
                url: './api/reopen_ticket.php',
                type: 'POST',
                data: { ticket_id: ticketId, reason: result.value || '' },
                dataType: 'JSON',
                success: function (response) {
                    if (response.status === 'success') {
                        toastr.success('IT has been notified.', 'Ticket Reopened!', {
                            timeOut: 2000,
                            onHidden: function () {
                                location.reload();
                            }
                        });
                    } else {
                        toastr.error(response.message || 'Failed to reopen ticket.', 'Error!');
                        $btn.prop('disabled', false).text('Reopen: still not fixed');
                    }
                },
                error: function () {
                    toastr.error('Something went wrong.', 'Error!');
                    $btn.prop('disabled', false).text('Reopen: still not fixed');
                }
            });
        });
    });

    $(document).on('click', '.close_ticket', function(){
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var ticketNumber = $btn.data('ticket-number');
    
        Swal.fire({
            title: 'Is the issue fixed?',
            text: 'Ticket #' + ticketNumber + ' will be closed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, close it',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            closeTicket($btn, ticketId, ticketNumber, 'Confirm: issue fixed', 'Thank you for confirming.');
        });
    });

    $(document).on('click', '#btnForceClose', function () {
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var ticketNumber = $btn.data('ticket-number');
    
        Swal.fire({
            title: 'Close ticket #' + ticketNumber + '?',
            text: 'The requester has not confirmed the fix. This will be logged under your name.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, close it',
            confirmButtonColor: '#6c757d',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            closeTicket($btn, ticketId, ticketNumber, 'Close ticket', 'The ticket has been closed.');
        });
    });

    function closeTicket($btn, ticketId, ticketNumber, btnLabel, successText) {
        $btn.prop('disabled', true).text('Closing...');
    
        $.ajax({
            url: './api/confirm_close.php',
            type: 'POST',
            data: { ticket_id: ticketId },
            dataType: 'JSON',
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire('Closed', successText, 'success')
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $btn.prop('disabled', false).text(btnLabel);
                }
            },
            error: function () {
                Swal.fire('Error!', 'Something went wrong.', 'error');
                $btn.prop('disabled', false).text(btnLabel);
            }
        });
    }
 

    $("#btnComment").on('click', function(e){
        e.preventDefault();

        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var comment = $("#comment").val().trim();

        $btn.prop('disabled', true).text('Sending Comment...');

        $.ajax({
            url: './api/add_comment.php',
            type: "POST",
            data: {
                ticket_id: ticketId,
                comment: comment,
            },
            dataType: 'JSON',
            success: function(response){
                console.log(response);
                if (response.status === 'success') {
                    $('#commentsThread').append(renderComment(response.comment));
                    $('#comment').val('');
                    
                    toastr.success('Comment sent successfully', 'Success!', {
                        timeOut: 1500,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to send comment.', 'Error!');
                    $btn.prop('disabled', false).text('Send Comment');
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                
                toastr.error('Something went wrong. ' + error, 'Error!');
                $btn.prop('disabled', false).text('Send Comment');
            }
        });
    });

    $("#btnMarkResolved").on('click', function() {
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var notes = $("#resolutionNotes").val().trim();

        if (notes === '') {
            Swal.fire('Resolution Notes Required','Please describe what was done before marking this resolved.','warning');
            return;
        }

        $btn.prop('disabled', true).text('Resolving...');

        $.ajax({
            url: "./api/resolve_ticket.php",
            type: "POST",
            data: { ticket_id: ticketId, resolution_notes: notes },
            dataType: 'JSON',
            success: function(response) {
                console.log(response);
                if (response.status === 'success') {
                    toastr.success('The ticket has been marked as resolved.', 'Resolved!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to resolve ticket.', 'Error!');
                    $btn.prop('disabled', false).text('Mark as Resolved');
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                
                toastr.error('Something went wrong. ' + error, 'Error!');
                $btn.prop('disabled', false).text('Mark as Resolved');
            }
        });
    });

    $("#add_ticket").on("click", function(e) {
        e.preventDefault();

        var formElement = $("#frmCreateTicket")[0];
        var formData = new FormData(formElement);

        var fileInput = $("#attachment")[0];
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            formData.append("attachment", fileInput.files[0]);
        }

        $.ajax({
            url: "./api/add_tickets.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'JSON',
            success: function(response) {
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                        }
                    });
                // Handle BOTH 'warning' and 'partial' status codes
                } else if (response.status === 'warning' || response.status === 'partial') {
                    toastr.warning(response.message, 'Ticket Created', {
                        timeOut: 2500,
                        onHidden: function() {
                            window.location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to process request.', 'Error!');
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                
                toastr.error('An error occurred: ' + error, 'Error!');
            }
        });
    });

    $("#save_ticket").on('click', function(e){
        e.preventDefault();

        const form = document.getElementById('frmEditTicket');

        // Basic client-side check before hitting the server
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        Swal.fire({
            title: 'Update this ticket?',
            text: "Please confirm your changes before saving.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Saving...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './api/save_tickets.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response){
                    
                    Swal.close();

                    if (response.status === 'success') {
                        toastr.success(response.message, 'Updated!', {
                            timeOut: 2000,
                            onHidden: function() {
                                window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                            }
                        });
                    } else if (response.status === 'warning') {
                        toastr.warning(response.message, 'Partial success', {
                            timeOut: 2500,
                            onHidden: function() {
                                window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                            }
                        });
                    } else {
                        toastr.error(response.message || 'Something went wrong.', 'Error');
                    }
                },
                error: function(xhr){
                    Swal.close();
                    toastr.error('Server returned an unexpected response. Please try again.', 'Request failed');
                    console.error(xhr.responseText);
                }
            });
        });
    });

    $('.delete_ticket').on('click', function(){
        var $btn = $(this);
        var ticketNumber = $btn.data('ticket-number');

        const ticketId = $(this).closest('.delete_ticket').attr('delete_ticket_id') || $(this).attr('delete_ticket_id');

        Swal.fire({
            title: 'Delete ticket #' + ticketNumber + '?',
            text: 'This will remove your ticket. IT will no longer see it.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: './api/delete_ticket.php',
                type: 'POST',
                data: { ticket_id: ticketId },
                dataType: 'JSON',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire('Deleted', 'Ticket #' + ticketNumber + ' has been deleted.', 'success')
                            .then(function () { window.location.href = 'employee_dashboard.php'; });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                        $btn.prop('disabled', false).text('Delete Ticket');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'Something went wrong deleting the ticket.', 'error');
                    $btn.prop('disabled', false).text('Delete Ticket');
                }
            });
        });
    });

    $('.delete_change_request').on('click', function(){
        const changeRequestId = $(this).closest('.delete_change_request').attr('delete_change_request_id') || $(this).attr('delete_change_request_id');

        Swal.fire({
            title: 'Delete this change request?',
            text: "This will permanently delete the change request. This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, close it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Deleting change request...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './api/delete_change_request.php', // adjust path to match your project
                type: 'POST',
                data: { change_request_id: changeRequestId },
                dataType: 'json',
                success: function(response){
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Deleted',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Something went wrong.',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error){
                    console.log("Status: " + status);
                    console.log("Error: " + error);
                    console.log("Ajax Error: ", xhr.responseText);
                    Swal.fire({
                        title: 'Request failed',
                        text: 'Server returned an unexpected response. Please try again.',
                        icon: 'error'
                    });
                    console.error(xhr.responseText);
                }
            });
        });
    });
    
    
    $("#add_users").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url: "./api/add_users.php",
            type: "POST",
            data: {
                name: $("#name").val(),
                email: $("#email").val(),
                role: $("#role").val(),
                department: $("#department").val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to process request.', 'Error!');
                }

                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                Swal.fire({
                    title: 'Request failed',
                    text: 'Server returned an unexpected response. Please try again.',
                    icon: 'error'
                });
            }
        });
    });

    $("#save_password").on('click', function(e){
        e.preventDefault();

        var currentPassword = $('#current_password').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_password').val();

        if (newPassword !== confirmPassword) {
            toastr.error('Passwords do not match', 'Error!');
            return; // Stops the AJAX call if passwords don't match
        }

        $.ajax({
            url: "./api/save_password.php",
            type: 'POST',
            data: { 
                current_password: currentPassword,
                new_password: newPassword
            },
            dataType: 'JSON',
            success: function(response) { 
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to update password.', 'Error!');
                }

                // Fallback reload if it's an error
                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#add_kba").on("click",function(e){

        e.preventDefault();

        $.ajax({
            url: "./api/add_kb_articles.php",
            type: "POST",
            data: {
                kba_title:$("#kba_title").val(),
                kba_description:$("#kba_description").val(),
                kba_category:$("#kba_category").val(),
            },
            dataType: "JSON",
            success: function(response) {
                console.log(response);
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK',
                });
                setTimeout(function(){
                    location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#add_category").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url: './api/add_categories.php',
            type: 'POST',
            data: {
                category_code: $("#category_code").val(),
                category_name: $("#category_name").val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to add category.', 'Error!');
                }

                $("#add_category_modal").modal('hide');

                // Fallback reload if it's an error
                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#save_category").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url: './api/save_categories.php',
            type: 'POST',
            data: {
                edit_category_id: $("#edit_category_id").val(),
                edit_category_code: $("#edit_category_code").val(),
                edit_category_name: $("#edit_category_name").val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to save category.', 'Error!');
                }

                $(document.activeElement).blur();
                $("#edit_category_modal").modal('hide');

                // Fallback reload if it's an error
                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#add_sla").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url: './api/add_sla_rules.php',
            type: 'POST',
            data: {
                category: $("#category").val(),
                priority: $("#priority").val(),
                response_minutes: $("#response_minutes").val(),
                resolution_hours: $("#resolution_hours").val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);

                // Trigger Toastr based on status
                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to process request.', 'Error!');
                }

                $(document.activeElement).blur();
                $("#add_sla_modal").modal('hide');

                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Error: " + error);

                toastr.error('An error occurred: ' + error, 'Error!');
                $("#add_sla_modal").modal('hide');
            }
        });
    });

    $("#save_sla").on('click', function(e){
        e.preventDefault();

        $.ajax({
            url: './api/save_sla_rules.php',
            type: 'POST',
            data: {
                edit_sla_id: $("#edit_sla_id").val(),
                edit_sla_cat_id: $("#edit_sla_cat_id").val(),
                edit_sla_priority: $("#edit_sla_priority").val(),
                edit_sla_response_minutes: $("#edit_sla_response_minutes").val(),
                edit_sla_resolution_hours: $("#edit_sla_resolution_hours").val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);

                if (response.status === 'success') {
                    toastr.success(response.message, 'Success!', {
                        timeOut: 2000,
                        onHidden: function() {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to process request.', 'Error!');
                }

                $(document.activeElement).blur();
                $("#edit_sla_modal").modal('hide');

                if (response.status !== 'success') {
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                $("#edit_sla_modal").modal('hide');
            }
        });
    });

    $("#btnLogin").on("click", function(e) {
        const supportRoles = ["IT Support Specialist", "System Administrator", "IT Helpdesk Support"];

        e.preventDefault();

        $.ajax({
            url: './api/login.php',
            type: 'POST',
            data: {
                email: $('#email').val(),
                password: $('#password').val(),
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);

                if (response.status === 'success') {
                    // Immediate redirect on successful login
                    if (response.role === "IT Manager" || response.role === "Administrator") {
                        window.location.href = "manager_dashboard.php";
                    } else if (response.role === "IT Supervisor") {
                        window.location.href = "supervisor_dashboard.php";
                    } else if (supportRoles.includes(response.role)) {
                        window.location.href = "support_dashboard.php";
                    } else {
                        window.location.href = "employee_dashboard.php";
                    }
                } else {
                    // Non-blocking toast notification for invalid credentials/errors
                    toastr.error(response.message || "Invalid login credentials.");
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                toastr.error("An unexpected error occurred. Please try again.");
            }
        });
    });

    $("#btnLogout").on("click", function(e) {
        e.preventDefault();

        $.ajax({
            url: './api/logout.php',
            type: 'POST',
            dataType: 'json', 
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message || "Logged out successfully!");
                    
                    // Slight delay to allow the user to read the toast notification before redirecting
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    toastr.error(response.message || 'An error occurred during logout.');
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                toastr.error('An error occurred while logging out.');
            }
        });
    });

    $('#exportCsvBtn').on('click', function () {
        Swal.fire({
            title: 'Export Report?',
            text: 'Do you want to download the helpdesk CSV report?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Download',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                
                Swal.fire({
                    title: 'Generating CSV...',
                    text: 'Please wait while your report is being generated.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('export', 'csv');

                $.ajax({
                    url: 'full_reports.php?' + urlParams.toString(),
                    type: 'GET',
                    xhrFields: {
                        responseType: 'blob' // CRITICAL: Tells jQuery to receive binary stream
                    },
                    success: function (data, status, xhr) {
                        // Create download link from blob response
                        const blob = new Blob([xhr.response], { type: 'text/csv' });
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const $a = $('<a>', {
                            href: downloadUrl,
                            download: 'kaban_helpdesk_report_' + new Date().toISOString().slice(0, 10) + '.csv',
                            style: 'display: none'
                        }).appendTo('body');

                        $a[0].click();

                        window.URL.revokeObjectURL(downloadUrl);
                        $a.remove();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Report downloaded successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function (xhr, status, error) {
                        console.log("AJAX XHR: " + xhr.responseText);
                        console.log("Ajax Status: " + status);
                        console.log("AJAX Error: " + error);
                        Swal.fire({
                            title: 'Export Failed',
                            text: 'An error occurred while generating the CSV file.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

//Supervisor will takeover the escalated ticket
    $("#btnTakeOver").click(function() {
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');

        Swal.fire({
            title: 'Take over this ticket?',
            text: 'This will assign it to you and mark it as In Progress.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, take it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).text('Taking over...');

            $.ajax({
                url: "./api/claim_escalation.php",
                type: "POST",
                data: { ticket_id: ticketId },
                dataType: 'JSON',
                success: function(response) {
                    $btn.prop('disabled', false).text('Take This Myself');

                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Done!',
                            text: 'You now own this ticket.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX XHR: " + xhr.responseText);
                    console.log("Ajax Status: " + status);
                    console.log("AJAX Error: " + error);
                    $btn.prop('disabled', false).text('Take This Myself');
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                }
            });
        });
    });


    let currentResetUserId = null;

    $('#resetPasswordModal').on('show.bs.modal', function(event) {
        var $trigger = $(event.relatedTarget);
        currentResetUserId = $trigger.data('id');
        $("#resetPasswordName").text($trigger.data('name'));
        $("#newPassword").val('');
    });

    $('#btnGeneratePassword').on('click', function() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        let pass = '';
        for (let i = 0; i < 10; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#newPassword').val(pass);
    });

    $('#btnConfirmReset').on('click', function() {
        const newPassword = $('#newPassword').val();

        if (newPassword.length < 4) {
            toastr.warning('Password must be at least 4 characters.', 'Too short');
            return;
        }

        const $btn =$(this).prop('disabled', true).text('Resetting...');

        $.ajax({
            url: './api/request_reset_password.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({ user_id: currentResetUserId, new_password: newPassword }),
            contentType: 'application/json',
            success: function(response) {
                $btn.prop('disabled', false).text('Confirm Reset');
                if (response.status == 'success') {
                    $('#resetPasswordModal').modal('hide');
                    
                    // Using Toastr with a longer timeout (10 seconds) so the admin can read/copy the password
                    const userName = $("#resetPasswordName").text();
                    const successMsg = `New password for ${userName}: ${newPassword}\n(Share this with the user now)`;
                    
                    toastr.success(successMsg, 'Password Reset Success!', {
                        timeOut: 10000,
                        extendedTimeOut: 3000,
                        closeButton: true
                    });
                } else {
                    if (response.status === 'error') {
                        toastr.error(response.message || 'Could not reset password. Please try again.', 'Reset Failed');
                    } else {
                        toastr.warning(response.message || 'Could not reset password. Please try again.', 'Reset Failed');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX XHR: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                console.log("AJAX Error: " + error);

                $btn.prop('disabled', false).text('Confirm Reset');
                Swal.fire({
                    icon: 'error',
                    title: status,
                    text: xhr.responseText
                });
            }
        });
    });

    let usersTable = null;
    if ($("#tblManageUsers").length) {
        usersTable = $("#tblManageUsers").DataTable({
            "scrollX": true,
            "info": true,
            "lengthChange": true,
            "paging": true,
            "searching": true,
            "pageLength": 10,
            "order": [],
            "ajax": {
                "url": "./api/get_users.php",
                "dataSrc": "data"
            },
            "columns": [
                { 
                    "data": "name", 
                    "render": data => esc(data) 
                },
                { 
                    "data": "email", 
                    "render": data => esc(data) 
                },
                { 
                    "data": "role", 
                    "render": data => esc(data) 
                },
                { 
                    "data": "department", 
                    "render": data => esc(data) 
                },
                { 
                    "data": "username", 
                    "render": data => esc(data) 
                },
                { 
                    "data": "status",
                    "render": function(data) {
                        return data === 1 
                            ? `<span class="fa fa-solid fa-circle fa-xs text-success"></span> Active`
                            : `<span class="fa fa-solid fa-circle fa-xs text-muted"></span> Inactive`;
                    }
                },
                { 
                    "data": "is_online",
                    "render": function(data, type, row) {
                        const agoText = esc(row.ago);
                        if (data) {
                            return `<span class="font-weight-bold text-success">Yes (${agoText})</span>`;
                        } else if (row.last_activity_at) {
                            return `<span class="font-weight-bold text-danger">No (${agoText})</span>`;
                        } else {
                            return `<span class="font-weight-bold text-danger">No</span>`;
                        }
                    }
                },
                { 
                    "data": "last_activity_at",
                    "render": data => formatDate(data, '—')
                },
                { 
                    "data": "last_login_at",
                    "render": data => formatDate(data, 'Never')
                },
                { 
                    "data": "created_at",
                    "render": data => esc(data || '—')
                },
                { 
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        const toggleStatusBtn = row.status === 1
                            ? `<span class="btn btn-outline-danger btn-sm toggle_status" data-id="${row.id}" data-status="1" title="Deactivate">Deactivate</span>`
                            : `<span class="btn btn-outline-success btn-sm toggle_status" data-id="${row.id}" data-status="0" title="Activate">Activate</span>`;

                        const resetPasswordBtn = `
                            <span class="btn btn-outline-primary btn-sm reset_password" 
                                  data-id="${row.id}"
                                  data-name="${esc(row.name)}"
                                  data-toggle="modal" 
                                  data-target="#resetPasswordModal"
                                  title="Reset Password">
                                Reset Password
                            </span>`;

                        return `${toggleStatusBtn} ${resetPasswordBtn}`;
                    }
                }
            ]
        });

        // 3-Second Auto-Polling Loop (Pauses automatically when browser tab is inactive)
        setInterval(function () {
            if (!document.hidden && usersTable) {
                // The 'false' argument preserves pagination, search input, and sorting position
                usersTable.ajax.reload(null, false); 
            }
        }, 3000);
    }

    $(document).on('click', '.toggle_status', function (e) {
        e.preventDefault();
        const btn = $(this);
        const id = btn.data('id');
        const currentStatus = btn.data('status');
        const action = currentStatus == 1 ? 'deactivate' : 'activate';
        const isDeactivating = currentStatus == 1;

        Swal.fire({
            title: `${isDeactivating ? 'Deactivate' : 'Activate'} this user?`,
            text: isDeactivating
                ? 'They will no longer be able to log in.'
                : 'They will regain access to log in.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isDeactivating ? '#dc3545' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: './api/toggle_user_status.php',
                method: 'POST',
                data: { id: id, status: currentStatus },
                dataType: 'json',
                success: function (res) {
                    if (res.success || res.status === 'success') {
                        toastr.success(res.message || 'Status updated successfully.', 'Success!', {
                            timeOut: 1500
                        });

                        // Instantly reload the DataTable data while preserving page/search state
                        if (typeof usersTable !== 'undefined' && usersTable !== null) {
                            usersTable.ajax.reload(null, false);
                        } else {
                            location.reload(); // Fallback if table variable isn't global
                        }
                    } else {
                        toastr.error(res.message || 'Something went wrong.', 'Error!');
                    }
                },
                error: function (xhr, status, error) {
                    console.log("AJAX XHR: " + xhr.responseText);
                    console.log("Ajax Status: " + status);
                    console.log("AJAX Error: " + error);
                    Swal.fire({
                        title: 'Action Failed',
                        text: 'An error occurred while updating the user status.',
                        icon: 'error'
                    });
                }
            });
        });
    });


    
    /** Night Mode  **/
    if (localStorage.getItem('nightMode') === 'enabled') {
        $("body").addClass('bg-dark text-white');
        $(".card").addClass('bg-dark text-white');
        $(".table.dataTable tbody tr").addClass('bg-dark text-white');
        $(".table.table thead th").addClass('bg-dark text-white');
        $('.dataTables_info,.dataTables_length,.dataTables_filter,.dataTables_paginate').addClass('text-white');
    }

    $("body").fadeIn(0);

    $("#nightMode").click(function(){
		$("body").toggleClass('bg-dark text-white');
        $(".card").toggleClass('bg-dark text-white');
        $(".table.dataTable tbody tr").toggleClass('bg-dark text-white');
        $(".table.table thead th").toggleClass('bg-dark text-white');
        $('.dataTables_info, .dataTables_length,.dataTables_filter,.dataTables_paginate').toggleClass('text-white');

        if ($("body").hasClass("bg-dark text-white")) {
            localStorage.setItem("nightMode","enabled");
        } else {
            localStorage.setItem("nightMode",'disabled');
        }
	});

    /** Night Mode  **/
});