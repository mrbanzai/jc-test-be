<?php
    // Don't attempt to process existing files or static files
    // (except for theme.css, it's dynamically-generated)
	if (file_exists(dirname(__FILE__) . $_SERVER['REQUEST_URI']) ||
        (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico)$/', $_SERVER['REQUEST_URI'])) &&
          strpos($_SERVER['REQUEST_URI'], '/theme.css') === false)
		return false;

    putenv('ENVIRONMENT=local');
	include('index.php');