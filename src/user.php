<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

try {
    if ($user = $module->verifyCookie('login')) {
        //JS and CSS with inputs URLs
        define('USERID', $user[$module->getFirstEventId()]['full_name']);
        require_once 'urls.php';
        ?>
        <link rel="stylesheet" href="<?php echo $module->getUrl('src/css/types.css', true, true) ?>">
        <script src="<?php echo $module->getUrl('src/js/user.js', true, true) ?>"></script>
        <div id="brandbar">
            <div class="container">
                <div class="row">
                    <div class="col-3">
                        <a href="http://www.stanford.edu"><img
                                    src="https://www-media.stanford.edu/su-identity/images/brandbar-stanford-logo@2x.png"
                                    alt="Stanford University" width="152" height="23"></a>
                    </div>
                    <div class="col-9">
                        <nav class="navbar-expand-sm navbar-dark">
                            <div class="collapse navbar-collapse justify-content-end" id="navbarCollapse">
                                <?php
                                if (defined('USERID')) {
                                    ?>
                                    <ul class="navbar-nav">
                                        <li class="nav-item active">
                                            <a class="nav-link" href="#">Logged in
                                                as: <?php echo(defined('USERID') ? USERID : ' NOT LOGGED IN') ?></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="manage nav-link" href="#">Manage my Appointments</a>
                                        </li>
                                    </ul>
                                    <?php
                                }
                                ?>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <table id="appointments" class="display table table-striped table-bordered"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Visit</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $events = $module->getProject()->events['1']['events'];
                $reservationFields = \REDCap::getFieldNames('reservation');
                foreach ($events as $eventId => $event) {
                    //if we did not define reservation for this event skip it.
                    if (!in_array('reservation', $module->getProject()->eventsForms[$eventId])) {
                        continue;
                    }
                    // check if user has record for this event

                    if (isset($user[$eventId])) {
                        $reservation = $module->getReservationArray($reservationFields, $user[$eventId]);
                        if (empty($reservation)) {
                            $time = 'Not Scheduled';
                            $action = '<button class="btn btn-success">Schedule</button>';
                        } else {
                            //todo provide data to view and reschedule.
                        }

                    } else {
                        $time = 'Not Scheduled';
                        $action = '<button class="btn btn-success">Schedule</button>';
                    }
                    ?>
                    <tr>
                        <td><?php echo $event['descrip'] ?></td>
                        <td><?php echo $time ?></td>
                        <td><?php echo $action ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php
    } else {
        redirect($module->getUrl('src/login.php', false, false));
    }
} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}