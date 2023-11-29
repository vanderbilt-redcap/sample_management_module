<?php

$project_id = $_POST['project_id'];
$record = $_POST['record'];
$process = db_real_escape_string($_POST['process']);
$tableHTML = "";

if ($project_id != "" && is_numeric($project_id)) {
    $event_id = db_real_escape_string($_POST['event_id']);
    $repeat_instance = db_real_escape_string($_POST['repeat_instance']);
    $currentValues = array();

    $module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project_id);
    $settings = $module->getModuleSettings($project_id);
    $project = new \Project($project_id);

    if ($process == "get_container_options") {
        $containerList = $module->getContainerList();

        $containers['options'] = "<option value=''></option>";
        foreach ($containerList as $record => $name) {
            $containers['options'] .= "<option value='" . $record . "'>" . $name . "</option>";
        }

        $tableHTML = json_encode($containers);
    }
    elseif ($process == "container_list") {
        $containerList = $module->getContainerList();
        $tableHTML = json_encode($containerList);
    }
    elseif ($process == "get_slot_options") {
        $availableSlots = array();
        $currentSlots = json_decode($_POST['currentSlots'],true);
        $slotInfo = $module->getContainerSlots(array($record),true);
        foreach ($slotInfo as $info) {
            $availableSlots[$info['index']."_".$info['project_id']."_".$info['record']."_".$info['event']."_".$info['instance']] = $info['slot'];
        }
        $slots['options'] = "";
        $slots['inputs'] = "";
        foreach ($currentSlots as $fieldName => $slotSettings) {
            if (is_array($slotSettings) && !empty($slotSettings)) {
                $slots['inputs'] .= "<input type='hidden' name='".$slotSettings['value']."' value='".$slotSettings['label']."' />";
            }
        }
        foreach ($availableSlots as $value => $label) {
            $slots['options'] .= "<option value='".$value."'>$label</option>";
            $slots['inputs'] .= "<input type='hidden' name='$value' value='$label' />";
        }

        $tableHTML = json_encode($slots);
    }
    elseif ($process == "slot_info") {
        $allSlots = $module->getContainerSlots(array($record));
        $tableHTML = json_encode($allSlots);
    }
    elseif ($process == "sample_list" && isset($_POST['track_num'])) {
        $trackNum = $_POST['track_num'];
        $trackField = $settings[$module::LOOKUP_FIELD][0];
        $sampleList = array();

        if ($trackNum != "" && $trackField != "") {
            $sampleData = json_decode(\REDCap::getData(
                array(
                    'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[".$trackField."] = '".$trackNum."'",
                    'fields'=>array($project->table_pk,$settings[$module::SAMPLE_ID][0],$settings[$module::SAMPLE_FIELD],$trackField), 'exportAsLabels' => true
                )
            ),true);

            if (empty($sampleData['errors']) && is_array($sampleData)) {
                foreach ($sampleData as $record => $sData) {
                    $sampleList[$sData[$project->table_pk]] = array('sample_id'=>$sData[$settings[$module::SAMPLE_ID][0]]);
                }
            }
        }

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "save_sample") {
        $discrepChecks = db_real_escape_string($_POST['discreps']);
        $discrepOther = db_real_escape_string($_POST['discrep_other']);
        $destProject = new Project($project_id);
        $settings = $module->getModuleSettings($project_id);
        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID],$record);

        if ($recordID != "") {
            $saveData[0] = array(
                $destProject->table_pk => $recordID, $module::DISCREP_OTHER => $discrepOther
            );
            foreach ($discrepChecks as $dCheck) {
                $saveData[0][$module::DISCREP_FIELD."___".$dCheck] = 1;
            }
        }
    }
}

echo $tableHTML;