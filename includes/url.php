<?php
	// custom urls can be added here
	// define urls here
	$url=explode("/", $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	$short_url="http://".$url[0]."/".$url[1]."/";
?>