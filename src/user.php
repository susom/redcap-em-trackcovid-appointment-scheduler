<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

try {
    if ($user = $module->verifyCookie('login')) {
        //JS and CSS with inputs URLs
        $recordId = $user['id'];
        $url = $module->getUrl('src/list.php', true, true,
                true) . '&event_id=' . $module->getSlotsEventId() . '&' . COMPLEMENTARY_SUFFIX . '=' . $module->getSuffix();
        require_once 'urls.php';
        ?>
        <link rel="stylesheet" href="<?php echo $module->getUrl('src/css/types.css', true, true) ?>">
        <script src="<?php echo $module->getUrl('src/js/user.js', true, true) ?>"></script>
        <script>
            User.listURL = "<?php echo $url ?>"
            User.slotsEventId = "<?php echo $module->getSlotsEventId() ?>"
            User.submitURL = "<?php echo $module->getUrl('src/book.php', true,
                    true) . '&pid=' . $module->getProjectId()  ?>"
            User.cancelURL = "<?php echo $module->getUrl('src/cancel.php', true,
                    true) . '&pid=' . $module->getProjectId() ?>"
            User.userListURL = "<?php echo $module->getUrl('src/user_list.php', true, true)?>"
            User.loginURL = "<?php echo $module->getUrl('src/login.php', true, true) ?>"
            User.locationsEventId = "<?php echo $module->getProjectSetting('testing-sites-event') ?>"
            User.locations = <?php echo json_encode($module->locationRecords) ?>
        </script>

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

                                <ul class="navbar-nav"><?php
                                    if ($user['record'][$module->getFirstEventId()]['first_name']) {
                                        ?>
                                        <li class="nav-item active">
                                            <a class="nav-link" href="#">
                                                <h5><?php echo $user['record'][$module->getFirstEventId()]['first_name'] . ' ' . $user['record'][$module->getFirstEventId()]['last_name'] ?></h5>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="nav-item ">
                                        <a class="nav-link logout" href="#"><p>Logout</p></a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="col-12">
                <?php
                $instances = $module->getInstances();
                $instance = $instances[0];
                echo $instance['instance_description'];
                $locations = $module->getLocationRecords();
                $array = array();
                $counties = parseEnum($module->getProject()->metadata['county']['element_enum']);

                foreach ($locations as $location) {
                    $county = $location[$module->getProjectSetting('testing-sites-event')]['county'];
                    $array[$county][] = $location[$module->getProjectSetting('testing-sites-event')];
                }
                ?>

                <div class="accordion mb-3" id="accordionExample">
                    <div class="card" style="    border-bottom-width: 1px  !important;border-bottom-style: solid !important;
    border-bottom-color: rgba(0, 0, 0, 0.125) !important;">
                        <div class="card-header" id="headingOne">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse"
                                        data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    View Testing Sites Information:
                                </button>
                            </h2>
                        </div>

                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                             data-parent="#accordionExample">
                            <div class="card-body">
                                <ul>
                                    <?php
                                    foreach ($array as $c => $county) {
                                        ?>
                                        <li><h4><?php echo $counties[$c]; ?></h4>
                                            <ul>
                                                <?php
                                                foreach ($county as $site) {
                                                    ?>
                                                    <li><strong><?php echo $site['title'] ?>
                                                            : <?php echo $site['testing_site_address'] ?></strong>
                                                        <p><?php echo $site['site_details'] ?></p></li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        </li>
                                        <?php
                                    }

                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <table id="appointments" class="display table table-striped table-bordered"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Visit</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
            <div id="complete-section" class="row" style="display: none">
                <div class="col text-center">
                    <button id="complete-schedule" class="btn btn-success btn-lg">Complete</button>
                </div>
            </div>
        </div>

        <?php
    } else {
        redirect($module->getUrl('src/login.php', true, false));
    }

    require_once 'models.php';
    ?>
    <div class="loader"><!-- Place at bottom of page --></div>
    <?php
} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}