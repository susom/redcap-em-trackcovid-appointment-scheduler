<?php

namespace Stanford\TrackCovidAppointmentScheduler;


/**
 * Class Scheduler
 * @package Stanford\TrackCovidAppointmentScheduler
 * @property \Project $project
 * @property array $sites;
 * @property string $slotsEventId
 * @property string $testingSitesEventId
 */
class Scheduler
{
    private $project;

    private $sites;

    private $slotsEventId;

    private $testingSitesEventId;

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


}