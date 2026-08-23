<!-- include connect for database communication -->
<?php include("connect.php");?>
<!-- include url for url identification-->
<?php include("url.php");?>

<!-- top element/ do not touch -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Internal IT ticketing and support system for Kaban Hotel and Casino Boracay. Submit, track, and resolve IT support requests across Hotel and Casino operations.">
    <meta name="author" content="OGELC and GHLI IT Department">
    <meta name="robots" content="noindex, nofollow">
    <meta name="title" content="Kaban Helpdesk | IT Support & Ticketing System" />
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kaban Helpdesk">
    <meta property="og:description" content="Internal IT ticketing system for Kaban Hotel and Casino Boracay — submit and track support requests.">
    <meta property="og:site_name" content="Kaban Helpdesk">
    <meta name="application-name" content="Kaban Helpdesk">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/mdb.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url ?>assets/css/bootstrap-select.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/addons/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/hover.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $short_url;?>assets/css/style_v2.css">
    <link rel="shortcut icon" href="<?php echo $short_url;?>assets/img/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .poppins-light { font-family: "Poppins", sans-serif; font-weight: 300; font-style: normal; }
        .betson-color { background-color: #002E5D; }
        header { background-color: #002E5D; padding: 20px; text-align: center; font-size: 24px; color: #fff; }
        .side-nav { background-color: #002E5D !important; }
    </style>
</head>
<body class="poppins-light fixed-sn">