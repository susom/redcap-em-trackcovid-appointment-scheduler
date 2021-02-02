<?php


namespace Stanford\TrackCovidSharedAppointmentScheduler;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */

try {
    /**
     * check if user still logged in
     */
    if (!$module::isUserHasManagePermission()) {
        throw new \LogicException('You cant be here');
    }

    $weekDays = $module->getWeekdaysDates(filter_var($_GET['index'], FILTER_SANITIZE_NUMBER_INT));
    $totals = $module->buildWeeklyTotalsTable($weekDays);

    $locations = $module->getDefinedLocations();
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-1 d-flex">
                <button type="button"
                        data-index="<?php echo filter_var($_GET['index'], FILTER_SANITIZE_NUMBER_INT) - 1 ?>"
                        class="get-totals btn btn-primary justify-content-center align-self-center">
                    <span>Previous Week</span></button>
            </div>
            <div class="col-lg-10">
                <h3>Weekly Totals: <?php echo $weekDays[0] . ' - ' . $weekDays[count($weekDays) - 1] ?></h3>
                <?php
                if ($totals) {

                    ?>
                    <table id="weekly-totals-table" class="display">
                        <thead>
                        <tr>
                            <th>Testing Site</th>
                            <?php foreach ($weekDays as $day) {
                                ?>
                                <th><?php echo $day ?></th>
                                <?php
                            } ?>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($totals as $id => $site) {
                            ?>
                            <tr>
                                <td><?php echo $locations[$id] ?></td>
                                <?php
                                foreach ($weekDays as $day) {
                                    ?>
                                    <td><?php echo $site[$day] ? $site[$day] : 0 ?></td>
                                    <?php
                                }
                                ?>
                                <td><?php echo $site['total'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Total:</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                    <?php
                } else {
                    echo 'No scheduled visits for selected week';
                }
                ?>
            </div>
            <div class="col-lg-1 d-flex">
                <button type="button"
                        data-index="<?php echo filter_var($_GET['index'], FILTER_SANITIZE_NUMBER_INT) + 1 ?>"
                        class="get-totals btn btn-primary justify-content-center align-self-center">
                    <span>Next Week</span></button>
            </div>
        </div>

    </div>
    <?php
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
?>
