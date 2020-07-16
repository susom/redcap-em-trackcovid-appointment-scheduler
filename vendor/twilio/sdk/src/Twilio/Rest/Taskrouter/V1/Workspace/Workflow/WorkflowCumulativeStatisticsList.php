<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Taskrouter\V1\Workspace\Workflow;

use Twilio\ListResource;
use Twilio\Version;

class WorkflowCumulativeStatisticsList extends ListResource
{
    /**
     * Construct the WorkflowCumulativeStatisticsList
     *
     * @param Version $version Version that contains the resource
     * @param string $workspaceSid The SID of the Workspace that contains the
     *                             Workflow.
     * @param string $workflowSid Returns the list of Tasks that are being
     *                            controlled by the Workflow with the specified Sid
     *                            value
     * @return \Twilio\Rest\Taskrouter\V1\Workspace\Workflow\WorkflowCumulativeStatisticsList
     */
    public function __construct(Version $version, $workspaceSid, $workflowSid)
    {
        parent::__construct($version);

        // Path Solution
        $this->solution = array('workspaceSid' => $workspaceSid, 'workflowSid' => $workflowSid,);
    }

    /**
     * Constructs a WorkflowCumulativeStatisticsContext
     *
     * @return \Twilio\Rest\Taskrouter\V1\Workspace\Workflow\WorkflowCumulativeStatisticsContext
     */
    public function getContext()
    {
        return new WorkflowCumulativeStatisticsContext(
            $this->version,
            $this->solution['workspaceSid'],
            $this->solution['workflowSid']
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString()
    {
        return '[Twilio.Taskrouter.V1.WorkflowCumulativeStatisticsList]';
    }
}