<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project = new \Project((int)$_GET['pid']);
$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project->project_id);

$shippingData = array();
$trackingNum = "";
$ajaxUrl = $module->getUrl('interface/ajax.php');

?>

<h2>Receiving Samples</h2>
<span>
    <label for="tracking_num">Tracking Number: </label><input type="text" name="tracking_num" id="tracking_num" value="<?php echo ($trackingNum ?? ""); ?>">
    <input type="button" value="Load Samples" name="load_samples" onclick="loadShippingSamples('tracking_num','sample_table');loadAllContainers('container_select');">
    <div id="package_info" style="display:none;">
        <h5>Shipping Info</h5>
        <span style="display:block;" id="shipped_date">Shipped Date: 01-01-2023</span>
        <span style="display:block;" id="shipped_by">Shipped By: Tester Person</span>
    </div>
</span>

<div id="sample_list" class="sample_section" style="display:none;">
    <h3>Sample List</h3>
    <span id="sample_table"></span>
</div>

<span style="display:table-row;">
<span id="container_container" class="sample_section" style="display:none;">
    <h3>Container Information</h3>
    <div style="margin:10px;"><span>Select Container</span><span><select id="container_select" onchange="loadContainer(this.value,'container_table','');"><option></option></select></span></div>
    <div id="container_table">
    </div>
</span>
<div id="sample_info_container" class="sample_section" style="display:none;padding:15px;">
    <h3>Sample Information</h3>
    <span id="sample_info"></span>
</div>
</span>
<style>
    h2,h3 {
        margin-bottom: 2px;
    }
    table.sample_table {
        width:400px;
        margin:25px;
    }
    td,th {
        border: 1px solid black;
        padding:5px;
    }
    .bric_container td {
        width:100px;
        height:100px;
        vertical-align: top;
    }
    .sample_section {
        margin-top: 15px;
    }
    .slot_label {
        display:block;
        font-weight:bold;
    }
    .scan_barcode input {
        width:75px;
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
       $('#receive_date').datepicker();

    });
    function loadShippingSamples(tracking_id,sample_element) {
        $('#package_info').css('display','block');
        $('#sample_list').css('display','block');
        $('#container_container').css('display','table-cell');
        let tracking_num = $('#'+tracking_id).val();

        $.ajax({
            url: '<?php echo $ajaxUrl; ?>',
            data: {
                project_id:<?php echo $project->project_id; ?>,
                track_num: tracking_num,
                process: 'sample_list'
            },
            type: 'POST'
        }).done(function (html) {
            let sampleList = JSON.parse(html);
            let sampleHTML = "<table><tr><th>Sample Barcode</th><th>Sample Status</th><th>Location</th></tr>";
            let sampleData = "";
            for (const key in sampleList) {
                const value = sampleList[key]['sample_id'];
                sampleHTML += "<tr id='sample_row_"+key+"'><td>"+value+"</td><td></td><td></td></tr>";
            }
            sampleHTML += "</table>";
            $('#'+sample_element).html(sampleHTML);
        });
    }

    function loadAllContainers(table_id) {
        $.ajax({
            url: '<?php echo $ajaxUrl; ?>',
            data: {
                project_id:<?php echo $project->project_id; ?>,
                process: 'container_list'
            },
            type: 'POST'
        }).done(function (html) {
            let containerSelect = $('#'+table_id);
            let containerList = JSON.parse(html);

            for (const key in containerList) {
                const value = containerList[key];
                var o = new Option(value,key);
                containerSelect.append(o);
            }
            containerSelect.select2();
        });
    }

    function loadContainer(record,table_id,container = '') {
        $.ajax({
            url: '<?php echo $ajaxUrl; ?>',
            data: {
                project_id: <?php echo $project->project_id; ?>,
                record: record,
                process: 'slot_info'
            },
            type: 'POST'
        }).done(function (html) {
            let slotList = JSON.parse(html);
            let previousRow = "";
            let currentRow = "";
            let tableHTML = "<table><tr>";
            for (const key in slotList) {
                const value = slotList[key];
                const slotID = value['index']+"_"+value['project_id']+"_"+value['record']+"_"+value['event']+"_"+value['instance'];
                const slotLabel = value['slot'];
                let currentRow = slotLabel.substring(0,1);
                if (previousRow != "" && currentRow != previousRow) {
                    tableHTML += "</tr><tr>";
                }
                tableHTML += "<td><span class='slot_label'>"+slotLabel+"</span>";
                if (value['sample'] != "") {
                    tableHTML += "<div id='sample_slot_"+slotID+"'>ID: "+slotLabel+"</div>";
                }
                else {
                    tableHTML += "<div id='sample_slot_"+slotID+"'><span class='scan_barcode'><label for='barcode_slot_"+slotID+"'>Scan Barcode:</label><input class='barcode_text' type='text' id='barcode_slot_"+slotID+"' oninput='saveSample(this.value,\""+slotID+"\",\"sample_issue_\");loadSample(this.value,\""+slotID+"\");' /></span></div>";
                }
                tableHTML += "</td>";
                previousRow = currentRow;
            }
            tableHTML += "</tr></table>";
            $('#'+table_id).append(tableHTML);
        });

        /*let containerLayout = ["A1","A2","A3","A4","A5","A6","A7","A8","A9","A10","B1","B2","B3","B4","B5","B6","B7","B8","B9","B10"];

        let currentSample = {'A1': {'id':'2345','type':'Blood'}};
        let tableHTML = "<table class='bric_container'><tr>";
        let i = 0;
        let previousRow = "";
        let currentRow = "";

        while (i < containerLayout.length) {
            let currentSlot = containerLayout[i];
            if (currentSlot == "") continue;
            let currentRow = currentSlot.substring(0,1);

            if (previousRow != "" && currentRow != previousRow) {
                tableHTML += "</tr><tr>";
            }
            tableHTML += "<td><span class='slot_label'>"+currentSlot+"</span>";
            if (currentSlot in currentSample) {
                tableHTML += "<div id='sample_slot_"+currentSlot+"'>ID: "+currentSample[currentSlot]['id']+"<br/>Type:"+currentSample[currentSlot]['type']+"</div>";
            }
            else {
                tableHTML += "<div id='sample_slot_"+currentSlot+"'><span class='scan_barcode'><label for='barcode_slot_"+currentSlot+"'>Scan Barcode:</label><input type='text' id='barcode_slot_"+currentSlot+"' onblur='loadSample(this,\"sample_slot_"+currentSlot+"\")' /></span></div>";
            }
            tableHTML += "</td>";
            previousRow = currentRow;
            i++;
        }
        tableHTML += "</tr></table>";

        $('#'+table_id).append(tableHTML);*/
    }

    function loadSample(barcode,slot_id) {
        let sampleHTML = generateSampleInfo(barcode,slot_id);
    }

    function generateSampleInfo(barcode,slot_id) {
        let parent_id = "sample_slot_"+slot_id;
        let sampleTable = "";
        $.ajax({
            url: '<?php echo $ajaxUrl; ?>',
            data: {
                project_id: <?php echo $project->project_id; ?>,
                record: barcode,
                process: 'load_sample'
            },
            type: 'POST'
        }).done(function (html) {
            if (html != "") {
                let resultData = JSON.parse(html);
                for (const key in resultData) {
                    if (key == "") continue;
                    sampleTable = "<table><tr><td>Sample ID</td><td>"+barcode+"</td></tr>";
                    let sampleData = resultData[key];
                    sampleTable += "<tr><td>Participant ID</td><td>" + sampleData['participant_id'] + "</td></tr>" +
                        "<tr><td>Collection Date</td><td>" + sampleData['collect_date'] + "</td></tr>" +
                        "<tr><td><h5>Expected Type</h5><br/>" + sampleData['planned_type'] + "</td><td><h5>Actual Type</h5><br/>" + sampleData['actual_type'] + "</td></tr>" +
                        "<tr><td><h5>Expected Collect Event</h5><br/>" + sampleData['planned_collect'] + "</td><td><h5>Actual Collect Event</h5><br/>" + sampleData['actual_collect'] + "</td></tr>";
                    sampleTable += "<tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\"" + barcode + "\",\"" + parent_id + "\",\"sample_issue_\");$(\"#sample_info_container\").css(\"display\",\"none\");' value='Save Sample' /></td></tr>";
                    $('#' + parent_id).html("Part. ID: " + sampleData['participant_id'] + "<br/>Samp. ID: " + sampleData['sample_id'] + "<br/>Sample Type: " + sampleData['planned_type'] + "<br/>Collect Date: " + sampleData['collect_date']);
                    sampleTable += "</table>";
                }
                $('#sample_info_container').css('display','table-cell');
                $('#sample_info').html(sampleTable);
            }
        });
        //return "<table><tr><td>Sample ID</td><td>"+barcode+"</td></tr><tr><td>Sample Type</td><td>Blood</td></tr><tr><td>Issues</td><td><span><input id='sample_issue_1' type='checkbox' value='1' /><label for='sample_issue_1'>Empty</label></span><br/><span><input id='sample_issue_2' type='checkbox' value='2' /><label for='sample_issue_2'>Wrong Sample Type</label></span><br/><span><input id='sample_issue_3' type='checkbox' value='3' /><label for='sample_issue_3'>Sample Missing</label></span><br/><span><input id='sample_issue_4' type='checkbox' value='4' /><label for='sample_issue_4'>Damaged Sample</label></span><br/><span><input id='sample_issue_5' type='checkbox' value='5' /><label for='sample_issue_5'>Damaged Tube</label></span></td></tr><tr><td colspan='2'><label for='sample_issue_other'>Other Notes</label><textarea id='sample_issue_other' name='sample_issue_other'></textarea></td></tr><tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\""+barcode+"\",\""+parent_id+"\",\"sample_issue_\");$(\"#sample_info_container\").css(\"display\",\"none\");' value='Save Sample' /></td></tr></table>";
    }

    function saveSample(barcode,slot_id,issue_id_prefix) {
        let discrepData = [];
        let sample_cell_id = "sample_slot_"+slot_id;
        $("input[id^='"+issue_id_prefix+"']").each(function() {
           if ($(this).prop("checked")) {
               discrepData.push($(this).val());
           }
        });
        let discrep_other = $("#"+issue_id_prefix+"other").val();

        $.ajax({
            url: '<?php echo $ajaxUrl; ?>',
            data: {
                project_id: <?php echo $project->project_id; ?>,
                record: barcode,
                discreps: discrepData,
                discrep_other: discrep_other,
                process: 'save_sample'
            },
            type: 'POST'
        }).done(function (html) {

        });
    }
</script>