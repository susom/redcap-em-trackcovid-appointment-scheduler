<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\TwiML\Voice;

use Twilio\TwiML\TwiML;

class Number extends TwiML
{
    /**
     * Number constructor.
     *
     * @param string $phoneNumber Phone Number to dial
     * @param array $attributes Optional attributes
     */
    public function __construct($phoneNumber, $attributes = array())
    {
        parent::__construct('Number', $phoneNumber, $attributes);
    }

    /**
     * Add SendDigits attribute.
     *
     * @param string $sendDigits DTMF tones to play when the call is answered
     * @return static $this.
     */
    public function setSendDigits($sendDigits)
    {
        return $this->setAttribute('sendDigits', $sendDigits);
    }

    /**
     * Add Url attribute.
     *
     * @param string $url TwiML URL
     * @return static $this.
     */
    public function setUrl($url)
    {
        return $this->setAttribute('url', $url);
    }

    /**
     * Add Method attribute.
     *
     * @param string $method TwiML URL method
     * @return static $this.
     */
    public function setMethod($method)
    {
        return $this->setAttribute('method', $method);
    }

    /**
     * Add StatusCallbackEvent attribute.
     *
     * @param string $statusCallbackEvent Events to call status callback
     * @return static $this.
     */
    public function setStatusCallbackEvent($statusCallbackEvent)
    {
        return $this->setAttribute('statusCallbackEvent', $statusCallbackEvent);
    }

    /**
     * Add StatusCallback attribute.
     *
     * @param string $statusCallback Status callback URL
     * @return static $this.
     */
    public function setStatusCallback($statusCallback)
    {
        return $this->setAttribute('statusCallback', $statusCallback);
    }

    /**
     * Add StatusCallbackMethod attribute.
     *
     * @param string $statusCallbackMethod Status callback URL method
     * @return static $this.
     */
    public function setStatusCallbackMethod($statusCallbackMethod)
    {
        return $this->setAttribute('statusCallbackMethod', $statusCallbackMethod);
    }

    /**
     * Add Byoc attribute.
     *
     * @param string $byoc BYOC trunk SID (Beta)
     * @return static $this.
     */
    public function setByoc($byoc)
    {
        return $this->setAttribute('byoc', $byoc);
    }
}