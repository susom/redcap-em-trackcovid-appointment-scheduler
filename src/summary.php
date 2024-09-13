<?php

namespace Stanford\WISESharedAppointmentScheduler;

/** @var \Stanford\WISESharedAppointmentScheduler\WISESharedAppointmentScheduler $module */


$event_id = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
if (isset($_GET['month']) && isset($_GET['year'])) {
    $month = filter_var($_GET['month'], FILTER_SANITIZE_NUMBER_INT);
    $year = filter_var($_GET['year'], FILTER_SANITIZE_NUMBER_INT);
    $data = $module->getMonthSlots($event_id, $year, $month, '', 0, null, false, '', true);
    if (!empty($_GET['pid']) && $module->getProjectSetting('enable-redcap-calendar')) {
        $records = $module->getProjectREDCapCalendar(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT), $year,
            $month);
    }

} else {
    $data = $module->getMonthSlots($event_id);
    if (!empty($_GET['pid']) && $this->getProjectSetting('enable-redcap-calendar')) {
        $records = $module->getProjectREDCapCalendar(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT));
    }
}
$days = array();
$eventIds[] = $module->getFirstEventId();
$name_field = $module->getProjectSetting('name-field');
$records = $module->getParticipant()->getAllReservedSlots($module->getProjectId(), $eventIds);
//process scheduler calendar
foreach ($data as $slot) {
    $slot = array_pop($slot);
    /**
     * group by day
     */
    $day = (int)date('d', strtotime($slot['start']));

    // get the date so we can pull reserved slots for that date.
    $date = date('Y-m-d', strtotime($slot['start']));

    // skip day if already processed.
    if(!empty($days[$day])) {
        continue;
    }

    $reserved = $module->getParticipant()->getDateReservedSlots($module->getProjectId(), $date, $name_field, $module->getFirstEventId(), $eventIds);


    /**
     * if we have available slots on that day
     */
    if (!empty($reserved)) {
        $days[$day]['available']++;

        // sort by reservation datetime
        $reserved = $module->getParticipant()->orderReservedSlots($reserved);


        foreach ($reserved as $reservedSlot) {



            $name = $reservedSlot[$name_field];
            if($days[$day]['availableText']){
                $days[$day]['availableText'] .= ', '. $name . ': ' . date('h:i A', strtotime($reservedSlot['reservation_datetime'])) ;
            }else{
                $days[$day]['availableText'] = $name . ': ' . date('h:i A', strtotime($reservedSlot['reservation_datetime']));
                $days[$day]['fullDateText'] = date('Y-m-d', strtotime($reservedSlot['reservation_datetime']));

            }
        }
//        /**
//         * no need to show more than three available slots
//         */
//        if ($days[$day]['available'] <= 3) {
//            $days[$day]['availableText'] .= 'REDCap Appt ' . date('H:i',
//                    strtotime($slot['start'])) . ' - ' . date('H:i', strtotime($slot['end'])) . ' ';
//        }
    } else {
        $days[$day]['booked'] = 0;
    }

    /**
     *
     */
}

//if enabled get REDCap Calendar.
if (!empty($_GET['pid']) && $module->getProjectSetting('enable-redcap-calendar')) {

    while ($row = db_fetch_assoc($records)) {
        $day = (int)date('d', strtotime($row['event_date']));
        //show as many REDCap calendar records as there is.
        if (!isset($days[$day])) {
            $days[$day]['available'] = 1;
        }
        $days[$day]['REDCapAvailableText'] .= '<a class="ui-state-default redcap-default" style="min-height: unset" href="javascript:;" onclick="popupCal(' . $row['cal_id'] . ', 600)">' . $row['event_time'] . ' ' . $row['notes'] . '</a>';
    }
}
echo json_encode($days);