<?php

namespace App\Services;

use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        $subscriptions = WebhookSubscription::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
                'max_attempts' => 3,
            ]);

            $this->attemptDelivery($delivery, $subscription);
        }
    }

    public function attemptDelivery(WebhookDelivery $delivery, ?WebhookSubscription $subscription = null): void
    {
        $subscription ??= $delivery->subscription;

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Martin-Logistics-Webhook/1.0',
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Delivery-Id' => $delivery->id,
            ];

            if ($subscription->secret) {
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($delivery->payload), $subscription->secret);
            }

            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->post($subscription->url, $delivery->payload);

            $delivery->update([
                'status_code' => $response->status(),
                'response_body' => Str::limit($response->body(), 5000),
                'attempts' => $delivery->attempts + 1,
                'last_attempt_at' => now(),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if (!$response->successful() && $delivery->attempts < $delivery->max_attempts) {
                $delivery->update([
                    'status' => 'retrying',
                    'next_attempt_at' => now()->addMinutes(5 * $delivery->attempts),
                ]);
            }
        } catch (\Exception $e) {
            $delivery->update([
                'status_code' => 0,
                'response_body' => $e->getMessage(),
                'attempts' => $delivery->attempts + 1,
                'last_attempt_at' => now(),
                'status' => $delivery->attempts >= $delivery->max_attempts ? 'failed' : 'retrying',
                'next_attempt_at' => $delivery->attempts < $delivery->max_attempts ? now()->addMinutes(5 * $delivery->attempts) : null,
            ]);
        }
    }

    public function retryFailed(): int
    {
        $retried = 0;
        $deliveries = WebhookDelivery::where('status', 'retrying')
            ->where('next_attempt_at', '<=', now())
            ->where('attempts', '<', \DB::raw('max_attempts'))
            ->get();

        foreach ($deliveries as $delivery) {
            $this->attemptDelivery($delivery);
            $retried++;
        }

        return $retried;
    }
}
