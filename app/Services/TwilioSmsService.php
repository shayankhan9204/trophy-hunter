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
    private Client $client;
    private string $from;

    public function __construct()
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');
        $this->from = config('services.twilio.messaging_service_sid')
            ?: config('services.twilio.from');

        $this->client = new Client($sid, $token);
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
        $params = ['body' => $body];

        // Use messaging service SID if configured, otherwise a plain From number.
        $messagingServiceSid = config('services.twilio.messaging_service_sid');
        if ($messagingServiceSid) {
            $params['messagingServiceSid'] = $messagingServiceSid;
        } else {
            $params['from'] = $this->from;
        }

        $message = $this->client->messages->create($to, $params);

        Log::info('Twilio SMS sent', [
            'sid' => $message->sid,
            'to'  => $to,
        ]);

        return $message->sid;
    }
}
