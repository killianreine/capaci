<?php

// Keep generated URLs at the deployment root instead of exposing /api.
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/../public/index.php';
