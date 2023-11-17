<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project = new \Project((int)$_GET['pid']);
$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project->project_id);

$shippingData = array();
$trackingNum = "";
$ajaxUrl = $module->getUrl('interface/ajax.php');
$containerLayout = array("A1","A2","A3","A4","A5","A6","A7","A8","A9","A10",
                        "B1","B2","B3","B4","B5","B6","B7","B8","B9","B10");
echo "<script type='text/javascript'>";
echo "</script>";
?>

<h2>Receiving Samples</h2>
<span>
    <label for="tracking_num">Tracking Number: </label><input type="text" name="tracking_num" id="tracking_num" value="<?php echo ($trackingNum ?? ""); ?>">
    <input type="button" value="Load Samples" name="load_samples" onclick="loadShippingSamples($('#tracking_num').value);">
    <div id="package_info" style="display:none;">
        <h5>Shipping Info</h5>
        <span style="display:block;" id="shipped_date">Shipped Date: 01-01-2023</span>
        <span style="display:block;" id="shipped_by">Shipped By: Tester Person</span>
    </div>
</span>

<span id="sample_list" class="sample_section" style="display:none;">
    <h3>Sample List</h3>
    <table>
        <tr><th>Sample ID</th><th>Status</th><th>Location</th></tr>
        <tr><td>1234</td><td>not checked in</td><td></td></tr>
        <tr style="background-color: forestgreen;"><td>2345</td><td>checked in</td><td>C3PO Box A A1</td></tr>
        <tr style="background-color: palevioletred;"><td>3456</td><td>sample broken</td><td></td></tr>
    </table>
</span>

<span style="display:table-row;">
<span id="container_container" class="sample_section" style="display:none;">
    <h3>Container Information</h3>
    <div style="margin:10px;"><span>Select Container</span><span><select><option></option><option>Container #1</option><option>Container #2</option></select></span><span><button onclick="loadContainer('container_table','');">Load Container</button></span></div>
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
    function loadShippingSamples(tracking_num) {
        $('#package_info').css('display','block');
        $('#sample_list').css('display','block');
        $('#container_container').css('display','table-cell');
    }

    function loadContainer(table_id,container = '') {
        let containerLayout = ["A1","A2","A3","A4","A5","A6","A7","A8","A9","A10","B1","B2","B3","B4","B5","B6","B7","B8","B9","B10"];

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

        $('#'+table_id).append(tableHTML);
    }

    function loadSample(barcode_element,parent_id) {
        let barcode = barcode_element.value;
        $('#sample_info_container').css('display','table-cell');
        let sampleHTML = generateSampleInfo(barcode,parent_id);
        $('#sample_info').html(sampleHTML);
    }

    function generateSampleInfo(barcode,parent_id) {
        return "<table><tr><td>Sample ID</td><td>"+barcode+"</td></tr><tr><td>Sample Type</td><td>Blood</td></tr><tr><td>Issues</td><td><span><input id='sample_issue_1' type='checkbox' value='1' /><label for='sample_issue_1'>Wrong Sample Type</label></span><br/><span><input id='sample_issue_2' type='checkbox' value='2' /><label for='sample_issue_2'>Broken Tube</label></span><br/><span><input id='sample_issue_3' type='checkbox' value='3' /><label for='sample_issue_3'>Missing</label></span></td></tr><tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\""+barcode+"\",\""+parent_id+"\")' value='Save Sample' /></td></tr></table>";
    }

    function saveSample(sample_id,sample_cell_id) {
        $('#sample_info_container').css('display','none');
        $('#'+sample_cell_id).html("ID: "+sample_id+"<br/>Type: Blood");
    }
</script>