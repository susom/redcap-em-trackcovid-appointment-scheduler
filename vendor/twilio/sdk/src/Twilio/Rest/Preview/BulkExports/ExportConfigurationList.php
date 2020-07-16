<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Preview\BulkExports;

use Twilio\ListResource;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 */
class ExportConfigurationList extends ListResource
{
    /**
     * Construct the ExportConfigurationList
     *
     * @param Version $version Version that contains the resource
     * @return \Twilio\Rest\Preview\BulkExports\ExportConfigurationList
     */
    public function __construct(Version $version)
    {
        parent::__construct($version);

        // Path Solution
        $this->solution = array();
    }

    /**
     * Constructs a ExportConfigurationContext
     *
     * @param string $resourceType The type of communication – Messages, Calls
     * @return \Twilio\Rest\Preview\BulkExports\ExportConfigurationContext
     */
    public function getContext($resourceType)
    {
        return new ExportConfigurationContext($this->version, $resourceType);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString()
    {
        return '[Twilio.Preview.BulkExports.ExportConfigurationList]';
    }
}