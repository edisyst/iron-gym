<?php

namespace App\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        /** @var callable(mixed, Notification): array{title: string, body: string} $toWebPush */
        $toWebPush = [$notification, 'toWebPush'];
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $message = $notification->toWebPush($notifiable, $notification);

        $subscriptions = PushSubscription::where('user_id', $notifiable->id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapidPublic = config('services.vapid.public_key', '');
        $vapidPrivate = config('services.vapid.private_key', '');

        if (empty($vapidPublic) || empty($vapidPrivate)) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
            ]);
            $webPush->queueNotification($subscription, json_encode($message) ?: null);
        }

        foreach ($webPush->flush() as $report) {
            if (! $report->isSuccess()) {
                // Endpoint non più valido: rimuovi la subscription
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
