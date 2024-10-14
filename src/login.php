<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */

use REDCap;

//JS and CSS with inputs URLs
require_once 'urls.php';

if ($module->getProjectSetting('not-login-redirect-page') == '') {
    echo "<h1>YOU ARE NOT LOGGED IN. PLEASE TRY TO LOG IN USING YOUR SURVEY LINK.</h1>";
} else {
    ?>
    <div class="container">
        <?php
        echo $module->getProjectSetting('not-login-redirect-page');
        ?>
    </div>
    <?php
}
?>
