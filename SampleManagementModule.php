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

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance = 1){
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

        $availableSlots = $this->getOpenSlots($settings,$currentValues);

        /*$javaScript = "<script>$(document).ready(function() { let dataForm = $('#form'); ";
        foreach ($settings[self::ASSIGN_FIELD] as $assignField) {
            $javaScript .= $this->buildJavascript($project_id, $assignField, $assignField."_".$event_id."_".$repeat_instance, $availableSlots, (isset($currentValues[$assignField]) ? $currentValues[$assignField] : ""), $view);
        }
        $javaScript .= "});</script>";
        echo $javaScript;*/
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1) {
        $settings = $this->getModuleSettings($project_id);
        $invenProject = new \Project($settings[self::INVEN_PROJECT]);

        foreach ($settings[self::ASSIGN_FIELD] as $index => $assignField) {
            if (!isset($settings[self::SAMPLE_ID][$index])) continue;
            $sampleField = $settings[self::SAMPLE_ID][$index];

            if (isset($_POST[$assignField]) && isset($_POST[$sampleField]) && $_POST[$sampleField] != "") {
                if ($_POST[$assignField] != "") {
                    $assignValue = explode("_", $_POST[$assignField]);

                    $destRecord = $assignValue[2];
                    $destEvent = $assignValue[3];
                    $destInstance = $assignValue[4];
                    $destField = $settings[self::SAMPLE_FIELD];
                    $destForm = $invenProject->metadata[$destField]['form_name'];
                    $sampleValue = $_POST[$sampleField];

                    $saveData[0] = array($invenProject->table_pk => $destRecord, $destField => $sampleValue);

                    if ($invenProject->isRepeatingEvent($destEvent)) {
                        $saveData[0]['redcap_repeat_instrument'] = '';
                        $saveData[0]['redcap_repeat_instance'] = $destInstance;
                    } elseif ($invenProject->isRepeatingForm($destEvent, $destForm)) {
                        $saveData[0]['redcap_repeat_instrument'] = $destForm;
                        $saveData[0]['redcap_repeat_instance'] = $destInstance;
                    }

                    $results = \REDCap::saveData($invenProject->project_id, 'json', json_encode($saveData), 'normal', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false);

                    if (empty($results['errors'])) {
                        $this->setProjectSetting($assignField . "_" . $record . "_" . $event_id . "_" . $repeat_instance, json_encode(array('project' => $invenProject->project_id, 'field' => $destField, 'record' => $destRecord, 'event' => $destEvent, 'instance' => $destInstance, 'value' => $_POST[$assignField], 'label' => $_POST[$_POST[$assignField]])));
                    }
                }
                else {
                    $currentStore = json_decode($this->getProjectSetting($assignField."_".$record."_".$event_id."_".$repeat_instance,$project_id),true);
                    $storeProject = new \Project($currentStore['project']);
                    $storeForm = $storeProject->metadata[$currentStore['field']]['form_name'];

                    $saveData[0] = array($storeProject->table_pk => $currentStore['record'], $currentStore['field'] => '');

                    if ($storeProject->isRepeatingEvent($currentStore['event'])) {
                        $saveData[0]['redcap_repeat_instrument'] = '';
                        $saveData[0]['redcap_repeat_instance'] = $currentStore['instance'];
                    } elseif ($storeProject->isRepeatingForm($currentStore['event'], $storeForm)) {
                        $saveData[0]['redcap_repeat_instrument'] = $storeForm;
                        $saveData[0]['redcap_repeat_instance'] = $currentStore['instance'];
                    }

                    $results = \REDCap::saveData($storeProject->project_id, 'json', json_encode($saveData), 'overwrite', 'YMD', 'flat', null, true, true, true, false, true, array(), false, false);

                    if (empty($results['errors'])) {
                        $this->removeProjectSetting($assignField . "_" . $record . "_" . $event_id . "_" . $repeat_instance, $project_id);
                    }
                }
            }
        }
        //$this->exitAfterHook();
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
                        process: 'contain'
                    },
                    type: 'POST'
                }).done(function (html) {
                if (html != '') {
                ";
        foreach ($currentContainers as $fieldName => $value) {
            if (!in_array($fieldName,$fieldsOnForm)) continue;
            $javaScript .= "$('#".$fieldName."-tr').find('".$valueTD."').find('input:first').remove();
            let containerList = JSON.parse(html);
            $('#".$fieldName."-tr').find('td.data').html('<select role=\"listbox\" aria-labelledby class=\"x-form-text x-form-field\" name=\"".$fieldName."\" onchange=\"doBranching();updateSampleLocations(this,".$project_id.",".$event_id.",".$repeat_instance.");\"></select>');
            $('select[name=\"".$fieldName."\"]').append(containerList['options']).val('".$value."').trigger('change');";
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
                        process: 'assign',
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
                        let slotList = JSON.parse(html);
                        $('#".$fieldName."-tr').find('td.data').html('<select role=\"listbox\" aria-labelledby class=\"x-form-text x-form-field\" name=\"".$fieldName."\" onchange=\"doBranching();\"><option value=\"\"></option>".($label != "" ? "<option value=\"$value\">$label</option>" :"")."</select>');
                        $('select[name=\"".$fieldName."\"]').append(slotList['options']).val('".$value."').trigger('change');
                        slotForm.html('').append(slotList['inputs']);";
                    }
            $javaScript .= "});
        }
        </script>";

        return $javaScript;
    }

    function getOpenSlots($settings,$record)
    {
        $fieldList = $this->getFieldList($settings);

        $invenProjectID = $settings[self::INVEN_PROJECT];
        //TODO Speed of this versus external module filter logic: external_modules/docs/query-data.md
        $inventoryData = \REDCap::getData(
            array(
                'return_format' => 'array', 'fields' => $fieldList, 'records' => array($record), 'project_id' => $invenProjectID,
                'filterLogic' => "[" . $settings[self::SAMPLE_FIELD] . "] = '' AND [" . $settings[self::STORE_FIELD] . "] = '1'"
            )
        );
        $availableSlots = array();

        foreach ($inventoryData as $record => $eventData) {
            $cleanRecord = htmlentities($record);
            foreach ($eventData as $eventID => $recordData) {
                if ($eventID == 'repeat_instances') {
                    foreach ($recordData as $subEventID => $subEventData) {
                        foreach ($subEventData as $subInstrument => $instrumentData) {
                            foreach ($instrumentData as $instance => $instanceData) {
                                foreach ($settings[self::STORE_LABEL] as $index => $labelString) {
                                    $slotLabel = \Piping::replaceVariablesInLabel($labelString, $record, $subEventID, $instance, $inventoryData, false, $invenProjectID, false);
                                    $availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$subEventID."_".$instance] = $slotLabel;
                                }
                            }
                        }
                    }
                }
                else {
                    if (!isset($eventData['repeat_instances'])) {
                        foreach ($settings[self::STORE_LABEL] as $index => $labelString) {
                            $slotLabel = \Piping::replaceVariablesInLabel($labelString, $record, $eventID, 1, $inventoryData, false, $invenProjectID, false);
                            $availableSlots[$index."_".$invenProjectID."_".$cleanRecord."_".$eventID."_1"] = $slotLabel;
                        }
                    }
                }
            }
        }
        return $availableSlots;
    }

    function getModuleSettings($project_id) {
        $moduleSettings = array(
            self::INVEN_PROJECT => $this->getProjectSetting(self::INVEN_PROJECT,$project_id),
            self::CONTAIN_FIELD => $this->getProjectSetting(self::CONTAIN_FIELD,$project_id),
            self::SAMPLE_FIELD => $this->getProjectSetting(self::SAMPLE_FIELD,$project_id),
            self::STORE_FIELD => $this->getProjectSetting(self::STORE_FIELD,$project_id),
            self::ASSIGN_FIELD => $this->getProjectSetting(self::ASSIGN_FIELD,$project_id),
            self::CONTAIN_LABEL => $this->getProjectSetting(self::CONTAIN_FIELD,$project_id),
            self::STORE_LABEL => $this->getProjectSetting(self::STORE_LABEL,$project_id),
            self::SAMPLE_ID => $this->getProjectSetting(self::SAMPLE_ID,$project_id),
            self::ASSIGN_CONTAIN => $this->getProjectSetting(self::ASSIGN_CONTAIN,$project_id)
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
}