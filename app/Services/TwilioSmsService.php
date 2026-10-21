<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Thin wrapper around the Twilio SDK.
 *
 * Credentials are read from config/services.php (never from env() directly).
 * Bind this class as a singleton in AppServiceProvider so callers don't
 * construct the Twilio Client on every use:
 *
 *   $this->app->singleton(TwilioSmsService::class);
 */
class TwilioSmsService
{
    private ?Client $client = null;
    private ?string $from = null;

    public function __construct()
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');
        $this->from = config('services.twilio.messaging_service_sid')
            ?: config('services.twilio.from')
            ?: null;

        if (!empty($sid) && !empty($token)) {
            $this->client = new Client($sid, $token);
        }
    }

    /**
     * Send an SMS and return the Twilio Message SID on success.
     * Throws TwilioException on failure (callers decide how to handle it).
     *
     * @param  string $to   E.164-formatted recipient number, e.g. "+61412345678"
     * @param  string $body Message text
     * @return string       Twilio Message SID
     * @throws TwilioException
     */
    public function send(string $to, string $body): string
    {
        if (! $this->client) {
            throw new TwilioException('Twilio credentials (TWILIO_SID or TWILIO_AUTH_TOKEN) are missing or not configured.');
        }

        $params = ['body' => $body];

        // Use messaging service SID if configured, otherwise a plain From number.
        $messagingServiceSid = config('services.twilio.messaging_service_sid');
        if (! empty($messagingServiceSid)) {
            $params['messagingServiceSid'] = $messagingServiceSid;
        } elseif (! empty($this->from)) {
            $params['from'] = $this->from;
        } else {
            throw new TwilioException('Neither TWILIO_MESSAGING_SERVICE_SID nor TWILIO_FROM is configured.');
        }

        $message = $this->client->messages->create($to, $params);

        Log::info('Twilio SMS sent', [
            'sid' => $message->sid,
            'to'  => $to,
        ]);

        return $message->sid;
    }
}
