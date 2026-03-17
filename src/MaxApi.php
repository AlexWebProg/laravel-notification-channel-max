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

    /**
     * Get an upload URL from the MAX API.
     *
     * @param  string  $type  Upload type: image, video, audio, file
     * @return array{url: string, token?: string}
     *
     * @throws CouldNotSendNotification
     */
    public function getUploadUrl(string $type = 'image'): array
    {
        $url = self::BASE_URL . '/uploads?' . http_build_query(['type' => $type]);

        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post($url);

        if ($response->failed()) {
            throw CouldNotSendNotification::apiError(
                $response->status(),
                $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Upload a file to the MAX API and return the upload response.
     *
     * @param  string  $filePath  Absolute path to the file
     * @param  string  $type  Upload type: image, video, audio, file
     * @return array  The upload response (contains token for image/file, or other data)
     *
     * @throws CouldNotSendNotification
     */
    public function uploadFile(string $filePath, string $type = 'image'): array
    {
        // Step 1: Get upload URL
        $uploadData = $this->getUploadUrl($type);
        $uploadUrl = $uploadData['url'];

        // Step 2: Upload file to the URL
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->attach(
            'data',
            file_get_contents($filePath),
            basename($filePath)
        )->post($uploadUrl);

        if ($response->failed()) {
            throw CouldNotSendNotification::apiError(
                $response->status(),
                $response->body()
            );
        }

        $result = $response->json();

        // For video/audio, token comes from getUploadUrl, not from upload response
        if (in_array($type, ['video', 'audio']) && isset($uploadData['token'])) {
            $result['token'] = $uploadData['token'];
        }

        return $result;
    }
}
