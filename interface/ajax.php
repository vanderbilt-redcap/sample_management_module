<?php

$project_id = \ExternalModules\ExternalModules::escape($_POST['project_id']);
$record = \ExternalModules\ExternalModules::escape($_POST['record']);
$process = \ExternalModules\ExternalModules::escape($_POST['process']);
$tableHTML = "";

if ($project_id != "" && is_numeric($project_id)) {
    $module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project_id);
    $event_id = \ExternalModules\ExternalModules::escape($_POST['event_id']);
    $repeat_instance = \ExternalModules\ExternalModules::escape($_POST['repeat_instance']);
    $currentValues = array();

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
        $currentSlots = json_decode(\ExternalModules\ExternalModules::escape($_POST['currentSlots']),true);
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
        $trackNum = \ExternalModules\ExternalModules::escape($_POST['track_num']);
        $trackField = $settings[$module::LOOKUP_FIELD];
        $manifestFields = $settings[$module::MANIFEST_FIELDS] ?? array();
        $sampleList = array();

        if ($trackNum != "" && $trackField != "") {
            $sampleData = json_decode(\REDCap::getData(
                array(
                    'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[".$trackField."] = '".$trackNum."'",
                    'fields'=>array_merge($manifestFields,array($project->table_pk,$trackField,$settings[$module::DISCREP_OTHER],$settings[$module::ASSIGN_FIELD],$settings[$module::DISCREP_FIELD])), 'exportAsLabels' => true
                )
            ),true);

            if (empty($sampleData['errors']) && is_array($sampleData)) {
                foreach ($sampleData as $index => $sData) {
                    $sampleRecordID = $sData[$project->table_pk]; 
                    $storageInfo = $module->getContainerInfoFromSetting($project_id, $sData[$settings[$module::ASSIGN_FIELD]]);
                    $sampleList[$sampleRecordID]['sample_id'] = $sData[$settings[$module::SAMPLE_ID]];
                    $stored = $storageInfo['container']." ".$storageInfo['slot'];

                    $discrepField = $settings[$module::DISCREP_FIELD];
                    $discrepOtherField = $settings[$module::DISCREP_OTHER];
                    $discrepencies = "";
                    if ($destMeta[$discrepField]['element_enum'] != "") {
                        $fieldLabels = $module->processFieldEnum($destMeta[$discrepField]['element_enum']);
                    }
                    foreach ($fieldLabels as $value => $label) {
                        if ($sData[$discrepField . "___" . (int)$value] == 1) {
                            $discrepencies .= $label . "<br/>";
                        }
                    }
                    if ($discrepencies != "" && $sData[$discrepOtherField] != "") {
                        $discrepencies .= $sData[$discrepOtherField];
                    }
                    $sampleList['field_list'] = array_merge(array('sample__store','sample__status'),$manifestFields);
                    if (!isset($sampleList[$sampleRecordID]['sample__store'])) {
                        $sampleList['headers']['sample__store'] = "Storage";
                        $sampleList['data'][$sampleRecordID]['sample__store'] = "";
                    }
                    $sampleList['data'][$sampleRecordID]['sample__store'] = $stored;
                    if (!isset($sampleList['data'][$sampleRecordID]['sample__status'])) {
                        $sampleList['headers']['sample__status'] = "Status";
                        $sampleList['data'][$sampleRecordID]['sample__status'] = "";
                    }
                    $sampleList['data'][$sampleRecordID]['sample__status'] = ($stored != " " ? "Stored: $stored": "").($discrepencies != "" ? "<br/>Discrepencies:<br/>$discrepencies" : "");
                    foreach ($manifestFields as $manifestField) {
                        if (!isset($sampleList['headers'][$manifestField])) {
                            $sampleList['headers'][$manifestField] = $destMeta[$manifestField]['element_label'];
                        }
                        $sampleList['data'][$sampleRecordID][$manifestField] = $sData[$manifestField];
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
                'fields' => array($project->table_pk, $settings[$module::SAMPLE_ID], $settings[$module::SAMPLE_FIELD], $settings[$module::COLLECT_DATE], $settings[$module::PLANNED_TYPE],
                    $settings[$module::ACTUAL_TYPE], $settings[$module::PLANNED_COLLECT], $settings[$module::ACTUAL_COLLECT], $settings[$module::PARTICIPANT_ID],$settings[$module::DISCREP_FIELD],$settings[$module::DISCREP_OTHER]),
                'exportAsLabels' => true
            )
        ), true);

        $sampleList = [];
        $discrepField = $settings[$module::DISCREP_FIELD];
        $destMeta = $project->metadata;

        if ($destMeta[$discrepField]['element_enum'] != "") {
            $fieldLabels = $module->processFieldEnum($destMeta[$discrepField]['element_enum']);
        }

        foreach ($sampleData as $sData) {
            $sampleList[$sData[$project->table_pk]] = array(
                'sample_id' => $sData[$settings[$module::SAMPLE_ID]], "collect_date" => $sData[$settings[$module::COLLECT_DATE]],
                'participant_id' => $sData[$settings[$module::PARTICIPANT_ID]], 'planned_collect' => $sData[$settings[$module::PLANNED_COLLECT]],
                'actual_collect' => $sData[$settings[$module::ACTUAL_COLLECT]], 'planned_type' => $sData[$settings[$module::PLANNED_TYPE]],
                'actual_type' => $sData[$settings[$module::ACTUAL_TYPE]], 'discrep_other' => $sData[$settings[$module::DISCREP_OTHER]]
            );
            foreach ($fieldLabels as $index => $value) {
                $sampleList[$sData[$project->table_pk]]['discreps'][$index] = array('label'=>$value,'value'=>strtolower($sData[$discrepField."___".$index]));
            }
        }

        $tableHTML = json_encode($sampleList);
    }
    elseif ($process == "save_sample") {
        $discrepChecks = \ExternalModules\ExternalModules::escape($_POST['discreps']);
        $discrepOther = \ExternalModules\ExternalModules::escape($_POST['discrep_other']);
        $slotSetting = \ExternalModules\ExternalModules::escape($_POST['slot_setting']);
        $slotLabel = \ExternalModules\ExternalModules::escape($_POST['slot_label']);
        $returnData = array("stored"=>false,"discreps"=>"");

        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID],$record);
        $saveData = array(0 => array());

        $destMeta = $project->metadata;
        $fieldLabels = array();

        if ($recordID != "") {
            $saveData[0][$project->table_pk] = $recordID;

            if (!is_null($settings[$module::DISCREP_FIELD])) {
                $discrepField = $settings[$module::DISCREP_FIELD];
                if ($destMeta[$discrepField]['element_enum'] != "") {
                    $fieldLabels = $module->processFieldEnum($destMeta[$discrepField]['element_enum']);
                }
                foreach ($fieldLabels as $value => $label) {
                    if (!empty($discrepChecks[$value])) {
                        $saveData[0][$discrepField . "___" . (int)$value] = 1;
                        $returnData['discreps'] .= $label . "<br/>";
                    }
                    else {
                        $saveData[0][$discrepField . "___" . (int)$value] = 0;
                    }
                }
                foreach ($discrepChecks as $dCheck) {
                    $saveData[0][$discrepField . "___" . (int)$dCheck] = 1;
                    $returnData['discreps'] .= $fieldLabels[$dCheck] . "<br/>";
                }
            }
            if (!is_null($settings[$module::DISCREP_OTHER])) {
                $discrepOtherField = $settings[$module::DISCREP_OTHER];
                $saveData[0][$discrepOtherField] = $discrepOther;
            }


            if (isset($settings[$module::ASSIGN_FIELD]) && isset($settings[$module::SAMPLE_ID]) && isset($settings[$module::ASSIGN_CONTAIN])) {
                $assignField = $settings[$module::ASSIGN_FIELD];
                $slotField = $settings[$module::ASSIGN_CONTAIN];
                $invenProject = new \Project($settings[$module::INVEN_PROJECT]);

                list($destRecord,$currentStoreSetting) = $module->saveSample($project_id, $recordID, $event_id, $repeat_instance, $assignField, explode("_", $slotSetting), $record, $slotLabel);
                $returnData['previous_slot'] = $currentStoreSetting['project']."_".$currentStoreSetting['record']."_".$currentStoreSetting['event']."_".$currentStoreSetting['instance'];
                $saveData[0][$assignField] = $slotSetting;
                $saveData[0][$slotField] = $destRecord;
                $saveData[0][$project->table_pk] = $recordID;
            }

            if (!empty($saveData[0])) {
                $result = REDCap::saveData(
                    $project->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false
                );
            }

            $returnData['results'] = $result;
            $returnData['data'] = $saveData;
            if (empty($result['errors'])) {
                $returnData['stored'] = ($slotSetting != "");
            }
        }

        $tableHTML = json_encode($returnData);
    }
    elseif ($process == "shipping_info") {
        $trackNum = \ExternalModules\ExternalModules::escape($_POST['track_num']);
        $trackField = $settings[$module::LOOKUP_FIELD];

        $shippingInfo = array();

        if ($trackNum != "" && $trackField != "") {
            $shipData = json_decode(\REDCap::getData(
                array(
                    'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[".$trackField."] = '".$trackNum."'",
                    'fields'=>array($settings[$module::SHIP_DATE],$settings[$module::SHIPPED_BY]), 'exportAsLabels' => true,

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
        $slotSetting = \ExternalModules\ExternalModules::escape($_POST['slot_setting']);
        $slotOptions = explode("_",$slotSetting);

        $settings = $module->getModuleSettings($project_id);
        $recordID = $module->getRecordByField($project_id,$settings[$module::SAMPLE_ID],$record);
        $destProject = new Project($project_id);

        $saveData = array(0 => array());
        $returnData = array("removed"=>false);

        if ($recordID != "") {
            if (isset($settings[$module::ASSIGN_FIELD]) && isset($settings[$module::SAMPLE_ID]) && isset($settings[$module::ASSIGN_CONTAIN])) {
                $assignField = $settings[$module::ASSIGN_FIELD];
                $slotField = $settings[$module::ASSIGN_CONTAIN];
                $invenProject = new \Project($settings[$module::INVEN_PROJECT]);

                list($destRecord,$currentStoreSetting) = $module->saveSample($project_id, $recordID, $destProject->firstEventId, "", $assignField, array(), $record);
                $saveData[0][$assignField] = "";
                $saveData[0][$slotField] = "";
                $saveData[0][$destProject->table_pk] = $recordID;
            }

            if (!empty($saveData[0])) {
                $result = REDCap::saveData(
                    $destProject->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false
                );
            }

            if (empty($result['errors'])) {
                $returnData['removed'] = true;
            }
        }

        $tableHTML = json_encode($returnData);
    }
}

echo $tableHTML;