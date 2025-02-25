<?php

use Vanderbilt\SampleManagementModule\DataReport;

$project_id = $_GET['pid'];
$report_index = (is_numeric($_POST['report_index']) ? (int)$_POST['report_index'] : null);
echo "<pre>";
print_r($_POST);
echo "</pre>";
if (is_numeric($project_id)) {
	$reportClass = new DataReport($project_id);
	$reportClass->module->loadTwigExtensions();
	$reportClass->module->addJS('js/report_setup.js');

	if (isset($_POST['report_submit'])) {
		$reportClass->saveReportSettings($report_index,$_POST);
	}

	echo $reportClass->loadReportSetupTwig($report_index);
}
