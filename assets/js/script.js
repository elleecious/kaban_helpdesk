function loadOverdueAlert() {
    $.ajax({
        url: "./api/get_overdue_tickets.php",
        type: "GET",
        dataType: 'JSON',
        success: function(response) {
            var $container = $("#overdueAlertContainer");
            if (response.status === 'success' && response.tickets.length > 0) {
                var count = response.tickets.length;
                var parts = response.tickets.map(function(t) {
                    return '#' + t.id + ' (' + t.priority + ', ' + t.subject + ')';
                });

                var html = '<div class="alert alert-danger" role="alert">' +
                    '<strong><span class="fa fa-warning"></span> ' + count + ' ticket' + (count > 1 ? 's are' : ' is') + ' overdue.</strong> ' +
                    parts.join(' and ') + ' ' + (count > 1 ? 'have' : 'has') + ' breached SLA.' +
                    '</div>';

                $container.html(html);
            } else {
                $container.html(
                    '<div class="alert alert-success" role="alert">' +
                    '<strong><span class="fa fa-info-circle"></span> No overdue tickets right now.</strong>' +
                    '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.log("Error: ", error);
            console.log("Ajax Error: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            $("#overdueAlertContainer").empty();
        }
    });
}

function loadAttentionAlert() {
    $.ajax({
        url: "./api/get_attention_summary.php",
        type: "GET",
        dataType: 'JSON',
        success: function(response) {
            var $container = $("#attentionAlertContainer");
            var unassigned = response.unassigned_count;
            var escalated = response.escalated_count;
            var total = unassigned + escalated;

            if (total === 0) {
                $container.html(
                    '<div class="alert alert-success" role="alert">' +
                    '✅ Nothing needs your attention right now.' +
                    '</div>'
                );
                return;
            }

            var parts = [];
            if (unassigned > 0) {
                parts.push(unassigned + ' unassigned ticket' + (unassigned > 1 ? 's' : '') + ' waiting &gt; 1 hour');
            }
            if (escalated > 0) {
                parts.push(escalated + ' ticket' + (escalated > 1 ? 's' : '') + ' escalated by an agent');
            }

            var html = '<div class="alert alert-warning" role="alert">' +
                '<strong>' + total + ' ticket' + (total > 1 ? 's' : '') + ' need' + (total > 1 ? '' : 's') + ' attention:</strong> ' +
                parts.join(', ') + '.' +
                '</div>';

            $container.html(html);
        },
        error: function(xhr, status, error) {
            console.log("Error: " + error);
            console.log("Ajax Error: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            $("#attentionAlertContainer").html('<tr><td colspan="5" class="text-center text-danger">Failed to load tickets.</td></tr>');
        }
    });
}

function loadNotifications() {
    $.ajax({
      url: './api/get_notifications.php',
      type: 'GET',
      dataType: 'json',
      success: function(response) {
        console.log(response);
        if (response.error) return;

        // Update Badge Count
        const count = parseInt(response.unread_count);
        if (count > 0) {
          $('#unreadBadgeContainer').html(`
            <span class="badge badge-danger badge-pill small mr-1">${count}</span>
            <span class="unread-dot bg-success" style="position:static; width:8px; height:8px; display:inline-block;"></span>
          `);
          $('#markAllReadBtn').show();
        } else {
          $('#unreadBadgeContainer').empty();
          $('#markAllReadBtn').hide();
        }

        // Render Dropdown Content
        const listContainer = $('#notificationList');
        listContainer.empty();

        if (!response.notifications || response.notifications.length === 0) {
          listContainer.html(`
            <div class="text-center p-4 text-muted">
              <i class="fas fa-bell-slash fa-2x mb-2"></i>
              <p class="small mb-0">No notifications available</p>
            </div>
          `);
          return;
        }

        response.notifications.forEach(function(item) {
            const style = getNotificationStyle(item.type);
            const isUnread = parseInt(item.is_read) === 0;
            const bgClass = isUnread ? 'bg-light' : '';
            const title = item.type.replace(/_/g, ' ').toUpperCase();

            let actionButtons = '';
            if (item.type === 'change_approval') {
                actionButtons = `
                <div class="d-flex mt-2">
                    <button type="button" class="btn btn-outline-dark btn-sm rounded-pill flex-grow-1 text-capitalize px-3 py-1 mr-2 waves-effect decision-btn" data-id="${item.change_request_id}" data-decision="decline">Decline</button>
                    <button type="button" class="btn btn-dark btn-sm rounded-pill flex-grow-1 text-capitalize px-3 py-1 waves-effect decision-btn" data-id="${item.change_request_id}" data-decision="accept">Accept</button>
                </div>
                `;
            }

            const itemHtml = `
                <div class="dropdown-item p-3 notification-item border-bottom waves-effect ${bgClass}" data-link="${item.link}" data-id="${item.id}">
                <div class="d-flex align-items-start">
                    <div class="avatar-badge-wrapper flex-shrink-0 mr-3">
                    <div class="rounded-circle ${style.bg} text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas ${style.icon}"></i>
                    </div>
                    </div>
                    <div class="flex-grow-1 mr-2">
                    <div class="small text-dark mb-1">
                        <strong>${title}</strong>
                        <span class="text-muted ml-1">${item.created_at}</span>
                    </div>
                    <p class="small text-muted notification-desc mb-0">${item.message}</p>
                    ${actionButtons}
                    </div>
                    ${isUnread ? '<span class="unread-dot bg-success"></span>' : ''}
                </div>
                </div>
            `;
            listContainer.append(itemHtml);
        });
      },
      error: function(xhr, status, error){
         console.log("Error: +", error);
         console.log(xhr.responseText);
         console.log("Status: " + status);
         console.log("Error in loading notification");
      }
    });
}

function getNotificationStyle(type) {
    switch(type) {
        case 'password_reset':
            return { icon: 'fa-key', bg: 'bg-danger' };
        case 'ticket_assigned': 
            return { icon: 'fa-ticket-alt', bg: 'bg-warning' };
        case 'ticket_in_progress':
            return { icon: 'fa-bars-progress', bg: 'bg-info' }
        case 'resolved_ticket': 
            return { icon: 'fa-circle-check', bg: 'bg-info' };
        case 'add_comment':
            return { icon: 'fa-comment', bg: 'bg-primary' };
        case 'change_approval': 
            return { icon: 'fa-user-plus', bg: 'bg-success' };
        case 'approved_request': 
            return { icon: 'fa-circle-check', bg: 'bg-success' };
         case 'reject_request': 
            return { icon: 'fa-times-circle ', bg: 'bg-success' };
        case 'sla_warning': 
            return { icon: 'fa-exclamation-triangle', bg: 'bg-danger' };
        default: 
            return { icon: 'fa-bell', bg: 'bg-info' };
    }
  }

function loadUnassignedTickets() {
    $.ajax({
        url: './api/get_open_tickets.php',
        type: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('#unassignedTicketsBody').html('<tr><td colspan="5" class="text-center">Loading unassigned tickets...</td></tr>');
        },
        success: function(res) {
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    $('#unassignedTicketsBody').html('<tr><td colspan="5" class="text-center">No unassigned tickets found.</td></tr>');
                    return;
                }

                let rows = '';

                // Native forEach iteration
                res.data.forEach(ticket => {
                    rows += `
                        <tr>
                            <td>${ticket.ticket_number}</td>
                            <td>${ticket.subject}</td>
                            <td class="font-weight-bold ${ticket.priority_class}">${ticket.priority}</td>
                            <td>${ticket.waiting_time}</td>
                            <td>
                                <a class="btn btn-info btn-sm"  href="ticket_detail.php?id=${ticket.id}">
                                    View  
                                </a>
                                <a class="btn btn-primary btn-sm assign_ticket"
                                   data-id="${ticket.id}"
                                   data-ticket-number="${ticket.ticket_number}"
                                   data-subject="${ticket.subject}"
                                   data-toggle="modal" 
                                   data-target="#assignTicketModal">
                                   Assign
                                </a>
                                <a class='btn kaban-color btn-sm takeover-ticket ml-1' 
                                    data-id="${ticket.id}"
                                    data-ticket-number="${ticket.ticket_number}">
                                    Take Over
                                </a>
                            </td>
                        </tr>
                    `;
                });

                $('#unassignedTicketsBody').html(rows);
            } else {
                $('#unassignedTicketsBody').html(`<tr><td colspan="5" class="text-center text-danger">Error: ${res.message}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            $('#unassignedTicketsBody').html('<tr><td colspan="5" class="text-center text-danger">Failed to fetch data.</td></tr>');
        }
    });
}

function loadTicketQueue(){
    $.ajax({
        url:'./api/get_tickets.php',
        type:'GET',
        dataType:'JSON',
        beforeSend:function() {
            $('#ticketQueueBody').html('<tr><td colspan="7" class="text-center">Loading tickets...</td></tr>');
        },
        success: function(response){
            if (response.status === 'success') {
                console.log(response);
                if (response.data.length === 0) {
                    $('#ticketQueueBody').html('<tr><td colspan="7" class="text-center">No open tickets assigned.</td></tr>');
                    return;
                }

                let rows = '';
                response.data.forEach(ticket => {
                    rows += `
                        <tr>
                            <td>#${ticket.ticket_number}</td>
                            <td>${ticket.subject}</td>
                            <td>${ticket.priority}</td>
                            <td>${ticket.sla_display}</td>
                            <td><h5><span class="badge ${ticket.status_badge}">${ticket.status}</span></h5></td>
                            <td>${ticket.requester_name}</td>
                            <td><a href="ticket_detail.php?id=${ticket.ticket_id}" class="btn btn-sm btn-info">View</a></td>
                        </tr>
                    `;
                });
                $('#ticketQueueBody').html(rows);
            } else {
                $('#ticketQueueBody').html(`<tr><td colspan="7" class="text-center text-danger">Error: ${response.data.message}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.log("Status: " + status);
            console.log(xhr.responseText);
            console.error(error);
            $('#ticketQueueBody').html('<tr><td colspan="7" class="text-center text-danger">Failed to fetch data from server.</td></tr>');
        }
    });
}


$(document).ready(function() {

	// popovers Initialization
	$('[data-toggle="popover"]').popover();
	// sidenav initialization
	$(".button-collapse").sideNav();

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
                    Swal.fire('Escalated', 'This ticket has been flagged for Supervisor review.', 'success')
                        .then(function() { location.reload(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Escalate to Supervisor');
                Swal.fire('Error!', 'Something went wrong.', 'error');
            }
        });
    });

var currentAssignTicketId = null;
var currentAssignTicketNumber = null;

$('#assignTicketModal').on('show.bs.modal', function(event) {
    var $trigger = $(event.relatedTarget); // the button that opened the modal

    currentAssignTicketId = $trigger.data('id');
    currentAssignTicketNumber = $trigger.data('ticket-number');
    var subject = $trigger.data('subject');

    $("#assignTicketNumber").text('#' + currentAssignTicketNumber);
    $("#assignTicketSubject").text(subject);

    // Load the agent dropdown
    $("#assignAgentSelect").html('<option value="">Loading...</option>');

    $.ajax({
        url: "./api/get_agents.php",
        type: "GET",
        dataType: 'JSON',
        success: function(response) {
            var options = '<option value="">-- Select IT Support --</option>';
            response.agents.forEach(function(a) {
                options += `<option value="${a.id}">${a.name} (${a.open_count} open)</option>`;
            });
            $("#assignAgentSelect").html(options);
        }
    });
});

var currentAssignTicketId = null;

$(document).on('click', '.assign_ticket', function(e) {
    e.preventDefault();

    currentAssignTicketId = $(this).data('id');

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
        data: { ticket_id: currentAssignTicketId, agent_id: agentId },
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

    $(document).on('click', '.takeover-ticket', function() {
        var $btn = $(this);
        var ticketId = $btn.data('id');
        var ticketNumber = $btn.data('ticket-number');

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
                url: "./api/pickup_ticket.php", // same endpoint Agents use to self-claim
                type: "POST",
                data: { ticket_id: ticketId },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Taken!', 'Ticket #' + ticketNumber + ' is now assigned to you.', 'success')
                            .then(() => loadUnassignedTickets()); // just refresh this table, no full page reload needed
                    } else if (response.status === 'already_claimed') {
                        Swal.fire('Too Late!', 'Someone already claimed this ticket.', 'info');
                        loadUnassignedTickets();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                        $btn.removeClass('disabled').text('Take Over');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                    $btn.removeClass('disabled').text('Take Over');
                }
            });
        });
    });

    $("#btnComment").on('click', function(e){

        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var comment = $("#comment").val().trim();

        $btn.prop('disabled', true).text('Sending Comment...');

        $.ajax({
            url:'./api/add_comment.php',
            type:"POST",
            data: {
                ticket_id: ticketId,
                comment: comment,
            },
            dataType: 'JSON',
            success: function(response){
                console.log(response);
                if (response.status === 'success') {
                    Swal.fire('Suuccess', 'Comment sent successfully', 'success')
                        .then(function() { location.reload(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $btn.prop('disabled', false).text('Send Comment');
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                Swal.fire('Error!', 
                    'Something went wrong.' + error, 
                    'error');
                $btn.prop('disabled', false).text('Send Comment');
            }
        });
    });

    $("#btnMarkResolved").on('click',function() {
        var $btn = $(this);
        var ticketId = $btn.data('ticket-id');
        var notes = $("#resolutionNotes").val().trim();

        if (notes === '') {
            Swal.fire('Add Resolution Notes', 'Please describe what was done before marking this resolved.', 'warning');
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
                    Swal.fire('Resolved!', 'The ticket has been marked as resolved.', 'success')
                        .then(function() { location.reload(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $btn.prop('disabled', false).text('Mark as Resolved');
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                Swal.fire('Error!', 
                    'Something went wrong.' + error, 
                    'error');
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
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.reload();
                    });
                // Handle BOTH 'warning' and 'partial' status codes
                } else if (response.status === 'warning' || response.status === 'partial') {
                    Swal.fire({
                        title: 'Ticket Created',
                        text: response.message,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to process request.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Error: " + error);
                console.log("Ajax Error: ", xhr.responseText);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#save_ticket").on('click', function(e){
        e.preventDefault();

        const form = document.getElementById('frmCreateTicket');

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
                url: 'ajax/update_ticket.php', // adjust path to match your project structure
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response){
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Updated!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                        });
                    } else if (response.status === 'warning') {
                        Swal.fire({
                            title: 'Partial success',
                            text: response.message,
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Something went wrong.',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr){
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

    $('.close_ticket').on('click', function(){
        const ticketId = $(this).closest('.close_ticket').attr('close_ticket_id') || $(this).attr('close_ticket_id');

        Swal.fire({
            title: 'Close this ticket?',
            text: "This will mark the ticket as Closed. This action can be reversed later if needed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, close it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Closing ticket...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './api/close_ticket.php', // adjust path to match your project
                type: 'POST',
                data: { ticket_id: ticketId },
                dataType: 'json',
                success: function(response){
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Closed',
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
            url:"./api/add_users.php",
            type:"POST",
            data:{
                name: $("#name").val(),
                email: $("#email").val(),
                password: $("#password").val(),
                role:$("#role").val(),
                department:$("#department").val(),
                confirm_password:$("#confirm_password").val(),
            },
            dataType:'json',
            success:function(response){
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

    $("#save_password").on('click', function(e){
        e.preventDefault();

        var currentPassword = $('#current_password').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_password').val();

        if (newPassword !== confirmPassword) {
            Swal.fire({
                title: 'Error!',
                text: 'Passwords do not match',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

        $.ajax({
            url: "./api/save_password.php",
            type: 'POST',
            data: { 
                current_password:currentPassword,
                new_password:newPassword
            },
            dataType: 'JSON',
            success: function(response) { 
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK'
                });
                setTimeout(function(){
                    location.reload();
                }, 1000);
            },
            error: function(error) {
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
            url:'./api/add_categories.php',
            type:'POST',
            data:{
                category_code:$("#category_code").val(),
                category_name:$("#category_name").val(),
            },
            dataType:'json',
            success:function(response){
                console.log(response);
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK',
                });
                $("#add_category_modal").modal('hide');
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

    $("#save_category").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url:'./api/save_categories.php',
            type:'POST',
            data:{
                edit_category_id:$("#edit_category_id").val(),
                edit_category_code:$("#edit_category_code").val(),
                edit_category_name:$("#edit_category_name").val(),
            },
            dataType:'json',
            success:function(response){
                console.log(response);
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK', 
                });
                $(document.activeElement).blur();
                $("#edit_category_modal").modal('hide');
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

    $("#add_sla").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url:'./api/add_sla_rules.php',
            type:'POST',
            data:{
                category: $("#category").val(),
                priority: $("#priority").val(),
                response_minutes: $("#response_minutes").val(),
                resolution_hours: $("#resolution_hours").val(),
            },
            dataType:'json',
            success: function(response){
                console.log(response);
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK',
                });
                $(document.activeElement).blur();
                $("#add_sla_modal").modal('hide');
                setTimeout(function(){
                    location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.log("Status: " + status);
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Error: " + error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $("#save_sla").on('click', function(e){
        e.preventDefault();

        $.ajax({
            url:'./api/save_sla_rules.php',
            type: 'POST',
            data:{
                edit_sla_id:$("#edit_sla_id").val(),
                edit_sla_cat_id:$("#edit_sla_cat_id").val(),
                edit_sla_priority:$("#edit_sla_priority").val(),
                edit_sla_response_minutes:$("#edit_sla_response_minutes").val(),
                edit_sla_resolution_hours:$("#edit_sla_resolution_hours").val(),
            },
            dataType:'json',
            success: function(response){
                console.log(response);
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK',
                });
                $(document.activeElement).blur();
                $("#edit_category_modal").modal('hide');
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
        })
    });

    $("#btnLogin").on("click", function(e){
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
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (response.status === 'success') {
                        if (response.role == "IT Manager") {
                            window.location.href = "manager_dashboard.php";
                        } else if(response.role == "IT Supervisor"){
                            window.location.href = "supervisor_dashboard.php";
                        } else if (response.role == "IT Support Specialist") {
                            window.location.href = "support_dashboard.php";
                        } else {
                            window.location.href = "employee_dashboard.php";
                        }
                    }
                })
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

    $("#btnLogout").on("click", function(e){
        e.preventDefault();

        $.ajax({
            url: './api/logout.php',
            type: 'POST',
            dataType: 'json', 
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Logged Out',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
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
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#newPassword').val(pass);
});

$('#btnConfirmReset').on('click', function() {
    const newPassword = $('#newPassword').val();

    if (newPassword.length < 4) {
        Swal.fire({ icon: 'warning', title: 'Too short', text: 'Password must be at least 4 characters.' });
        return;
    }

    const $btn = $(this).prop('disabled', true).text('Resetting...');

    $.ajax({
        url: './api/request_reset_password.php',
        type: 'POST',
        dataType:'json',
        data: JSON.stringify({ user_id: currentResetUserId, new_password: newPassword }),
        contentType: 'application/json',
        success: function(response) {
            $btn.prop('disabled', false).text('Confirm Reset');
            if (response.status == 'success') {
                $('#resetPasswordModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Password Reset',
                    html: `New password for <strong>${$("#resetPasswordName").text()}</strong>:<br>
                           <code style="font-size:1.2em;">${$('<div>').text(newPassword).html()}</code><br>
                           <small>Share this with the user now — it won't be shown again.</small>`
                });
            } else {
                Swal.fire({ 
                    icon: response.status === 'error' ? 'error' : 'warning', 
                    title: 'Reset Failed', 
                    text: response.message || 'Could not reset password. Please try again.', 
                });
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX XHR: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            console.log("AJAX Error: " + error);

            $btn.prop('disabled', false).text('Confirm Reset');
            Swal.fire({ 
                icon: 'error', 
                title: 'Server Error',
                text: 'Please try again.' 
            });
        }
    });
});

$('.toggle_status').on('click', function () {
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
                if (res.success) {
                    if (res.newStatus == 1) {
                        btn.removeClass('btn-success').addClass('btn-danger')
                           .attr('data-status', 1)
                           .attr('title', 'Deactivate')
                           .html("<i class='fa fa-unlock fa-xs'></i>");
                    } else {
                        btn.removeClass('btn-danger').addClass('btn-success')
                           .attr('data-status', 0)
                           .attr('title', 'Activate')
                           .html("<i class='fa fa-lock fa-xs text-success'></i>");
                    }

                    const badge = btn.closest('tr').find('td').eq(/* status column index */2);
                    badge.html(res.newStatus == 1
                        ? "<span class='fa fa-solid fa-circle fa-xs text-success'></span> Active"
                        : "<span class='fa fa-solid fa-circle fa-xs text-muted'></span> Inactive");

                    Swal.fire({
                        title: 'Success',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX XHR: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                console.log("AJAX Error: " + error);
                Swal.fire('Error', 'Request failed. Please try again.', 'error');
            }
        });
    });
});

    $(document).on('click', '.notification-item', function(e) {
    if ($(e.target).closest('.decision-btn').length) return;

    const link = $(this).data('link');
    const notificationId = $(this).data('id'); // you'll need to add data-id="${item.id}" to the row HTML

    $.ajax({
        url: `./api/get_notifications.php?action=mark_one&id=${notificationId}`,
        type: 'GET'
    });

    window.location.href = link;
    });

  // Clicking the row navigates, unless a decision button was clicked
    $(document).on('click', '.notification-item', function(e) {
    if ($(e.target).closest('.decision-btn').length) return;
    window.location.href = $(this).data('link');
    });

    // Accept/decline via POST, not a GET link
    $(document).on('click', '.decision-btn', function() {
    const id = $(this).data('id');
    const decision = $(this).data('decision');
    $.ajax({
        url: './api/approve.php',
        type: 'POST',
        data: { id: id, action: decision },
        success: function() { loadNotifications(); }
    });
    });

  // Event handler for Mark All Read button
    $(document).on('click', '#markAllReadBtn', function(e) {
        e.preventDefault();
        $.ajax({
            url: './api/get_notifications.php?action=mark_all',
            type: 'GET',
            success: function() {
            loadNotifications();
            },
            error: function(){
                console.log("Error in loading notification");
            }
        });
    });

    // Initial Fetch & Auto Refresh setup
    loadNotifications();
    loadTicketQueue();
    loadUnassignedTickets();
    setInterval(loadNotifications, 5000); // Check every 5 seconds
    
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