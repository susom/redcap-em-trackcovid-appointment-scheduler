<?php

namespace Stanford\TrackCovidAppointmentScheduler;


/**
 * Class Scheduler
 * @package Stanford\TrackCovidAppointmentScheduler
 * @property \Project $project
 * @property array $sites;
 * @property string $slotsEventId
 * @property string $testingSitesEventId
 * @property array $slots
 */
class Scheduler
{

    use emLoggerTrait;

    private $project;

    private $sites;

    private $slotsEventId;

    private $testingSitesEventId;

    private $slots;
    /**
     * Scheduler constructor.
     * @param \Project $project
     * @param array $sites
     */
    public function __construct($project, $sites, $slotsEventId, $testingSitesEventId)
    {
        $this->setProject($project);
        $this->setSites($sites);
        $this->setSlotsEventId($slotsEventId);
        $this->setTestingSitesEventId($testingSitesEventId);
        $this->setSlots();
    }


    public function updateSlotBookedSpots($slot, $number = 1)
    {
        if ($slot['number_of_booked_slots']) {
            $slot['number_of_booked_slots'] = $slot['number_of_booked_slots'] + $number;
        } else {
            $slot['number_of_booked_slots'] = 1;
        }
        $response = \REDCap::saveData($this->getProject()->project_id, 'json', json_encode(array($slot)));
        if (!empty($response['errors'])) {
            if (is_array($response['errors'])) {
                $this->emError(implode(",", $response['errors']));
            } else {
                $this->emError($response['errors']);
            }
        }
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param \Project $project
     */
    public function setProject(\Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return array
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param array $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return string
     */
    public function getSlotsEventId()
    {
        return $this->slotsEventId;
    }

    /**
     * @param string $slotsEventId
     */
    public function setSlotsEventId($slotsEventId)
    {
        $this->slotsEventId = $slotsEventId;
    }

    /**
     * @return string
     */
    public function getTestingSitesEventId()
    {
        return $this->testingSitesEventId;
    }

    /**
     * @param string $testingSitesEventId
     */
    public function setTestingSitesEventId($testingSitesEventId)
    {
        $this->testingSitesEventId = $testingSitesEventId;
    }

    /**
     * @return array
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * @param array $slots
     */
    public function setSlots()
    {
        $param = array(
            'project_id' => $this->getProject()->project_id,
            'format' => 'array',
            'events' => $this->getSlotsEventId()
        );

        $slots = \REDCap::getData($param);
        $this->slots = $slots;
    }

    public function getSlot($id)
    {
        $slots = $this->getSlots();
        return $slots[$id];
    }

}