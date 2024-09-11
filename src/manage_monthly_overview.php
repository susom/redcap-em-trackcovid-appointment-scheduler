<?php


namespace Stanford\WISESharedAppointmentScheduler;

/** @var \Stanford\WISESharedAppointmentScheduler\WISESharedAppointmentScheduler $module */


$eventId = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
$instance = $module->getEventInstance();
?>
<link rel="stylesheet" href="<?php echo $module->getUrl('src/css/calendar.css') ?>">

<div class="container">
    <div class="row p-3 mb-2">
        <div class="col-8">
            <?php echo $instance['instance_description'] ?>
        </div>
    </div>

    <div class="row">
        <div class="date-picker-2" data-toggle="popover" data-html="true" data-content=""
             placeholder="Recipient's username" id="ttry" aria-describedby="basic-addon2"></div>
        <span class="" id="example-popover-2"></span>
    </div>
</div>

<input type="hidden" name="selected-date" id="selected-date"/>
<input type="hidden" name="selected-time" id="selected-time"/>

<!-- LOAD JS -->
<script src="<?php echo $module->getUrl('src/js/calendar.js') ?>"></script>

<?php
    require_once 'modals.php';
    ?>
    <div class="loader"><!-- Place at bottom of page --></div>
    <?php