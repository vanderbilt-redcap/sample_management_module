<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project_id = $_GET['pid'];

if ($project_id != "" && is_numeric($project_id)) {
    $module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project_id);

    if (is_array($_FILES['section_to_insert'])) {
        $tmp = $_FILES['section_to_insert']['tmp_name'];

        $settings = $module->getModuleSettings($project_id);
        $invenProject = new \Project($settings[$module::INVEN_PROJECT]);
        $fullDataImport = array();

        if (($handle = fopen($tmp, 'r')) !== false) {
            $rowCount = 0;
            $saveFields = array();
            while (($data = fgetcsv($handle)) !== false) {
                if ($rowCount == 0) {
                    $saveFields = $data;
                    $rowCount++;
                    continue;
                }

                $saveData = array_combine($saveFields, $data);
                $fullDataImport[$rowCount] = $saveData;
                //echo "Inserted record " . $data[0] . ", instance " . $data[2] . "<br/>";
                $rowCount++;
            }
            fclose($handle);
        }
        $destResult = \Records::saveData($invenProject->project_id, 'json', json_encode($fullDataImport));
    }

    echo "<form method='POST' action='".$module->getUrl('interface/index.php')."' enctype='multipart/form-data'>
<input type='file' name='section_to_insert' id='section_to_insert' />
<input type='submit' name='submit_inventory' value='Submit' />
    </form>";

    /*$fp = fopen($module->getModulePath().'interface/inventory.csv','w');

        $allRows[] = array('record_id', 'redcap_repeat_instrument', 'redcap_repeat_instance', 'secondary_name', 'position', 'holds_samples', 'form_1_complete');
        $record = 1;
        $alphabet = range('A','Z');
        $alphabet = array_combine(range(1, count($alphabet)), array_values($alphabet));
        $section = array(
            array(
                'name' => 'LN2_BY',
                'num_towers' => 7,
                'num_sliders' => 0,
                'num_boxes' => 13,
                'box_letnum' => 'let',
                'box_alpha_count' => 9,
                'box_num_count' => 9,
                'numbering' => 'tower'
            ),
            array(
                'name' => 'LN2_DV',
                'num_towers' => 30,
                'num_sliders' => 0,
                'num_boxes' => 13,
                'box_letnum' => 'let',
                'box_alpha_count' => 9,
                'box_num_count' => 9,
                'numbering' => 'tower'
            ),
            array(
                'name' => 'M80_C3PO',
                'num_towers' => 18,
                'num_sliders' => 5,
                'num_boxes' => 5,
                'box_letnum' => 'num',
                'box_alpha_count' => 10,
                'box_num_count' => 10,
                'numbering' => 'all'
            ),
            array(
                'name' => 'M80_R2D2',
                'num_towers' => 18,
                'num_sliders' => 5,
                'num_boxes' => 5,
                'box_letnum' => 'num',
                'box_alpha_count' => 10,
                'box_num_count' => 10,
                'numbering' => 'all'
            )
        );
        foreach ($section as $index => $sct) {
            $nameString = $sct['name'];
            for ($i = 1; $i <= $sct['num_towers']; $i++) {
                $towerString = "Tower $i";
                $instanceCount = 1;
                if ($sct['num_sliders'] > 0) {
                    for ($j = 1; $j <= $sct['num_sliders']; $j++) {
                        $sliderString = "Slider ".$alphabet[$j];
                        for ($k = 1; $k <= $sct['num_boxes']; $k++) {
                            $boxString = "Box ".($sct['box_letnum'] == 'let' ? $alphabet[$k] : $k);
                            for ($l = 1; $l <= $sct['box_alpha_count']; $l++) {
                                $depthString = $alphabet[$l];
                                for ($m = 1; $m <= $sct['box_num_count']; $m++) {
                                    $widthString = $m;
                                    $allRows[] = array(
                                        $record, 'form_1', $instanceCount, "$nameString $towerString $sliderString $boxString", $depthString.$widthString, 1, 0
                                    );
                                    $instanceCount++;
                                }
                            }
                            $record++;
                            $instanceCount = 1;
                        }
                    }
                }
                else {
                    for ($k = 1; $k <= $sct['num_boxes']; $k++) {
                        $boxString = "Box ".($sct['box_letnum'] == 'let' ? $alphabet[$k] : $k);
                        for ($l = 1; $l <= $sct['box_alpha_count']; $l++) {
                            $depthString = $alphabet[$l];
                            for ($m = 1; $m <= $sct['box_num_count']; $m++) {
                                $widthString = $m;
                                $allRows[] = array(
                                    $record, 'form_1', $instanceCount, "$nameString $towerString $boxString", $depthString.$widthString, 1, 0
                                );
                                $instanceCount++;
                            }
                        }
                        $record++;
                        $instanceCount = 1;
                    }
                }
            }
        }
        foreach ($allRows as $currentRow) {
            fputcsv($fp,$currentRow);
        }
        fclose($fp);*/
}