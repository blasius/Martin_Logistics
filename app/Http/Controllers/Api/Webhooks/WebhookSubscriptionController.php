<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSubscriptionController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $subscriptions = WebhookSubscription::where('user_id', $request->user()->id)
            ->withCount('deliveries')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);

        $subscription = WebhookSubscription::create([
            'user_id' => $request->user()->id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => Str::random(32),
        ]);

        return response()->json($subscription, 201);
    }

    public function update(Request $request, WebhookSubscription $webhookSubscription): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'sometimes|url|max:2048',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $webhookSubscription->update($validated);
        return response()->json($webhookSubscription);
    }

    public function destroy(WebhookSubscription $webhookSubscription): JsonResponse
    {
        $webhookSubscription->delete();
        return response()->json(['message' => 'Webhook subscription deleted.']);
    }

    public function deliveries(WebhookSubscription $webhookSubscription, Request $request): JsonResponse
    {
        $deliveries = WebhookDelivery::where('webhook_subscription_id', $webhookSubscription->id)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($deliveries);
    }

    public function retryDelivery(WebhookDelivery $webhookDelivery): JsonResponse
    {
        if ($webhookDelivery->status === 'success') {
            return response()->json(['message' => 'Delivery already succeeded.'], 400);
        }

        $this->webhookService->attemptDelivery($webhookDelivery);
        return response()->json(['message' => 'Retry initiated.']);
    }

    public function events(): JsonResponse
    {
        $events = [
            'order.created',
            'order.status_changed',
            'invoice.created',
            'invoice.status_changed',
            'delivery.confirmed',
            'trip.completed',
            'payment.received',
        ];

        return response()->json($events);
    }
}
