<?php

$project_id = $_GET['pid'];
$report_index = (is_numeric($_POST['report_index']) ? (int)$_POST['report_index'] : null);
if (is_numeric($project_id)) {
	$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule();
	$module->loadTwigExtensions();
	echo $module->loadReportSetupTwig($project_id, $report_index);
}
