<?php
    include('../config/connect.php');
    include('../library/functions.php');

    header('Content-Type: application/json');

    $year = date("Y");
    $crfNumber = generateCrfNumber($year);

    echo json_encode(['crf_number' => $crfNumber]);

?>