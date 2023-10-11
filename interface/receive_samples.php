<?php
require_once APP_PATH_DOCROOT.'ProjectGeneral/header.php';
?>

<div>
    <h2>Shipping Information</h2>
    <label for="receive_date">Date Shipment Received</label> <input type='text' value="" id="receive_date" /><br/>
    <label for="track_num">Tracking Label</label> <input type="text" value="" id="track_num" />
</div>
<div>
    <h2>Sample Types</h2>
    <input type="checkbox" id="samp_type_1" value="blister"> <label for="samp_type_1">Blister Fluids</label><br/>
    <input type="checkbox" id="samp_type_2" value="slough"> <label for="samp_type_2">Sloughed Skin</label><br/>
    <input type="checkbox" id="samp_type_3" value="blood"> <label for="samp_type_3">Blood</label><br/>
</div>
<div>
    <h3>Blister Fluids</h3>
    <div>
        <label for="blister_start">First Blister ID</label> <input type="text" id="blister_start"><br/>
        <label for="blister_stop">Last Blister ID</label> <input type="text" id="blister_stop">
    </div>
    <h3>Sloughed Skin</h3>
    <div>
        <label for="slough_start">First Slough ID</label> <input type="text" id="slough_start"><br/>
        <label for="slough_stop">Last Slough ID</label> <input type="text" id="slough_stop">
    </div>
    <h3>Blood</h3>
    <div>
        <label for="blood_start">First Blood ID</label> <input type="text" id="blood_start"><br/>
        <label for="blood_stop">Last Blood ID</label> <input type="text" id="blood_stop">
    </div>
</div>

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