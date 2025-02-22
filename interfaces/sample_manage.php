<?php

use Vanderbilt\SampleManagementModule\SampleManagementModule;

require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project = new Project((int)$_GET['pid']);
$module = new SampleManagementModule($project->project_id);

$shippingData = array();
$trackingNum = "";
$ajaxUrl = $module->getUrl('interfaces/ajax.php');
