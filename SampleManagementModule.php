<?php

namespace Vanderbilt\SampleManagementModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

class SampleManagementModule extends AbstractExternalModule
{
    const INVEN_PROJECT = "inven-project";
    const CONTAIN_FIELD = "container-field";
    const SAMPLE_FIELD = "sample-field";
    const STORE_FIELD = "can-store";
    const ASSIGN_CONTAIN = "assign-contain";
    const ASSIGN_FIELD = "assign-field";
    const CONTAIN_LABEL = "container-label";
    const STORE_LABEL = "storage-label";
    const SAMPLE_ID = "sample-id";
    const LOOKUP_FIELD = "lookup-field";
    const COLLECT_EVENT = "collect-event";
    const SAMPLE_TYPE = "sample-type";
    const SHIPPED_BY = "shipped-by";
    const SHIP_DATE = "ship-date";
    const DISCREP_FIELD = "discrepancy-field";
    const DISCREP_OTHER = "discrepancy-other";
    const PARTICIPANT_ID = "participant-id";
    const PLANNED_COLLECT = "planned-collect";
    const ACTUAL_COLLECT = "actual-collect";
    const COLLECT_DATE = "collect-date";
    const PLANNED_TYPE = "planned-type";
    const ACTUAL_TYPE = "actual-type";

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance = 1) {
        /*$settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        $inventoryData = \Records::getData(
            array(
                'return_format' => 'json',
                'records' => array('1'), 'project_id' => $invenProject->project_id
            )
        );
        echo "<pre>";
        print_r(json_decode($inventoryData,true));
        echo "</pre>";*/

        //$this->replaceFields($project_id,$record,$event_id,$repeat_instance,$instrument);
        $printJava = $this->buildJavascript($project_id,$record,$event_id,$repeat_instance,$instrument);
        /*echo "<pre>";
        print_r($containerNames);
        echo "</pre>";*/
        echo $printJava;

        echo "<script>
        $(document).ready(function() {
            $('#form').append('<div id=\"sample_slots\"></div>');
            getSampleContainers('".$project_id."','".$record."','".$event_id."','".$repeat_instance."');
        });
        </script>";
    }

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1){
        $this->replaceFields($project_id,$record,$event_id,$repeat_instance,$instrument,"survey");
    }

    function replaceFields($project_id,$record,$event_id,$repeat_instance,$instrument,$view = "form") {
        $settings = $this->getModuleSettings($project_id);
        $currentProject = new \Project($project_id);
        $fieldsOnForm = array_keys($currentProject->forms[$instrument]['fields']);
        //TODO JSON-ARRAY formatting?
        $currentData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'fields' => $settings[self::ASSIGN_FIELD], 'project_id' => $project_id, 'events' => array($event_id),
                'records'=> array($record)
            )
        ),true);

        $currentValues = array();

        foreach ($settings[self::ASSIGN_FIELD] as $assignField) {
            $savedSetting = $this->getProjectSetting($assignField."_".$event_id."_".$repeat_instance,$project_id);
            $currentValues[$assignField] = json_decode($savedSetting,true);
        }

        /*$javaScript = "<script>$(document).ready(function() { let dataForm = $('#form'); ";
        foreach ($settings[self::ASSIGN_FIELD] as $assignField) {
            $javaScript .= $this->buildJavascript($project_id, $assignField, $assignField."_".$event_id."_".$repeat_instance, $availableSlots, (isset($currentValues[$assignField]) ? $currentValues[$assignField] : ""), $view);
        }
        $javaScript .= "});</script>";
        echo $javaScript;*/
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1) {
        $settings = $this->getModuleSettings($project_id);

        foreach ($settings[self::ASSIGN_FIELD] as $index => $assignField) {
            if (!isset($settings[self::SAMPLE_ID][$index])) continue;
            $sampleField = $settings[self::SAMPLE_ID][$index];

            $destRecord = $this->saveSample($project_id, $record, $event_id, $repeat_instance,$assignField,$_POST[$assignField],$_POST[$sampleField]);
        }
        //$this->exitAfterHook();
    }

    function saveSample($project_id,$record,$event_id,$repeat_instance,$assignField,$assignValue,$sampleValue)
    {
        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        $projectSetting = $assignField . "_" . $record . "_" . $event_id . "_" . $repeat_instance;
        $destRecord = "";
        if (is_array($assignValue) && !empty($assignValue)) {
            $destRecord = $assignValue[2];
            $destEvent = $assignValue[3];
            $destInstance = $assignValue[4];
            $destField = $settings[self::SAMPLE_FIELD];
            $destForm = $invenProject->metadata[$destField]['form_name'];

            $saveData[0] = array($invenProject->table_pk => $destRecord, $destField => $sampleValue, $destForm . "_complete" => "2");

            if ($invenProject->isRepeatingEvent($destEvent)) {
                $saveData[0]['redcap_repeat_instrument'] = '';
                $saveData[0]['redcap_repeat_instance'] = $destInstance;
            } elseif ($invenProject->isRepeatingForm($destEvent, $destForm)) {
                $saveData[0]['redcap_repeat_instrument'] = $destForm;
                $saveData[0]['redcap_repeat_instance'] = $destInstance;
            }

            $results = \REDCap::saveData($invenProject->project_id, 'json', json_encode($saveData), 'normal', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false);

            if (empty($results['errors'])) {
                $this->setProjectSetting($projectSetting, json_encode(array('project' => $invenProject->project_id, 'field' => $destField, 'record' => $destRecord, 'event' => $destEvent, 'instance' => $destInstance, 'value' => $assignValue, 'label' => "")));
            }
        } else {
            $currentStore = json_decode($this->getProjectSetting($assignField . "_" . $record . "_" . $event_id . "_" . $repeat_instance, $project_id), true);
            $storeProject = new \Project($currentStore['project']);
            $storeForm = $storeProject->metadata[$currentStore['field']]['form_name'];

            $saveData[0] = array($storeProject->table_pk => $currentStore['record'], $currentStore['field'] => '', $storeForm . "_complete" => "0");

            if ($storeProject->isRepeatingEvent($currentStore['event'])) {
                $saveData[0]['redcap_repeat_instrument'] = '';
                $saveData[0]['redcap_repeat_instance'] = $currentStore['instance'];
            } elseif ($storeProject->isRepeatingForm($currentStore['event'], $storeForm)) {
                $saveData[0]['redcap_repeat_instrument'] = $storeForm;
                $saveData[0]['redcap_repeat_instance'] = $currentStore['instance'];
            }

            $results = \REDCap::saveData($storeProject->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false);

            if (empty($results['errors'])) {
                $this->removeProjectSetting($projectSetting, $project_id);
            }
        }
        return $destRecord;
    }

    function buildJavascript($project_id,$record,$event_id,$repeat_instance,$instrument,$view = "form") {
        $javaScript = "";
        if ($view == "survey") {
            $valueTD = "td:nth-child(3)";
            $labelTD = "find('td:nth-child(2)')";
        }
        else {
            $valueTD = "td:nth-child(2)";
            $labelTD = "find('td:first').find('td:first')";
        }

        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);
        $currentProject = new \Project($project_id);
        $fieldsOnForm = array_keys($currentProject->forms[$instrument]['fields']);
        $fieldList = array();
        $currentContainers = array();
        $currentSlots = array();

        $fieldList = array_merge(array_values($settings[self::ASSIGN_CONTAIN]),array_values($settings[self::ASSIGN_FIELD]));

        $currentData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'fields' => $fieldList, 'records' => array($record), 'project_id' => $project_id,
                'events' => array($event_id)
            )
        ),true);

        foreach ($settings[self::ASSIGN_CONTAIN] as $assignedContainer) {
            $currentContainers[$assignedContainer] = $currentData[0][$assignedContainer];
        }
        foreach ($settings[self::ASSIGN_FIELD] as $assignedSlot) {
            $currentSetting = $this->getProjectSetting($assignedSlot . "_" . $record . "_" . $event_id . "_" . $repeat_instance, $project_id);
            $currentSlots[$assignedSlot] = json_decode($currentSetting, true);
        }

        $ajaxUrl = $this->getUrl('interface/ajax.php');
        $javaScript = "<script>
        function getSampleContainers(project_id,record,event,instance) {
            $.ajax({
                    url: '".$ajaxUrl."',
                    data: {
                        project_id: project_id,
                        record: record,
                        event_id: event,
                        repeat_instance: instance,
                        process: 'get_container_options'
                    },
                    type: 'POST'
                }).done(function (html) {
                if (html != '') {
                    let containerList = JSON.parse(html);
                ";
        foreach ($currentContainers as $fieldName => $value) {
            if (!in_array($fieldName,$fieldsOnForm)) continue;
            $javaScript .= "$('#".$fieldName."-tr').find('".$valueTD."').find('input:first').remove();
            buildSampleDropdown(containerList,'".$fieldName."','".$value."',".$project_id.",".$event_id.",".$repeat_instance.",'container');";
        }
        $javaScript .= "}
            });
        }
        function updateSampleLocations(container,project_id,event,instance) {
            let containerRecord = container.value;
            let slotForm = $('#sample_slots');
            $.ajax({
                    url: '".$ajaxUrl."',
                    data: {
                        project_id: project_id,
                        record: containerRecord,
                        event_id: event,
                        repeat_instance: instance,
                        process: 'get_slot_options',
                        currentSlots: '".json_encode($currentSlots)."'
                    },
                    type: 'POST'
                }).done(function (html) { ";
                    foreach ($currentSlots as $fieldName => $slotSetting) {
                        if (!in_array($fieldName,$fieldsOnForm)) continue;
                        $value = $label = "";
                        if (is_array($slotSetting) && !empty($slotSetting)) {
                            $value = $slotSetting['value'];
                            $label = $slotSetting['label'];
                        }

                        $javaScript .= "$('#".$fieldName."-tr').find('".$valueTD."').find('input:first').remove();
                        let slotList = JSON.parse(html);";
                        if ($value != "" && $label != "") {
                            $javaScript .= "slotList['options'] = '<option value=\"$value\">$label</option>'+slotList['options'];";
                        }

                        $javaScript .= "buildSampleDropdown(slotList,'".$fieldName."','".$value."',".$project_id.",".$event_id.",".$repeat_instance.",'samples');
                        slotForm.html('').append(slotList['inputs']);";
                    }
            $javaScript .= "});
        }
        function buildSampleDropdown(containerList,field,value,project_id,event_id,instance,type) {
            let onchangeString = \"doBranching();\";
            console.log(containerList);
            if (type == 'container') {
                onchangeString = onchangeString+'updateSampleLocations(this,'+project_id+','+event_id+','+instance+');';
            }
            $('#'+field+'-tr').find('td.data').html('<select role=\"listbox\" aria-labelledby class=\"x-form-text x-form-field\" name=\"'+field+'\" onchange=\"'+onchangeString+'\"></select>');
            $('select[name=\"'+field+'\"]').append(containerList['options']).val(value).trigger('change');
            $('select[name=\"'+field+'\"]').select2();
        }
        </script>";

        return $javaScript;
    }

    function getContainerList() {
        $containers = array();
        $settings = $this->getModuleSettings();
        $containField = $settings[self::CONTAIN_FIELD];
        $invenProjectID = $settings[self::INVEN_PROJECT];

        $sql = "SELECT DISTINCT(`value`),project_id,record
						FROM redcap_data
						WHERE project_id = ?
                        AND field_name = ?
                        ORDER BY `value` ASC";

        $result = $this->query($sql, [$invenProjectID, $containField]);

        while ($row = $result->fetch_assoc()) {
            $containers[$row['record']] = $row['value'];
        }
        return $containers;
    }

    function getContainerSlots($invenRecords = array(),$openOnly = false)
    {
        $availableSlots = array();
        if (empty($invenRecords)) return $availableSlots;

        $settings = $this->getModuleSettings();
        $fieldList = $this->getFieldList($settings);

        $invenProjectID = $settings[self::INVEN_PROJECT];
        //TODO Speed of this versus external module filter logic: external_modules/docs/query-data.md
        $filterString = "[" . $settings[self::STORE_FIELD] . "] = '1'";
        if ($openOnly) {
            $filterString .= " AND [" . $settings[self::SAMPLE_FIELD] . "] = ''";
        }

        $inventoryData = \Records::getData(
            array(
                'return_format' => 'array', 'fields' => $fieldList, 'records' => $invenRecords, 'project_id' => $invenProjectID,
                'filterLogic' => $filterString
            )
        );

        foreach ($inventoryData as $record => $eventData) {
            $cleanRecord = htmlentities($record);
            foreach ($eventData as $eventID => $recordData) {
                if ($eventID == 'repeat_instances') {
                    foreach ($recordData as $subEventID => $subEventData) {
                        foreach ($subEventData as $subInstrument => $instrumentData) {
                            foreach ($instrumentData as $instance => $instanceData) {
                                foreach ($settings[self::STORE_LABEL] as $index => $labelString) {
                                    $slotLabel = \Piping::replaceVariablesInLabel($labelString, $record, $subEventID, $instance, $inventoryData, false, $invenProjectID, false);
                                    $storedSample = $instanceData[$settings[self::SAMPLE_FIELD]];
                                    //$availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$subEventID."_".$instance] = $slotLabel;
                                    $availableSlots[] = array(
                                        'index'=>$index,'project_id'=>$invenProjectID,'record'=>$cleanRecord,'event'=>$subEventID,'instance'=>$instance,'slot'=>$slotLabel,'sample'=>$storedSample
                                    );
                                }
                            }
                        }
                    }
                }
                else {
                    if (!isset($eventData['repeat_instances'])) {
                        foreach ($settings[self::STORE_LABEL] as $index => $labelString) {
                            $slotLabel = \Piping::replaceVariablesInLabel($labelString, $record, $eventID, 1, $inventoryData, false, $invenProjectID, false);
                            $storedSample = $recordData[$settings[self::SAMPLE_FIELD]];
                            //$availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$eventID."_1"] = $slotLabel;
                            $availableSlots[] = array(
                                'index'=>$index,'project_id'=>$invenProjectID,'record'=>$cleanRecord,'event'=>$eventID,'instance'=>1,'slot'=>$slotLabel,'sample'=>$storedSample
                            );
                        }
                    }
                }
            }
        }

        return $availableSlots;
    }

    function getModuleSettings($project_id = "")
    {
        if (!is_numeric($project_id)) $project_id = $this->getProjectId();

        $moduleSettings = array(
            self::INVEN_PROJECT => $this->getProjectSetting(self::INVEN_PROJECT, $project_id),
            self::CONTAIN_FIELD => $this->getProjectSetting(self::CONTAIN_FIELD, $project_id),
            self::SAMPLE_FIELD => $this->getProjectSetting(self::SAMPLE_FIELD, $project_id),
            self::STORE_FIELD => $this->getProjectSetting(self::STORE_FIELD, $project_id),
            self::ASSIGN_FIELD => $this->getProjectSetting(self::ASSIGN_FIELD, $project_id),
            self::CONTAIN_LABEL => $this->getProjectSetting(self::CONTAIN_FIELD, $project_id),
            self::STORE_LABEL => $this->getProjectSetting(self::STORE_LABEL, $project_id),
            self::SAMPLE_ID => $this->getProjectSetting(self::SAMPLE_ID, $project_id),
            self::ASSIGN_CONTAIN => $this->getProjectSetting(self::ASSIGN_CONTAIN, $project_id),
            self::LOOKUP_FIELD => $this->getProjectSetting(self::LOOKUP_FIELD, $project_id),
            self::SAMPLE_TYPE => $this->getProjectSetting(self::SAMPLE_TYPE, $project_id),
            self::SHIPPED_BY => $this->getProjectSetting(self::SHIPPED_BY, $project_id),
            self::SHIP_DATE => $this->getProjectSetting(self::SHIP_DATE, $project_id),
            self::DISCREP_FIELD => $this->getProjectSetting(self::DISCREP_FIELD, $project_id),
            self::DISCREP_OTHER => $this->getProjectSetting(self::DISCREP_OTHER, $project_id),
            self::COLLECT_EVENT => $this->getProjectSetting(self::COLLECT_EVENT,$project_id),
            self::PARTICIPANT_ID => $this->getProjectSetting(self::PARTICIPANT_ID,$project_id),
            self::PLANNED_COLLECT => $this->getProjectSetting(self::PLANNED_COLLECT,$project_id),
            self::ACTUAL_COLLECT => $this->getProjectSetting(self::ACTUAL_COLLECT,$project_id),
            self::COLLECT_DATE => $this->getProjectSetting(self::COLLECT_DATE,$project_id),
            self::PLANNED_TYPE => $this->getProjectSetting(self::PLANNED_TYPE,$project_id),
            self::ACTUAL_TYPE => $this->getProjectSetting(self::ACTUAL_TYPE,$project_id)
        );

        return $moduleSettings;
    }

    function getFieldList($settings) {
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        $fieldList = array($settings[self::SAMPLE_FIELD], $settings[self::STORE_FIELD], $invenProject->table_pk);

        foreach ($settings[self::STORE_LABEL] as $index => $labelString) {
            preg_match_all('#(?<=\[).+?(?=\])#', $labelString, $matches);
            foreach ($matches[0] as $fieldCheck) {
                $fieldList[] = $fieldCheck;
            }
        }
        return $fieldList;
    }

    function getShippingData($project_id,$fieldFilters = array()) {
        $returnArray = array();

        //TODO Just pass project object into this function instead of the PID?
        if (!is_numeric($project_id) || empty($fieldFilters)) return $returnArray;

        $project = new \Project($project_id);
        $moduleSettings = $this->getModuleSettings($project_id);
        $fieldList = array_merge($moduleSettings[self::DISCREP_FIELD],$moduleSettings[self::DISCREP_OTHER],$moduleSettings[self::SAMPLE_ID],$moduleSettings[self::SAMPLE_TYPE],
            $moduleSettings[self::ASSIGN_CONTAIN],$moduleSettings[self::ASSIGN_FIELD],$moduleSettings[self::LOOKUP_FIELD],
            $moduleSettings[self::SHIPPED_BY],$moduleSettings[self::SHIP_DATE],$moduleSettings[self::COLLECT_EVENT]);

        $filterString = "";
        foreach ($fieldFilters['fields'] as $fieldName) {
            $filterString .= ($filterString != "" ? " OR " : "")."[".$fieldName."] = '".$fieldFilters['value']."'";
        }

        $result = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => $filterString, 'fields' => $fieldList,
                'exportAsLabels' => true
            )
        ),true);

        if (empty($result['errors'])) {
            foreach ($result as $record => $rData) {
                foreach ($moduleSettings[self::LOOKUP_FIELD] as $index => $lField) {
                    if (isset($rData[$lField])) {
                        $returnArray[$record] = array(
                            $project->table_pk => $rData[$project->table_pk],
                            self::LOOKUP_FIELD => $rData[$lField],
                            self::SHIP_DATE => $rData[$moduleSettings[self::SHIP_DATE][$index]],
                            self::SHIPPED_BY => $rData[$moduleSettings[self::SHIPPED_BY][$index]],
                            self::ASSIGN_FIELD => $rData[$moduleSettings[self::ASSIGN_FIELD][$index]],
                            self::ASSIGN_CONTAIN => $rData[$moduleSettings[self::ASSIGN_CONTAIN][$index]],
                            self::DISCREP_OTHER => $rData[$moduleSettings[self::DISCREP_OTHER][$index]],
                            self::DISCREP_FIELD => $rData[$moduleSettings[self::DISCREP_FIELD][$index]],
                            self::SAMPLE_ID => $rData[$moduleSettings[self::SAMPLE_ID][$index]],
                            self::SAMPLE_TYPE => $rData[$moduleSettings[self::SAMPLE_TYPE][$index]],
                            self::COLLECT_EVENT => $rData[$moduleSettings[self::COLLECT_EVENT][$index]]
                        );
                    }
                }
            }
            return $returnArray;
        }
        return $returnArray;
    }

    function getRecordByField($project_id,$field,$value) {
        $returnValue = "";

        if (!is_numeric($project_id) || $value == "" || $field == "") return $returnValue;

        $project = new \Project($project_id);
        $recordData = REDCap::getData(array(
            'return_format' => 'array', 'project_id' => $project_id, 'filterLogic' => "[$field] = '$value'", 'fields' => $project->table_pk,
            'exportAsLabels' => true
        ));
        foreach ($recordData as $recordID => $data) {
            $returnValue = $recordID;
        }

        return $returnValue;
    }

    function getContainerInfoFromSetting($project_id,$slot_setting) {
        $returnInfo = array();

        $slotInfo = explode("_",$slot_setting);
        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);
        $containField = $settings[self::CONTAIN_FIELD];
        $record = $slotInfo[2];
        $event = $slotInfo[3];
        $instance = $slotInfo[4];

        $inventoryData = json_decode(\Records::getData(
            array(
                'return_format' => 'json', 'fields' => array_merge($settings[self::STORE_LABEL],array($containField)),
                'records' => array($record), 'project_id' => $invenProject->project_id,
                'events' => array($event)
            )
        ),true);

        foreach ($inventoryData as $index => $recordData) {
            if ((int)$recordData['redcap_repeat_instance'] === (int)$instance) {
                $container = $recordData[$containField];
                foreach ($settings[self::STORE_LABEL] as $storeField) {
                    if ($recordData[$storeField] != "") {
                        $returnInfo = array('container' => $container,'slot'=>$recordData[$storeField]);
                    }
                }
            }
        }

        return $returnInfo;
    }

    function processFieldEnum($enum) {
        $enumArray = array();
        $splitEnum = explode("\\n",$enum);
        foreach ($splitEnum as $valuePair) {
            $splitPair = explode(",",$valuePair);
            $enumArray[trim($splitPair[0])] = trim($splitPair[1]);
        }
        return $enumArray;
    }
}
