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
                    'fields'=>array($project->table_pk,$settings[$module::SAMPLE_ID][0],$settings[$module::SAMPLE_FIELD],$trackField,$settings[$module::COLLECT_DATE],$settings[$module::PLANNED_TYPE],$settings[$module::ACTUAL_TYPE],$settings[$module::PLANNED_COLLECT],$settings[$module::ACTUAL_COLLECT],$settings[$module::PARTICIPANT_ID]), 'exportAsLabels' => true
                )
            ),true);

            if (empty($sampleData['errors']) && is_array($sampleData)) {
                foreach ($sampleData as $index => $sData) {
                    $sampleList[$sData[$project->table_pk]] = array(
                        'sample_id'=>$sData[$settings[$module::SAMPLE_ID][0]],"collect_date" => $sData[$settings[$module::COLLECT_DATE]],
                        'participant_id'=>$sData[$settings[$module::PARTICIPANT_ID]],'planned_collect'=>$sData[$settings[$module::PLANNED_COLLECT]],
                        'actual_collect'=>$sData[$settings[$module::ACTUAL_COLLECT]],'planned_type'=>$sData[$settings[$module::PLANNED_TYPE]],
                        'actual_type'=>$sData[$settings[$module::ACTUAL_TYPE]]
                    );
                }
            }
        }

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "load_sample") {
        $sampleData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[" . $settings[$module::SAMPLE_ID][0] . "] = '" . $record . "'",
                'fields' => array($project->table_pk, $settings[$module::SAMPLE_ID][0], $settings[$module::SAMPLE_FIELD], $settings[$module::COLLECT_DATE], $settings[$module::PLANNED_TYPE], $settings[$module::ACTUAL_TYPE], $settings[$module::PLANNED_COLLECT], $settings[$module::ACTUAL_COLLECT], $settings[$module::PARTICIPANT_ID]), 'exportAsLabels' => true
            )
        ), true);

        $sData = $sampleData[0];
        $sampleList[$sData[$project->table_pk]] = array(
            'sample_id'=>$sData[$settings[$module::SAMPLE_ID][0]],"collect_date" => $sData[$settings[$module::COLLECT_DATE]],
            'participant_id'=>$sData[$settings[$module::PARTICIPANT_ID]],'planned_collect'=>$sData[$settings[$module::PLANNED_COLLECT]],
            'actual_collect'=>$sData[$settings[$module::ACTUAL_COLLECT]],'planned_type'=>$sData[$settings[$module::PLANNED_TYPE]],
            'actual_type'=>$sData[$settings[$module::ACTUAL_TYPE]]
        );

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "save_sample") {
        $discrepChecks = db_real_escape_string($_POST['discreps']);
        $discrepOther = db_real_escape_string($_POST['discrep_other']);
        $destProject = new Project($project_id);
        $settings = $module->getModuleSettings($project_id);
        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID][0],$record);

        if ($recordID != "") {
            $saveData[0] = array(
                $destProject->table_pk => $recordID, $module::DISCREP_OTHER => $discrepOther
            );
            foreach ($discrepChecks as $dCheck) {
                $saveData[0][$module::DISCREP_FIELD."___".$dCheck] = 1;
            }
        }
    }
    elseif ($process == "shipping_info") {
        $trackNum = $_POST['track_num'];
        $trackField = $settings[$module::LOOKUP_FIELD][0];
        $shippingInfo = array();

        if ($trackNum != "" && $trackField != "") {
            $shipData = json_decode(\REDCap::getData(
                array(
                    'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[".$trackField."] = '".$trackNum."'",
                    'fields'=>array($project->table_pk,$settings[$module::SHIP_DATE],$settings[$module::SHIPPED_BY]), 'exportAsLabels' => true,

                )
            ),true);

            if (empty($shipData['errors']) && is_array($shipData)) {
                foreach ($shipData as $index => $sData) {
                    $shippingInfo[$sData[$project->table_pk]] = array(
                        'ship_date'=>$sData[$settings[$module::SHIP_DATE]],"shipped_by" => $sData[$settings[$module::SHIPPED_BY]]
                    );
                    if ($sData[$settings[$module::SHIP_DATE]] != "" && $sData[$settings[$module::SHIPPED_BY]] != "") {
                        break;
                    }
                }
            }
        }

        $tableHTML = json_encode($shippingInfo);
    }
}

echo $tableHTML;