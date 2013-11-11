<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', -1);
error_reporting(-1);

include 'config.php';
include 'simple_html_dom.php';
include 'Azure.php';
include 'Amazon.php';

$db = new PDO('sqlite:status.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS status (id INTEGER PRIMARY KEY, CloudProvider TEXT, ServiceName TEXT, Region TEXT, Status INTEGER, Updated INTEGER)");

azureMonitor($db);
amazonMonitor($db);

$db = null;
?>