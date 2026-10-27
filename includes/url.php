<?php

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$short_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/";

?>