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

    if ($process == "get_shipping_ids") {
        $trackList = array();
        $trackField = $settings[$module::LOOKUP_FIELD];
        $trackData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => '['.$trackField.'] != ""',
                'fields'=>array($project->table_pk,$trackField), 'exportAsLabels' => true
            )
        ),true);

        if (empty($trackData['errors']) && is_array($trackData)) {
            foreach ($trackData as $index => $tData) {
                if (in_array($tData[$trackField],$trackList)) continue;
                $trackList[$tData[$project->table_pk]] = $tData[$trackField];
            }
        }
        $tableHTML = json_encode($trackList);
    }
    elseif ($process == "get_container_options") {
        $containerList = $module->getContainerList();

        $containers['options'] = "<option value=''></option>";
        foreach ($containerList as $record => $container) {
            $containers['options'] .= "<option value='" . $record . "'>" . $container['name'] . "</option>";
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
            $availableSlots[$info['project_id']."_".$info['record']."_".$info['event']."_".$info['instance']] = $info['slot'];
        }
        $slots['options'] = "";
        $slots['inputs'] = "";

        foreach ($currentSlots as $fieldName => $slotSettings) {
            if (is_array($slotSettings) && !empty($slotSettings)) {
                $slots['inputs'] .= "<input type='hidden' name='".implode("_",$slotSettings['value'])."' value='".$slotSettings['label']."' />";
                $slots['options'] .= "<option value='".implode("_",$slotSettings['value'])."'>".$slotSettings['label']."</option>";
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
        $destProject = new Project($project_id);
        $destMeta = $destProject->metadata;
        $trackNum = $_POST['track_num'];
        $trackField = $settings[$module::LOOKUP_FIELD];
        $sampleList = array();

        if ($trackNum != "" && $trackField != "") {
            $sampleData = json_decode(\REDCap::getData(
                array(
                    'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[".$trackField."] = '".$trackNum."'",
                    'fields'=>array($project->table_pk,$settings[$module::SAMPLE_ID],$settings[$module::SAMPLE_FIELD],$trackField,$settings[$module::COLLECT_DATE],$settings[$module::PLANNED_TYPE],$settings[$module::ACTUAL_TYPE],$settings[$module::PLANNED_COLLECT],$settings[$module::ACTUAL_COLLECT],$settings[$module::PARTICIPANT_ID],$settings[$module::DISCREP_OTHER],$settings[$module::ASSIGN_FIELD],$settings[$module::DISCREP_FIELD]), 'exportAsLabels' => true
                )
            ),true);

            if (empty($sampleData['errors']) && is_array($sampleData)) {
                foreach ($sampleData as $index => $sData) {
                    $sampleRecordID = $sData[$project->table_pk]; 
                    $sampleList[$sampleRecordID] = $module->getContainerInfoFromSetting($project_id, $sData[$settings[$module::ASSIGN_FIELD]]);
                    $sampleList[$sampleRecordID]['sample_id'] = $sData[$settings[$module::SAMPLE_ID]];

                    $discrepField = $settings[$module::DISCREP_FIELD];
                    $discrepOtherField = $settings[$module::DISCREP_OTHER];
                    $sampleList[$sampleRecordID]['discrep'] = "";
                    $sampleList[$sampleRecordID]['discrep_other'] = $sData[$discrepOtherField];
                    if ($destMeta[$discrepField]['element_enum'] != "") {
                        $fieldLabels = $module->processFieldEnum($destMeta[$discrepField]['element_enum']);
                    }
                    foreach ($fieldLabels as $value => $label) {
                        if ($sData[$discrepField . "___" . (int)$value] == 1) {
                            $sampleList[$sampleRecordID]['discrep'] .= $label . "<br/>";
                        }
                    }
                }
            }
        }

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "load_sample") {
        $sampleData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[" . $settings[$module::SAMPLE_ID] . "] = '" . $record . "'",
                'fields' => array($project->table_pk, $settings[$module::SAMPLE_ID], $settings[$module::SAMPLE_FIELD], $settings[$module::COLLECT_DATE], $settings[$module::PLANNED_TYPE], $settings[$module::ACTUAL_TYPE], $settings[$module::PLANNED_COLLECT], $settings[$module::ACTUAL_COLLECT], $settings[$module::PARTICIPANT_ID]), 'exportAsLabels' => true
            )
        ), true);

        foreach ($sampleData as $sData) {
            $sampleList[$sData[$project->table_pk]] = array(
                'sample_id' => $sData[$settings[$module::SAMPLE_ID]], "collect_date" => $sData[$settings[$module::COLLECT_DATE]],
                'participant_id' => $sData[$settings[$module::PARTICIPANT_ID]], 'planned_collect' => $sData[$settings[$module::PLANNED_COLLECT]],
                'actual_collect' => $sData[$settings[$module::ACTUAL_COLLECT]], 'planned_type' => $sData[$settings[$module::PLANNED_TYPE]],
                'actual_type' => $sData[$settings[$module::ACTUAL_TYPE]]
            );
        }

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "save_sample") {
        $discrepChecks = $_POST['discreps'];
        $discrepOther = db_real_escape_string($_POST['discrep_other']);
        $slotSetting = db_real_escape_string($_POST['slot_setting']);
        $slotLabel = db_real_escape_string($_POST['slot_label']);
        $destProject = new Project($project_id);
        $returnData = array("stored"=>false,"discreps"=>"");

        $settings = $module->getModuleSettings($project_id);
        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID],$record);
        $saveData = array(0 => array());

        $destMeta = $destProject->metadata;
        $fieldLabels = array();

        if ($recordID != "") {
            foreach ($settings[$module::DISCREP_FIELD] as $index => $discrepField) {
                $saveData[0] = array(
                    $destProject->table_pk => $recordID, $settings[$module::DISCREP_OTHER][$index] => $discrepOther
                );
                if ($destMeta[$discrepField]['element_enum'] != "") {
                    $fieldLabels = $module->processFieldEnum($destMeta[$discrepField]['element_enum']);
                }
                foreach ($discrepChecks as $dCheck) {
                    $saveData[0][$discrepField . "___" . (int)$dCheck] = 1;
                    $returnData['discreps'] .= $fieldLabels[$dCheck] . "<br/>";
                }
            }


            if (isset($settings[$module::ASSIGN_FIELD]) && isset($settings[$module::SAMPLE_ID]) && isset($settings[$module::ASSIGN_CONTAIN])) {
                $assignField = $settings[$module::ASSIGN_FIELD];
                $slotField = $settings[$module::ASSIGN_CONTAIN];
                $invenProject = new \Project($settings[$module::INVEN_PROJECT]);

                $destRecord = $module->saveSample($project_id, $recordID, $event_id, $repeat_instance, $assignField, explode("_", $slotSetting), $record, $slotLabel);
                $saveData[0][$assignField] = $slotSetting;
                $saveData[0][$slotField] = $destRecord;
                $saveData[0][$destProject->table_pk] = $recordID;
            }

            if (!empty($saveData[0])) {
                $result = REDCap::saveData(
                    $destProject->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false
                );
            }

            if (empty($result['errors'])) {
                $returnData['stored'] = ($slotSetting != "");
            }
        }

        $tableHTML = json_encode($returnData);
    }
    elseif ($process == "shipping_info") {
        $trackNum = $_POST['track_num'];
        $trackField = $settings[$module::LOOKUP_FIELD];
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
                    $shippingInfo = array(
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
    elseif ($process == "checkout_sample") {
        $slotSetting = db_real_escape_string($_POST['slot_setting']);
        $slotOptions = explode("_",$slotSetting);

        $settings = $module->getModuleSettings($project_id);
        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID],$record);
        $destProject = new Project($project_id);

        $saveData = array(0 => array());

        if ($recordID != "") {
            foreach ($settings[$module::DISCREP_FIELD] as $index => $discrepField) {
                $saveData[0] = array(
                    $destProject->table_pk => $recordID
                );
            }


            if (isset($settings[$module::ASSIGN_FIELD]) && isset($settings[$module::SAMPLE_ID]) && isset($settings[$module::ASSIGN_CONTAIN])) {
                $assignField = $settings[$module::ASSIGN_FIELD];
                $slotField = $settings[$module::ASSIGN_CONTAIN];
                $invenProject = new \Project($settings[$module::INVEN_PROJECT]);

                $destRecord = $module->saveSample($project_id, $recordID, $destProject->firstEventId, "", $assignField, array(), $record);
                $saveData[0][$assignField] = "";
                $saveData[0][$slotField] = "";
                $saveData[0][$destProject->table_pk] = $record;
            }

            if (!empty($saveData[0])) {
                $result = REDCap::saveData(
                    $destProject->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false
                );
            }

            if (empty($result['errors'])) {
                $returnData['stored'] = ($slotSetting != "");
            }
        }

        $tableHTML = json_encode($returnData);
    }
}

echo $tableHTML;