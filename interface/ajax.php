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
    $invenProject = new \Project($settings[$module::INVEN_PROJECT]);

    if ($process == "contain") {
        $containers = $currentContainers = array();

        $containField = $settings[$module::CONTAIN_FIELD];

        $sql = "SELECT DISTINCT(`value`),project_id,record
						FROM redcap_data
						WHERE project_id = ?
                        AND field_name = ?
                        ORDER BY `value` ASC";

        $result = $module->query($sql, [$invenProject->project_id, $containField]);
        $containerNames = array();
        $containers['options'] = "<option value=''></option>";
        while ($row = $result->fetch_assoc()) {
            $containers['options'] .= "<option value='".$row['record']."'>".$row['value']."</option>";
        }

        $tableHTML = json_encode($containers);
    }
    elseif ($process == "assign") {
        $availableSlots = array();
        $currentSlots = json_decode($_POST['currentSlots'],true);

        $availableSlots = $module->getOpenSlots($settings,$record);
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
}

echo $tableHTML;