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
    const REPORT_DESCRIP = 'report-descrip';
    const REPORT_FILTERS = 'report-filters';

    public function __construct(int $project_id)
    {
        $this->module = new SampleManagementModule($project_id);
        $this->reportList = json_decode($this->module->getProjectSetting(self::REPORT_LIST, $this->module->getProjectId()), true);
    }

    public function setCurrentReport(int $reportID):void
    {
        $this->currentReport = $this->loadReportSettings($reportID);
    }

    public function getReportList()
    {
        return $this->reportList;
    }

    public function loadReportSettings(int|null $reportID):array
    {
        $reportInfo = json_decode($this->module->getProjectSetting(self::REPORT_INFO . '-' . $reportID, $this->module->getProjectId()), true);
        $filterInfo = json_decode($this->module->getProjectSetting(self::REPORT_FILTERS . '-' . $reportID, $this->module->getProjectId()), true);
        $linkInfo = json_decode($this->module->getProjectSetting(self::REPORT_LINK . '-' . $reportID, $this->module->getProjectId()), true);
        $descripInfo = json_decode($this->module->getProjectSetting(self::REPORT_DESCRIP . '-' . $reportID, $this->module->getProjectId()), true);
        $returnArray = array('id' => $reportID, 'name' => $this->reportList[$reportID] ?? '', 'descrip' => $descripInfo, 'info' => $reportInfo, 'filters' => $filterInfo, 'links' => $linkInfo);

        return $returnArray;
    }

    public function loadReportSetupTwig(int|null $report_index)
    {
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

    public function saveReportSettings(int $reportID, array $reportSettings):string
    {
        $result = '';

        if (!isset($this->reportlist[$reportID])) {
            $this->reportList[$reportID] = $this->module->escape($reportSettings['report_name']);
        }
        $this->module->setProjectSetting(self::REPORT_LIST, json_encode($this->reportList));
        $this->module->setprojectSetting(self::REPORT_DESCRIP.'-'.$reportID, json_encode($this->module->escape($reportSettings['report_descrip'])));

        return $result;
    }
}
