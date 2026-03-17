<?php

namespace NotificationChannels\Max;

use Illuminate\Notifications\Notification;
use NotificationChannels\Max\Exceptions\CouldNotSendNotification;

class MaxChannel
{
    protected MaxApi $api;

    public function __construct(MaxApi $api)
    {
        $this->api = $api;
    }

    /**
     * Send the given notification.
     *
     * @throws CouldNotSendNotification
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        /** @var MaxMessage $message */
        $message = $notification->toMax($notifiable);

        if (! $message instanceof MaxMessage) {
            throw CouldNotSendNotification::invalidMessage();
        }

        // If no user/chat was set on the message, try to get it from the notifiable
        if ($message->getUserId() === null && $message->getChatId() === null) {
            $route = $notifiable->routeNotificationFor('max', $notification);

            if ($route === null) {
                throw CouldNotSendNotification::missingRecipient();
            }

            if (is_array($route)) {
                // ['chat_id' => 123] or ['user_id' => 456]
                if (isset($route['chat_id'])) {
                    $message->toChat($route['chat_id']);
                } elseif (isset($route['user_id'])) {
                    $message->to($route['user_id']);
                }
            } else {
                // Assume it's a user_id (integer)
                $message->to((int) $route);
            }
        }

        $response = $this->api->sendMessage($message);

        return $response->json();
    }
}
