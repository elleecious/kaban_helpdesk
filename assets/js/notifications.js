let currentFilter = 'all'; // 'all' | 'unread'
let lastNotifications = []; // accumulated list across pages
let currentOffset = 0;
const PAGE_SIZE = 10;
let hasMore = true;
let isLoadingMore = false;


function timeAgo(dateString) {
  const now = new Date();
  const then = new Date(dateString.replace(' ', 'T'));
  const seconds = Math.floor((now - then) / 1000);

  if (seconds < 60) return 'Just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d`;
  const weeks = Math.floor(days / 7);
  return `${weeks}w`;
}

function isToday(dateString) {
  const d = new Date(dateString.replace(' ', 'T'));
  const now = new Date();
  return d.toDateString() === now.toDateString();
}

function getNotificationStyle(type) {
    switch(type) {
        case 'password_reset':
            return { icon: 'fa-key', bg: 'bg-warning' };
        case 'escalation_taken':
            return { icon: 'fa-bolt', bg: 'bg-danger' };
        case 'ticket_update':
            return { icon: 'fa-pen-to-square', bg: 'bg-info' };
        case 'assigned_ticket':
            return { icon: 'fa-user-check', bg: 'bg-primary' };
        case 'ticket_escalated':
            return { icon: 'fa-triangle-exclamation', bg: 'bg-danger' };
        case 'pickup_ticket':
            return { icon: 'fa-hand-holding', bg: 'bg-success' };
        case 'ticket_assigned': 
            return { icon: 'fa-ticket-alt', bg: 'bg-info' };
        case 'ticket_in_progress':
            return { icon: 'fa-spinner', bg: 'bg-info' };
        case 'resolved_ticket': 
            return { icon: 'fa-circle-check', bg: 'bg-success' };
        case 'add_comment':
            return { icon: 'fa-comment', bg: 'bg-primary' };
        case 'change_approval': 
            return { icon: 'fa-file-signature', bg: 'bg-warning' };
        case 'approved_request': 
            return { icon: 'fa-circle-check', bg: 'bg-success' };
        case 'reject_request': 
            return { icon: 'fa-circle-xmark', bg: 'bg-danger' };
        case 'sla_warning': 
            return { icon: 'fa-exclamation-triangle', bg: 'bg-danger' };
        case 'change_received':
            return { icon: 'fa-file-circle-plus', bg: 'bg-primary' };
        case 'change_implementing':
            return { icon: 'fa-gear', bg: 'bg-warning' };
        case 'change_implemented':
            return { icon: 'fa-circle-check', bg: 'bg-success' };
        case 'change_status_update':
            return { icon: 'fa-tasks', bg: 'bg-primary' };
        case 'change_rolled_back':  
            return { icon: 'fa-undo', bg: 'bg-danger' };
        case 'change_closed':
            return { icon: 'fa-check-circle', bg: 'bg-secondary' };
        default: 
            return { icon: 'fa-bell', bg: 'bg-secondary' };
    }
}

function renderNotificationItem(item) {
  const style = getNotificationStyle(item.type);
  const isUnread = parseInt(item.is_read) === 0;

  let actionButtons = '';
  if (item.type === 'change_approval') {
    actionButtons = `
      <div class="d-flex mt-2">
        <button type="button" class="btn btn-outline-dark btn-sm rounded-pill flex-grow-1 text-capitalize px-3 py-1 mr-2 waves-effect decision-btn" data-id="${item.change_request_id}" data-decision="decline">Decline</button>
        <button type="button" class="btn btn-dark btn-sm rounded-pill flex-grow-1 text-capitalize px-3 py-1 waves-effect decision-btn" data-id="${item.change_request_id}" data-decision="accept">Accept</button>
      </div>
    `;
  }

  return `
    <div class="dropdown-item p-3 notification-item ${isUnread ? 'bg-light' : ''}" data-link="${item.link}" data-id="${item.id}">
      <div class="d-flex align-items-start">
        <div class="avatar-badge-wrapper flex-shrink-0 mr-3" style="position: relative; width: 44px; height: 44px;">
          <div class="rounded-circle ${style.bg} text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="fas fa-user"></i>
          </div>
          <span class="rounded-circle ${style.bg} text-white d-flex align-items-center justify-content-center"
                style="position:absolute; bottom:-2px; right:-2px; width:18px; height:18px; border:2px solid #212529; font-size:9px;">
            <i class="fas ${style.icon}"></i>
          </span>
        </div>
        <div class="flex-grow-1 mr-2">
          <p class="small mb-1">${item.message}</p>
          <span class="text-muted" style="font-size: 11px;">${timeAgo(item.created_at)}</span>
          ${actionButtons}
        </div>
        ${isUnread ? '<span class="unread-dot bg-success" style="width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px;"></span>' : ''}
      </div>
    </div>
  `;
}

function renderNotificationList() {
  const listContainer = $('#notificationList');
  listContainer.empty();

  const filtered = currentFilter === 'unread'
    ? lastNotifications.filter(n => parseInt(n.is_read) === 0)
    : lastNotifications;

  if (filtered.length === 0) {
    listContainer.html(`
      <div class="text-center p-4 text-muted">
        <i class="fas fa-bell-slash fa-2x mb-2"></i>
        <p class="small mb-0">No notifications available</p>
      </div>
    `);
    $('#loadMoreBtn').hide();
    return;
  }

  const todayItems = filtered.filter(n => isToday(n.created_at));
  const earlierItems = filtered.filter(n => !isToday(n.created_at));

  if (todayItems.length) {
    listContainer.append(`<div class="px-3 py-2 small font-weight-bold text-muted">Today</div>`);
    todayItems.forEach(item => listContainer.append(renderNotificationItem(item)));
  }
  if (earlierItems.length) {
    listContainer.append(`<div class="px-3 py-2 small font-weight-bold text-muted">Earlier</div>`);
    earlierItems.forEach(item => listContainer.append(renderNotificationItem(item)));
  }

  // "Load more" lives inside the scrollable list, after all current items
  if (hasMore) {
    listContainer.append(`
      <div class="text-center p-2">
        <button id="loadMoreBtn" class="btn btn-md btn-light w-100">See Previous Notifications</button>
      </div>
    `);
  }
}

const NOTIF_TOAST_OPTS = {
  positionClass: 'toast-bottom-right', // top-right would cover your fixed navbar's buttons
  timeOut: 6000,
  closeButton: true,
  newestOnTop: true,
  escapeHtml: true // messages can contain ticket subjects, so never render them as HTML
};

// Remember the newest notification id this tab has seen, across page navigations
let lastSeenId = null;
try {
  const stored = sessionStorage.getItem('kd_last_notif_id');
  if (stored !== null) lastSeenId = parseInt(stored);
} catch (e) {}

function saveLastSeenId() {
  try { sessionStorage.setItem('kd_last_notif_id', lastSeenId); } catch (e) {}
}

function toastTypeFor(type) {
  if (type === 'sla_warning') return 'warning';
  if (type === 'change_rolled_back') return 'error';
  return 'info';
}

function showNewNotificationToasts(notifications) {
  const maxId = notifications.length
    ? Math.max(...notifications.map(n => parseInt(n.id)))
    : 0;

  // First load in this tab: set the baseline silently so old unread items don't toast
  if (lastSeenId === null) {
    lastSeenId = maxId;
    saveLastSeenId();
    return;
  }

  const fresh = notifications
    .filter(n => parseInt(n.id) > lastSeenId && parseInt(n.is_read) === 0)
    .sort((a, b) => a.id - b.id); // oldest first so the newest ends up on top

  if (maxId > lastSeenId) {
    lastSeenId = maxId;
    saveLastSeenId();
  }

  const MAX_TOASTS = 3;
  fresh.slice(-MAX_TOASTS).forEach(function(item) {
    const title = item.type.replace(/_/g, ' ').toUpperCase();
    toastr[toastTypeFor(item.type)](item.message, title, Object.assign({}, NOTIF_TOAST_OPTS, {
      onclick: function() {
        $.ajax({ url: `./api/get_notifications.php?action=mark_one&id=${item.id}`, type: 'GET' });
        window.location.href = item.link;
      }
    }));
  });

  if (fresh.length > MAX_TOASTS) {
    toastr.info(`+${fresh.length - MAX_TOASTS} more new notifications`, '', NOTIF_TOAST_OPTS);
  }
}


function loadNotifications(reset = true) {
  if (reset) {
    currentOffset = 0;
  }

  $.ajax({
    url: `./api/get_notifications.php?limit=${PAGE_SIZE}&offset=${currentOffset}`,
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.status === 'error') return;

      const count = parseInt(response.unread_count);
      if (count > 0) {
        $('#unreadBadgeContainer').html(`<span class="badge badge-danger badge-pill small mr-1">${count}</span>`);
        $('#markAllReadBtn').show();
      } else {
        $('#unreadBadgeContainer').empty();
        $('#markAllReadBtn').hide();
      }

      if (reset) {
        // Fresh load / poll refresh — replace the list entirely so edits/new items surface correctly
        showNewNotificationToasts(response.notifications || []);
        lastNotifications = response.notifications || [];
      } else {
        // "Load more" — append the next page, avoiding duplicates if a poll snuck in between
        const existingIds = new Set(lastNotifications.map(n => n.id));
        const newOnes = (response.notifications || []).filter(n => !existingIds.has(n.id));
        lastNotifications = lastNotifications.concat(newOnes);
      }

      hasMore = !!response.has_more;
      renderNotificationList();
      isLoadingMore = false;
    },
    error: function() {
      console.error('Failed to load notifications');
      isLoadingMore = false;
    }
  });
}

// Tabs — stopPropagation so the click doesn't bubble up and trigger Bootstrap's
// document-click listener, which would otherwise close the dropdown
$(document).on('click', '.notif-tab', function(e) {
  e.stopPropagation();
  $('.notif-tab').removeClass('active');
  $(this).addClass('active');
  currentFilter = $(this).data('filter');
  renderNotificationList();
});

// Load more — delegated since the button is re-created on every render
$(document).on('click', '#loadMoreBtn', function(e) {
  e.stopPropagation();
  if (isLoadingMore) return;
  isLoadingMore = true;
  currentOffset += PAGE_SIZE;
  loadNotifications(false);
});

// Row click — mark read + navigate
$(document).on('click', '.notification-item', function(e) {
  if ($(e.target).closest('.decision-btn').length) return;
  const notificationId = $(this).data('id');
  $.ajax({ url: `./api/get_notifications.php?action=mark_one&id=${notificationId}`, type: 'GET' });
  window.location.href = $(this).data('link');
});

// Mark all read
$(document).on('click', '#markAllReadBtn', function(e) {
  e.preventDefault();
  e.stopPropagation();
  $.ajax({
    url: './api/get_notifications.php?action=mark_all',
    type: 'GET',
    success: function() {
      loadNotifications(true);
    }
  });
});

if ($('#notificationList').length) {
  loadNotifications(true);
  setInterval(() => loadNotifications(true), 5000);
}