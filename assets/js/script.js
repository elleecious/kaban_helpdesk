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
        url: "./api/assign_ticket.php",
        type: "POST",
        data: { ticket_id: currentAssignTicketId, agent_id: agentId },
        dataType: 'JSON',
        success: function(response) {
            console.log(response);
            $btn.prop('disabled', false).text('Confirm Assign');

            if (response.status === 'success') {
                $('#assignTicketModal').modal('hide');
                Swal.fire('Assigned!', 'Ticket #' + currentAssignTicketId + ' has been assigned.', 'success')
                    .then(function() { location.reload(); });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Confirm Assign');
            Swal.fire('Error!', 'Something went wrong assigning this ticket.', 'error');
            console.log(xhr.responseText); // helps you see the actual PHP error while debugging
        }
    });
});


// Claim button — delegated event since rows are injected dynamically
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
                    Swal.fire({
                        title: 'Ticket Claimed!',
                        text: 'Ticket #' + response.ticket_id + ' has been assigned to you.',
                        icon: 'success',
                        confirmButtonText: 'View Ticket'
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
            error: function() {
                Swal.fire('Error!', 'Something went wrong claiming this ticket.', 'error');
                $btn.prop('disabled', false).text('Claim');
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
            error: function() {
                Swal.fire('Error!', 'Something went wrong.', 'error');
                $btn.prop('disabled', false).text('Mark as Resolved');
            }
        });
    });

    $('#category').on('change', function () {
        if ($(this).val() === 'Others') {
            $('#other_category_container').removeClass('d-none');
            $('#other_category').prop('required', true);
        } else {
            $('#other_category_container').addClass('d-none');
            $('#other_category').prop('required', false);
            $('#other_category').val('');
        }
    });

    $("#add_ticket").on("click", function(e) {
        e.preventDefault();

        var formElement = $("#frmCreateTicket")[0];
        var formData = new FormData(formElement);

        // Only append attachment manually if it is NOT inside #frmCreateTicket
        // AND a file is actually selected.
        var fileInput = $("#attachment")[0];
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            // Only needed if #attachment is outside the <form id="frmCreateTicket">
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