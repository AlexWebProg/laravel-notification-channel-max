<?php

namespace NotificationChannels\Max;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use NotificationChannels\Max\Exceptions\CouldNotSendNotification;

class MaxApi
{
    protected string $token;

    protected string $baseUrl;

    protected string|bool|null $verifySsl;

    public function __construct(string $token, ?string $baseUrl = null, string|bool|null $verifySsl = null)
    {
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl ?? 'https://platform-api2.max.ru', '/');

        // Default: use bundled Russian Trusted CA certificate
        if ($verifySsl === null) {
            $bundledCert = __DIR__ . '/../resources/certs/subca_ssl_rsa2024.crt';
            $this->verifySsl = file_exists($bundledCert) ? $bundledCert : true;
        } else {
            $this->verifySsl = $verifySsl;
        }
    }

    /**
     * Create a configured HTTP client instance.
     */
    protected function httpClient(?string $token = null): PendingRequest
    {
        $client = Http::withHeaders([
            'Authorization' => $token ?? $this->token,
        ]);

        if ($this->verifySsl === false) {
            $client = $client->withoutVerifying();
        } elseif (is_string($this->verifySsl) && file_exists($this->verifySsl)) {
            $client = $client->withOptions([
                'verify' => $this->verifySsl,
            ]);
        }

        return $client;
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

        $url = $this->baseUrl . '/messages?' . http_build_query($queryParams);

        $response = $this->httpClient($message->getToken())
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $body);

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
     * @param  string|null  $token  Optional custom bot token
     * @return array{url: string, token?: string}
     *
     * @throws CouldNotSendNotification
     */
    public function getUploadUrl(string $type = 'image', ?string $token = null): array
    {
        $url = $this->baseUrl . '/uploads?' . http_build_query(['type' => $type]);

        $response = $this->httpClient($token)->post($url);

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
     * @param  string|null  $token  Optional custom bot token
     * @return array  The upload response (contains token for image/file, or other data)
     *
     * @throws CouldNotSendNotification
     */
    public function uploadFile(string $filePath, string $type = 'image', ?string $token = null): array
    {
        // Step 1: Get upload URL
        $uploadData = $this->getUploadUrl($type, $token);
        $uploadUrl = $uploadData['url'];

        // Step 2: Upload file to the URL
        $response = $this->httpClient($token)
            ->timeout(120)
            ->attach(
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
