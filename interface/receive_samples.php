<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';
$project = new \Project((int)$_GET['pid']);
$module = new \Vanderbilt\SampleManagementModule\SampleManagementModule($project->project_id);
$sampleData = array();

if (isset($_POST['tracking_num'])) {
    $trackingNum = htmlspecialchars($_POST['tracking_num']);
    $lookupField = $module->getProjectSetting('lookup-field',$project->project_id);
    if ($trackingNum != "" && $lookupField != "") {
        $sampleData = $module->getSampleData($project->project_id, array($lookupField => $trackingNum));
    }
}
?>

<h2>Receiving Samples</h2>
<span>
    <form action="<?php echo $module->getUrl('receive_samples.php'); ?>" method="post">
        <label for="tracking_num">Tracking Number: </label><input type="text" name="tracking_num" id="tracking_num">
        <input type="submit" value="Load Samples" name="load_samples">
    </form>
    <div id="package_info"></div>
</span>

<span>
    <h3>Sample Information</h3>
    <div id="sample_table"></div>
</span>

<style>
    h2,h3 {
        margin-bottom: 2px;
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
       $('#receive_date').datepicker();
    });
</script>