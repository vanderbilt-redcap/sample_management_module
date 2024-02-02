<?php

namespace Vanderbilt\SampleManagementModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use Vanderbilt\REDCap\Classes\MyCap\Api\DB\Project;

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

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1)
    {
        $settings = $this->getModuleSettings($project_id);

        $assignField = $settings[self::ASSIGN_FIELD];
        $sampleField = $settings[self::SAMPLE_ID];

        $destRecord = $this->saveSample($project_id, $record, $event_id, $repeat_instance, $assignField, explode("_", \ExternalModules\ExternalModules::escape($_POST[$assignField])), \ExternalModules\ExternalModules::escape($_POST[$sampleField]));

        //$this->exitAfterHook();
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

    function saveSample($project_id,$record,$event_id,$repeat_instance,$assignField,$assignValue,$sampleValue,$slotLabel = "")
    {
        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        $projectSetting = $assignField . "_" . $record . "_" . $event_id . "_" . ($repeat_instance == "" ? "1" : $repeat_instance);
        $destRecord = "";

        $currentStore = json_decode($this->getProjectSetting($projectSetting), true);
        if (!empty($currentStore)) {
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

        if (is_array($assignValue) && !empty($assignValue)) {
            $destRecord = $assignValue[1];
            $destEvent = $assignValue[2];
            $destInstance = $assignValue[3];
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
                if ($slotLabel == "") {
                    $containerInfo = $this->getContainerInfoFromSetting($project_id, implode("_",$assignValue));
                    $slotLabel = $containerInfo['slot'];
                }
                $this->setProjectSetting($projectSetting, json_encode(array('project' => $invenProject->project_id, 'field' => $destField, 'record' => $destRecord, 'event' => $destEvent, 'instance' => $destInstance, 'value' => $assignValue, 'label' => $slotLabel)));
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

        $fieldList = array($settings[self::ASSIGN_CONTAIN],$settings[self::ASSIGN_FIELD]);

        $currentData = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'fields' => $fieldList, 'records' => array($record), 'project_id' => $project_id,
                'events' => array($event_id)
            )
        ),true);

        if (isset($settings[self::ASSIGN_CONTAIN])) {
            $currentContainers[$settings[self::ASSIGN_CONTAIN]] = $currentData[0][$settings[self::ASSIGN_CONTAIN]];
        }

        if (isset($settings[self::ASSIGN_FIELD])) {
            $currentSetting = $this->getProjectSetting($settings[self::ASSIGN_FIELD] . "_" . $record . "_" . $event_id . "_" . $repeat_instance, $project_id);
            $currentSlots[$settings[self::ASSIGN_FIELD]] = json_decode($currentSetting, true);
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
                    //console.log(html);
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
                        $label = "";
                        $value = array();
                        if (is_array($slotSetting) && !empty($slotSetting)) {
                            $value = $slotSetting['value'];
                            $label = $slotSetting['label'];
                        }

                        $javaScript .= "$('#".$fieldName."-tr').find('".$valueTD."').find('input:first').remove();
                        //console.log('Test '+html);
                        let slotList = JSON.parse(html);";
                        if (!empty($value) && $label != "") {
                            $javaScript .= "slotList['options'] = '<option value=\"".implode("_",$value)."\">$label</option>'+slotList['options'];";
                        }

                        $javaScript .= "buildSampleDropdown(slotList,'".$fieldName."','".implode("_",$value)."',".$project_id.",".$event_id.",".$repeat_instance.",'samples');
                        slotForm.html('').append(slotList['inputs']);";
                    }
            $javaScript .= "});
        }
        function buildSampleDropdown(containerList,field,value,project_id,event_id,instance,type) {
            let onchangeString = \"doBranching();\";
            //console.log(containerList);
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
        $invenProjectID = $settings[self::INVEN_PROJECT];
        $invenProject = new \Project($invenProjectID);

        $fieldList = array($settings[self::CONTAIN_FIELD],$settings[self::SAMPLE_FIELD],$invenProject->table_pk);

        $inventoryData = \Records::getData(
            array(
                'return_format' => 'json-array', 'fields' => $fieldList, 'project_id' => $invenProjectID
            )
        );

        if (empty($inventoryData['errors'])) {
            foreach ($inventoryData as $iData) {
                $recordID = $iData[$invenProject->table_pk];

                if (!isset($containers[$recordID])) {
                    $containers[$recordID] = array('name' => $iData[$settings[self::CONTAIN_FIELD]],'sampleCount' => 0);
                }
                if ($iData[$settings[self::SAMPLE_FIELD]] != "" && is_numeric($containers[$recordID]['sampleCount'])) {
                    $containers[$recordID]['sampleCount']++;
                }
            }
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
                                $slotLabel = \Piping::replaceVariablesInLabel($settings[self::STORE_LABEL], $record, $subEventID, $instance, $inventoryData, false, $invenProjectID, false);
                                $storedSample = $instanceData[$settings[self::SAMPLE_FIELD]];
                                //$availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$subEventID."_".$instance] = $slotLabel;
                                $slotArray = array(
                                    'project_id' => $invenProjectID, 'record' => $cleanRecord, 'event' => $subEventID, 'instance' => $instance, 'slot' => $slotLabel, 'sample_id' => $storedSample
                                );
                                if ($storedSample != "") {
                                    $sampleData = $this->getSampleInfo($this::getProjectId(),array(),"[" . $settings[self::SAMPLE_ID] . "] = '" . $storedSample . "'");
                                    foreach ($sampleData as $sData) {
                                        $slotArray['collect_date'] = $sData[self::COLLECT_DATE];
                                        $slotArray['planned_type'] = $sData[self::SAMPLE_TYPE];
                                        $slotArray['participant_id'] = $sData[self::PARTICIPANT_ID];
                                    }
                                }
                                $availableSlots[] = $slotArray;
                            }
                        }
                    }
                }
                else {
                    if (!isset($eventData['repeat_instances'])) {
                        $slotLabel = \Piping::replaceVariablesInLabel($settings[self::STORE_LABEL], $record, $eventID, 1, $inventoryData, false, $invenProjectID, false);
                        $storedSample = $recordData[$settings[self::SAMPLE_FIELD]];
                        $slotArray = array(
                            'project_id' => $invenProjectID, 'record' => $cleanRecord, 'event' => $eventID, 'instance' => 1, 'slot' => $slotLabel, 'sample_id' => $storedSample
                        );
                        //$availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$eventID."_1"] = $slotLabel;
                        if ($storedSample != "") {
                            $sampleData = $this->getSampleInfo($this::getProjectId(),array(),"[" . $settings[self::SAMPLE_ID] . "] = '" . $storedSample . "'");
                            foreach ($sampleData as $sData) {
                                $slotArray['collect_date'] = $sData[self::COLLECT_DATE];
                                $slotArray['planned_type'] = $sData[self::SAMPLE_TYPE];
                                $slotArray['participant_id'] = $sData[self::PARTICIPANT_ID];
                            }
                        }
                        $availableSlots[] = $slotArray;
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

    function getFieldList($settings)
    {
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        $fieldList = array($settings[self::SAMPLE_FIELD], $settings[self::STORE_FIELD], $invenProject->table_pk);

        preg_match_all('#(?<=\[).+?(?=\])#', $settings[self::STORE_LABEL], $matches);
        foreach ($matches[0] as $fieldCheck) {
            $fieldList[] = $fieldCheck;
        }

        return $fieldList;
    }
    
    function getSampleInfo($project_id,$records = array(),$filterString = "") {
        $returnArray = array();
        $project = new \Project($project_id);
        $moduleSettings = $this->getModuleSettings($project_id);
        $fieldList = array($moduleSettings[self::DISCREP_FIELD],$moduleSettings[self::DISCREP_OTHER],$moduleSettings[self::SAMPLE_ID],$moduleSettings[self::SAMPLE_TYPE],
            $moduleSettings[self::ASSIGN_CONTAIN],$moduleSettings[self::ASSIGN_FIELD],$moduleSettings[self::LOOKUP_FIELD],
            $moduleSettings[self::SHIPPED_BY],$moduleSettings[self::SHIP_DATE],$moduleSettings[self::COLLECT_EVENT],
            $moduleSettings[self::COLLECT_DATE],$moduleSettings[self::PLANNED_TYPE],$moduleSettings[self::PLANNED_COLLECT],
            $moduleSettings[self::PARTICIPANT_ID]);

        $result = json_decode(\REDCap::getData(
            array(
                'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => $filterString, 'fields' => $fieldList,
                'exportAsLabels' => true, 'records' => $records
            )
        ),true);

        if (empty($result['errors'])) {
            foreach ($result as $record => $rData) {
                $returnArray[$record] = array(
                    $project->table_pk => $rData[$project->table_pk],
                    self::LOOKUP_FIELD => $rData[$moduleSettings[self::LOOKUP_FIELD]],
                    self::SHIP_DATE => $rData[$moduleSettings[self::SHIP_DATE]],
                    self::SHIPPED_BY => $rData[$moduleSettings[self::SHIPPED_BY]],
                    self::ASSIGN_FIELD => $rData[$moduleSettings[self::ASSIGN_FIELD]],
                    self::ASSIGN_CONTAIN => $rData[$moduleSettings[self::ASSIGN_CONTAIN]],
                    self::DISCREP_OTHER => $rData[$moduleSettings[self::DISCREP_OTHER]],
                    self::DISCREP_FIELD => $rData[$moduleSettings[self::DISCREP_FIELD]],
                    self::SAMPLE_ID => $rData[$moduleSettings[self::SAMPLE_ID]],
                    self::SAMPLE_TYPE => $rData[$moduleSettings[self::SAMPLE_TYPE]],
                    self::COLLECT_EVENT => $rData[$moduleSettings[self::COLLECT_EVENT]],
                    self::PLANNED_TYPE => $rData[$moduleSettings[self::PLANNED_TYPE]],
                    self::PLANNED_COLLECT => $rData[$moduleSettings[self::PLANNED_COLLECT]],
                    self::COLLECT_DATE => $rData[$moduleSettings[self::COLLECT_DATE]],
                    self::PARTICIPANT_ID => $rData[$moduleSettings[self::PARTICIPANT_ID]]
                );
            }
        }

        return $returnArray;
    }

    function getShippingData($project_id,$fieldFilters = array()) {
        $returnArray = array();

        //TODO Just pass project object into this function instead of the PID?
        if (!is_numeric($project_id) || empty($fieldFilters)) return $returnArray;

        return $returnArray;
    }

    function getRecordByField($project_id,$field,$value) {
        $returnValue = "";

        if (!is_numeric($project_id) || $value == "" || $field == "") return $returnValue;

        $project = new \Project($project_id);
        $recordData = json_decode(REDCap::getData(array(
            'return_format' => 'json', 'project_id' => $project_id, 'filterLogic' => "[$field] = '$value'", 'fields' => $project->table_pk,
            'exportAsLabels' => true
        )),true);

        foreach ($recordData as $index => $data) {
            $returnValue = $data[$project->table_pk];
        }

        return $returnValue;
    }

    function getContainerInfoFromSetting($project_id,$slot_setting) {
        $returnInfo = array('container'=>"",'slot'=>"");

        $slotInfo = explode("_",$slot_setting);
        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);
        $containField = $settings[self::CONTAIN_FIELD];
        $invenForm = $invenProject->metadata[$containField]['form_name'];
        $record = $slotInfo[1];
        $event = $slotInfo[2];
        $instance = $slotInfo[3];

        $inventoryData = \REDCap::getData(
            array(
                'return_format' => 'array',
                'records' => array($record), 'project_id' => $invenProject->project_id,
                'events' => array($event)
            )
        );

        foreach ($inventoryData as $recordID => $eventData) {
            foreach ($eventData['repeat_instances'][$event][$invenForm] as $checkInstance => $recordData) {
                if ((int)$checkInstance === (int)$instance) {
                    $container = $recordData[$containField];
                    if (isset($settings[self::STORE_LABEL])) {
                        $storeLabel = \Piping::replaceVariablesInLabel($settings[self::STORE_LABEL], $record, $event, $instance, $inventoryData, false, $invenProject->project_id, false);
                        $returnInfo = array('container' => $container, 'slot' => $storeLabel);
                    }
                }
            }
        }

        return $returnInfo;
    }

    function checkoutSample($barcode,$slotSetting) {
        $settings = $this->getModuleSettings($this->getProjectId());
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);
        $sampleRecord = $this->getRecordByField($this->getProjectId(),$settings[self::SAMPLE_ID],$barcode);

        if ($sampleRecord != "" && is_array($slotSetting)) {

        }
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

    # Function to determine the 'redcap_data' DB table for a REDCap project, in the case that this is running on a version of REDCap that uses more than the single DB table.
    function getDataTable($project_id){
        return method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($project_id) : "redcap_data";
    }
}
