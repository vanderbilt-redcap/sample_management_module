<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';

$project = new \Project((int)$_GET['pid']);
$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project->project_id);

$shippingData = array();
$trackingNum = "";
$ajaxUrl = $module->getUrl('interface/ajax.php');

?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<h2>Receiving Samples</h2>
<span>
    <label for="tracking_num">Tracking Number: </label><select name="tracking_num" id="tracking_num" onchange="loadShippingInfo('<?php echo $ajaxUrl; ?>','<?php echo $project->project_id; ?>','tracking_num','package_info');loadShippingSamples('<?php echo $ajaxUrl; ?>','<?php echo $project->project_id; ?>','tracking_num','samplelist_container');loadAllContainers('<?php echo $ajaxUrl; ?>','<?php echo $project->project_id; ?>','container_select');"><option></option></select>
    <div id="package_info" style="display:none;">
    </div>
</span>

<div id="sample_list" class="sample_section" style="display:none;">
    <h3>Sample List</h3>
    <span id="samplelist_container" class="sample_table"></span>
</div>

<span style="display:table-row;">
<span id="container_container" class="sample_section" style="display:none;">
    <h3>Container Information</h3>
    <div style="margin:10px;"><span>Select Container</span><span><select id="container_select" onchange="loadContainer('<?php echo $ajaxUrl; ?>','<?php echo $project->project_id; ?>','<?php echo $project->firstEventId; ?>',this.value,'container_table','');"><option></option></select></span></div>
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
    .sample_table {
        width:850px;
        margin:25px;
        display:inline-block;
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
    table.dataTable tbody th, table.dataTable tbody td {
        padding: 0 0 0 10px
    }
</style>
<script type="text/javascript" src="<?php echo $module->getUrl('js/functions.js'); ?>"></script>
<script type="text/javascript">
    $(document).ready(function() {
       $('#receive_date').datepicker();
       getShippingIds('<?php echo $ajaxUrl; ?>','<?php echo $project->project_id; ?>','tracking_num');
    });
</script>