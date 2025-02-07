<?php

$project_id = $_GET['pid'];
$report_index = (is_numeric($_POST['report_index']) ? (int)$_POST['report_index'] : null);
if (is_numeric($project_id)) {
	$reportClass = new \Vanderbilt\SampleManagementModule\DataReport($project_id);
	$reportClass->module->loadTwigExtensions();
	echo $reportClass->loadReportSetupTwig($report_index);
}
