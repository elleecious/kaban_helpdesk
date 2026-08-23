function loadOverdueAlert() {
    $.ajax({
        url: "./actions/get_overdue_tickets.php",
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
            console.log("Ajax Error: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            $("#overdueAlertContainer").empty();
        }
    });
}

function loadAttentionAlert() {
    $.ajax({
        url: "./actions/get_attention_summary.php",
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
            console.log("Ajax Error: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            $("#attentionAlertContainer").html('<tr><td colspan="5" class="text-center text-danger">Failed to load tickets.</td></tr>');
        }
    });
}


$(document).ready(function() {

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
        url: "./actions/get_agents.php",
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

// Confirm assignment
$("#btnConfirmAssign").click(function() {

    var agentId = $("#assignAgentSelect").val();

    if (!agentId) {
        Swal.fire('Select an agent', 'Please choose an IT Support agent first.', 'warning');
        return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).text('Assigning...');

    $.ajax({
        url: "./actions/assign_ticket.php",
        type: "POST",
        data: { ticket_id: currentAssignTicketId, agent_id: agentId },
        dataType: 'JSON',
        success: function(response) {
            $btn.prop('disabled', false).text('Confirm Assign');

            if (response.status === 'success') {
                $('#assignTicketModal').modal('hide');
                Swal.fire('Assigned!', 'Ticket #' + currentAssignTicketId + ' has been assigned.', 'success')
                    .then(function() { location.reload(); }); // or remove the row via JS instead of a full reload
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        }
    });
});


    // Fetch and populate when the modal opens
$('#unassignedTicketsModal').on('show.bs.modal', function() {
    $("#unassignedTicketsBody").html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');

    $.ajax({
        url: "./actions/get_unassigned_tickets.php",
        type: "GET",
        dataType: 'JSON',
        success: function(response) {
            if (response.status === 'success' && response.tickets.length > 0) {
                var rows = '';
                response.tickets.forEach(function(t) {
                    var waitingClass = t.waiting_minutes > 60 ? 'text-danger font-weight-bold' : 'text-muted';
                    var priorityClass = 
                        t.priority === 'Critical' ? 'badge-danger' :
                        t.priority === 'High' ? 'badge-warning' :
                        t.priority === 'Medium' ? 'badge-info' : 'badge-secondary';

                    rows += `
                        <tr>
                            <td>#${t.ticket_number}</td>
                            <td>${t.subject}</td>
                            <td><span class="badge ${priorityClass}" style='font-size:1rem;'>${t.priority}</span></td>
                            <td class="${waitingClass}">${t.waiting}</td>
                            <td><button class="btn btn-sm btn-primary btn-claim" data-id="${t.id}">Claim</button></td>
                        </tr>`;
                });
                $("#unassignedTicketsBody").html(rows);
            } else {
                $("#unassignedTicketsBody").html('<tr><td colspan="5" class="text-center text-muted">No unassigned tickets right now.</td></tr>');
            }
        },
        error: function(xhr, status,error) {
            console.log("Ajax Error: " + xhr.responseText);
            console.log("Ajax Status: " + status);
            $("#unassignedTicketsBody").html('<tr><td colspan="5" class="text-center text-danger">Failed to load tickets.</td></tr>');
        }
    });
});

// Claim button — delegated event since rows are injected dynamically
    $("#claim_ticket").click(function(e) {
        
        e.preventDefault();

        var ticketId = $(this).data('id');
        var $row = $(this).closest('tr');
        var $btn = $(this);

        $btn.prop('disabled', true).text('Claiming...');

        $.ajax({
            url: "./actions/pickup_ticket.php",
            type: "POST",
            data: { ticket_id: ticketId },
            dataType: 'JSON',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Ticket Claimed!',
                        text: 'Ticket #' + response.ticket_id + ' has been assigned to you.',
                        icon: 'success',
                        confirmButtonText: 'View Ticket'
                    }).then(function() {
                        window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                    });
                } else if (response.status === 'already_claimed') {
                    Swal.fire('Too Late!', 'Someone already claimed this ticket.', 'info');
                    $row.fadeOut(300, function() { $(this).remove(); });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    $btn.prop('disabled', false).text('Claim');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Something went wrong claiming this ticket.', 'error');
                $btn.prop('disabled', false).text('Claim');
            }
        });
    });


    $("#add_ticket").click(function(e) {
        e.preventDefault(); // only strictly needed if this button is type="submit" inside a <form>

        var formData = new FormData($("#frmCreateTicket")[0]);

        formData.append("subject", $("#subject").val());
        formData.append("description", $("#description").val());
        formData.append("category", $("#category").val());
        formData.append("attachment", $("#attachment")[0].files[0]);

        $.ajax({
            url: "./actions/add_tickets.php",
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
                        window.location.href = 'ticket_detail.php?id=' + response.ticket_id;
                    });
                } else if (response.status === 'partial') {
                    Swal.fire({
                        title: 'Ticket Created',
                        text: response.message,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    $("#frmCreateTicket")[0].reset();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
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
    
    $("#add_users").click(function(e){
        e.preventDefault();

        $.ajax({
            url:"./actions/add_users.php",
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

    $("#add_category").click(function(e){
        
        e.preventDefault();

        $.ajax({
            url:'./actions/add_categories.php',
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

    $("#add_sla").click(function(e){
        e.preventDefault();

        $.ajax({
            url:'./actions/add_sla_rules.php',
            type:'POST',
            data:{
                category: $("#category").val(),
                priority: $("#priority").val(),
                response_hours: $("#response_hours").val(),
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

    $("#btnLogin").click(function(e){
        e.preventDefault();

        $.ajax({
            url: './actions/login.php',
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

    $("#btnLogout").click(function(e){
        e.preventDefault();

        $.ajax({
            url: './actions/logout.php',
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

    const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const data = {
    labels: labels,
    datasets: [{
      label: 'My First Dataset',
      data: [65, 59, 80, 81, 56, 55, 40],
      backgroundColor: [
        'rgba(255, 99, 132, 0.2)',
        'rgba(255, 159, 64, 0.2)',
        'rgba(255, 205, 86, 0.2)',
        'rgba(75, 192, 192, 0.2)',
        'rgba(54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
      ],
      borderColor: [
        'rgb(255, 99, 132)',
        'rgb(255, 159, 64)',
        'rgb(255, 205, 86)',
        'rgb(75, 192, 192)',
        'rgb(54, 162, 235)',
        'rgb(153, 102, 255)',
      ],
      borderWidth: 1
    }]
  };

  const config = {
    type: 'bar',
    data: data,
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    },
  };

  const ctx = document.getElementById('myChart');
  new Chart(ctx, config);


    
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