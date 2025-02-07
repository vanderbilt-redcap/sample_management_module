<?php

namespace Vanderbilt\SampleManagementModule;

use ExternalModules\ExternalModules;
use Twig\Environment;
use ExternalModules\AbstractExternalModule;

class DataReport
{
	/** @var SampleManagementModule */
	public $module;
	private $currentReport;
	private $reportList;

	const REPORT_LINK = 'report-link';
	const REPORT_INFO = 'report-info';
	const REPORT_LIST = 'report-list';
	const REPORT_FILTERS = 'report-filters';

	public function __construct(int $project_id)
	{
		$this->module = new SampleManagementModule($project_id);
		$this->reportList = json_decode($this->module->getProjectSetting('report-list',$this->module->getProjectId()),true);
	}

	public function setCurrentReport(int $reportID) {
		$this->currentReport = $this->loadReportSettings($reportID);
	}

	public function getReportList() {
		return $this->reportList;
	}

	public function loadReportSettings(int|null $reportID) {
		if (isset($this->reportList[$reportID])) {
			$reportInfo = json_decode($this->module->getProjectSetting('report-information-'.$reportID,$this->module->getProjectId()),true);
			$filterInfo = json_decode($this->module->getProjectSetting('report-filters-'.$reportID,$this->module->getProjectId()),true);
			$linkInfo = json_decode($this->module->getProjectSetting('report-links-'.$reportID,$this->module->getProjectId()),true);
			$returnArray = array($reportID => array('name'=>$this->reportList[$reportID],'info'=>$reportInfo,'filters'=>$filterInfo,'links'=>$linkInfo));
		}
		return $returnArray ?? [];
	}

	public function loadReportSetupTwig(int|null $report_index) {
		//$reportList = $this->getAllReportNames($project_id);
		//$reportData = $this->buildReportTable($project_id, $report_index);

		$projectIDs = $this->module->getUserProjectIDs();
		$projectList = $this->module->getProjectNames($projectIDs);
		$reportList = $this->getReportList();
		$reportData = $this->loadReportSettings($report_index);

		return $this->module->getTwig()->render('report_setup.html.twig', [
			'report_list' => $reportList,
			'project_list' => $projectList,
			'report_data' => $reportData
		]);
	}
}
