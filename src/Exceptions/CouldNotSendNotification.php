<?php

namespace NotificationChannels\Max\Exceptions;

use Exception;

class CouldNotSendNotification extends Exception
{
    public static function apiError(int $statusCode, string $body): static
    {
        return new static(
            "MAX API responded with status {$statusCode}: {$body}"
        );
    }

    public static function invalidMessage(): static
    {
        return new static(
            'The toMax() method must return an instance of ' . \NotificationChannels\Max\MaxMessage::class
        );
    }

    public static function missingRecipient(): static
    {
        return new static(
            'No recipient specified. Either set user_id/chat_id on the message, '
            . 'or add a routeNotificationForMax() method to your notifiable model.'
        );
    }
}
