<?php

namespace NotificationChannels\Max;

use Illuminate\Support\Traits\Conditionable;

class MaxMessage
{
    use Conditionable;

    /** Message text (up to 4000 chars). */
    protected ?string $text = null;

    /** Target user ID. */
    protected ?int $userId = null;

    /** Target chat ID. */
    protected ?int $chatId = null;

    /** Disable link preview. */
    protected ?bool $disableLinkPreview = null;

    /** Notify chat participants. */
    protected bool $notify = true;

    /** Text format: markdown, html, or null (plain). */
    protected ?string $format = null;

    /** Message attachments (inline keyboards, images, etc.). */
    protected array $attachments = [];

    /** Link to another message. */
    protected ?array $link = null;

    /**
     * Create a new message instance.
     */
    public static function create(?string $text = null): static
    {
        return new static($text);
    }

    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    /**
     * Set message text.
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the target user ID.
     */
    public function to(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Alias for to() — set user ID.
     */
    public function userId(int $userId): static
    {
        return $this->to($userId);
    }

    /**
     * Set the target chat ID.
     */
    public function toChat(int $chatId): static
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * Set text format to Markdown.
     */
    public function markdown(): static
    {
        $this->format = 'markdown';

        return $this;
    }

    /**
     * Set text format to HTML.
     */
    public function html(): static
    {
        $this->format = 'html';

        return $this;
    }

    /**
     * Set text format explicitly.
     */
    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Disable link preview generation.
     */
    public function disableLinkPreview(bool $disable = true): static
    {
        $this->disableLinkPreview = $disable;

        return $this;
    }

    /**
     * Disable notification for chat participants.
     */
    public function silent(bool $silent = true): static
    {
        $this->notify = ! $silent;

        return $this;
    }

    /**
     * Add an inline keyboard attachment.
     *
     * $buttons should be an array of button rows:
     * [
     *     [ ['type' => 'link', 'text' => 'Open', 'url' => 'https://...'] ],
     *     [ ['type' => 'callback', 'text' => 'Click', 'payload' => 'data'] ],
     * ]
     */
    public function inlineKeyboard(array $buttons): static
    {
        $this->attachments[] = [
            'type' => 'inline_keyboard',
            'payload' => [
                'buttons' => $buttons,
            ],
        ];

        return $this;
    }

    /**
     * Upload and attach an image.
     *
     * @param  string  $filePath  Absolute path to the image file (JPG, PNG, GIF, etc.)
     */
    public function photo(string $filePath): static
    {
        /** @var MaxApi $api */
        $api = app(MaxApi::class);
        $result = $api->uploadFile($filePath, 'image');

        $this->attachments[] = [
            'type' => 'image',
            'payload' => $result,
        ];

        return $this;
    }

    /**
     * Upload and attach a video.
     *
     * @param  string  $filePath  Absolute path to the video file (MP4, MOV, etc.)
     */
    public function video(string $filePath): static
    {
        /** @var MaxApi $api */
        $api = app(MaxApi::class);
        $result = $api->uploadFile($filePath, 'video');

        $this->attachments[] = [
            'type' => 'video',
            'payload' => [
                'token' => $result['token'],
            ],
        ];

        return $this;
    }

    /**
     * Upload and attach an audio file.
     *
     * @param  string  $filePath  Absolute path to the audio file (MP3, WAV, etc.)
     */
    public function audio(string $filePath): static
    {
        /** @var MaxApi $api */
        $api = app(MaxApi::class);
        $result = $api->uploadFile($filePath, 'audio');

        $this->attachments[] = [
            'type' => 'audio',
            'payload' => [
                'token' => $result['token'],
            ],
        ];

        return $this;
    }

    /**
     * Upload and attach a file.
     *
     * @param  string  $filePath  Absolute path to the file
     */
    public function file(string $filePath): static
    {
        /** @var MaxApi $api */
        $api = app(MaxApi::class);
        $result = $api->uploadFile($filePath, 'file');

        $this->attachments[] = [
            'type' => 'file',
            'payload' => $result,
        ];

        return $this;
    }

    /**
     * Add a raw attachment array.
     */
    public function attachment(array $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Set a link to another message (reply/forward).
     */
    public function link(string $type, string $mid): static
    {
        $this->link = [
            'type' => $type,
            'mid' => $mid,
        ];

        return $this;
    }

    /**
     * Reply to a specific message.
     */
    public function replyTo(string $messageId): static
    {
        return $this->link('reply', $messageId);
    }

    /**
     * Forward a specific message.
     */
    public function forward(string $messageId): static
    {
        return $this->link('forward', $messageId);
    }

    /**
     * Get the user ID for routing.
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get the chat ID for routing.
     */
    public function getChatId(): ?int
    {
        return $this->chatId;
    }

    /**
     * Build the query parameters array.
     */
    public function toQueryParams(): array
    {
        $params = [];

        if ($this->userId !== null) {
            $params['user_id'] = $this->userId;
        }

        if ($this->chatId !== null) {
            $params['chat_id'] = $this->chatId;
        }

        if ($this->disableLinkPreview !== null) {
            $params['disable_link_preview'] = $this->disableLinkPreview ? 'true' : 'false';
        }

        return $params;
    }

    /**
     * Build the request body array.
     */
    public function toBody(): array
    {
        $body = [];

        if ($this->text !== null) {
            $body['text'] = $this->text;
        }

        if (! empty($this->attachments)) {
            $body['attachments'] = $this->attachments;
        }

        if ($this->link !== null) {
            $body['link'] = $this->link;
        }

        if ($this->notify === false) {
            $body['notify'] = false;
        }

        if ($this->format !== null) {
            $body['format'] = $this->format;
        }

        return $body;
    }

    /**
     * Send this message immediately via MaxApi.
     *
     * @throws \NotificationChannels\Max\Exceptions\CouldNotSendNotification
     */
    public function send(): array
    {
        /** @var MaxApi $api */
        $api = app(MaxApi::class);

        return $api->sendMessage($this)->json();
    }
}
