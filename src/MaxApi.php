<?php

namespace NotificationChannels\Max;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use NotificationChannels\Max\Exceptions\CouldNotSendNotification;

class MaxApi
{
    protected const BASE_URL = 'https://platform-api.max.ru';

    protected string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Send a message via the MAX API.
     *
     * @throws CouldNotSendNotification
     */
    public function sendMessage(MaxMessage $message): Response
    {
        $queryParams = $message->toQueryParams();
        $body = $message->toBody();

        $url = self::BASE_URL . '/messages?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($url, $body);

        if ($response->failed()) {
            throw CouldNotSendNotification::apiError(
                $response->status(),
                $response->body()
            );
        }

        return $response;
    }
}
