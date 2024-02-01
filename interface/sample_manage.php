<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project = new \Project((int)$_GET['pid']);
$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project->project_id);

$shippingData = array();
$trackingNum = "";
$ajaxUrl = $module->getUrl('interface/ajax.php');