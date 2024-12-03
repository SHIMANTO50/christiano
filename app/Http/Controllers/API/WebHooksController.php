<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use App\Models\FirebaseTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Notifications\UserNotifications;

class WebHooksController extends Controller
{
    public function handle(Request $request): JsonResponse
    {

        // Retrieve the Authorization header
        $authorizationHeader = $request->header('Authorization');
        // Retrieve the expected token from environment variables
        $expectedToken = 'Bearer ' . config('services.revenuecat.webhook_secret');
        // Verify the Authorization header using hash_equals
        if (!$authorizationHeader || !hash_equals($expectedToken, $authorizationHeader)) {
            Log::error('Invalid Authorization header in webhook request.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Proceed with processing the webhook
        $event = $request->all();

        // Extract necessary data from the webhook
        $userId = $event['event']['app_user_id'] ?? null;
        $eventType = $event['event']['type'] ?? null;

        $event = $event['event'] ?? null;

        if (!$userId || !$eventType) {
            Log::error('Invalid webhook data: missing user ID or event type.');
            return response()->json(['message' => 'Invalid data'], 400);
        }

        // Find the user by user_id
        $user = User::findOrFail($userId);

        if (!$user) {
            Log::error('User not found for user_id: ' . $userId);
            return response()->json(['message' => 'User not found'], 404);
        }

        // Handle different event types
        switch ($eventType) {
            case 'PRODUCT_CHANGE':
            case 'INITIAL_PURCHASE':
                $this->handleInitialPurchase($user, $event);
                break;

            case 'RENEWAL':
                $this->handleRenewal($user, $event);
                break;

            case 'CANCELLATION':
                $this->handleCancellation($user, $event);
                break;

            case 'EXPIRATION':
                $this->handleExpiration($user, $event);
                break;
            case 'NON_RENEWING_PURCHASE':
                $this->handleNonRenewingPurchase($user, $event);
                break;
            case 'SUBSCRIPTION_EXTENDED':
                $this->handleSubscriptionExtended($user, $event);
                break;
            case 'UNCANCELLATION':
                $this->handleUncancellation($user, $event);
                break;
            case 'SUBSCRIPTION_PAUSED':
                $this->handleSubscriptionPaused($user, $event);
                break;
            default:
                Log::warning('Unhandled event type: ' . $eventType);
                return response()->json(['message' => 'Unhandled event type'], 200);
        }

        // Return a successful response
        return response()->json(['message' => 'Event processed successfully'], 200);
    }

    private function handleInitialPurchase(User $user, array $event)
    {
        $user->subscription_status = 'active';
        $user->subscription_start_date = now();
        $user->subscription_end_date = $event['expiration_at_ms'] / 1000 ?? null;
        $user->product_id = $event['product_id'] ?? null;
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "You have successfully subscribed to our service!"));

        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'You have successfully subscribed to our service!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }

    private function handleRenewal(User $user, array $event)
    {
        $user->subscription_status = 'active';
        $user->subscription_end_date = $event['expiration_at_ms'] / 1000 ?? null;
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has been renewed!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has been renewed!!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }

    private function handleCancellation(User $user, array $event)
    {
        $user->subscription_status = 'cancelled';
        $user->subscription_end_date = now();
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has been cancelled!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has been cancelled!!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }

    private function handleExpiration(User $user, array $event)
    {
        $user->subscription_status = 'expired';
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has expired!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has expired!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }

    private function handleNonRenewingPurchase(User $user, array $event)
    {
        $user->subscription_status = 'non_renewing';
        $user->subscription_start_date = now();
        $user->subscription_end_date = $event['expiration_at_ms'] / 1000 ?? null;
        $user->product_id = $event['product_id'] ?? null;
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "You have successfully subscribed to our service!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'You have successfully subscribed to our service!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }
    private function handleSubscriptionExtended(User $user, array $event)
    {
        $newExpiration = $event['expiration_at_ms'] / 1000 ?? null;
        $user->subscription_end_date = $newExpiration;
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has been extended!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has been extended!!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }
    private function handleUncancellation(User $user, array $event)
    {
        $user->subscription_status = 'active';
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has been uncancelled!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has been uncancelled!!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }
    private function handleSubscriptionPaused(User $user, array $event)
    {
        $user->subscription_status = 'paused';
        $user->last_event_at = now();
        $user->save();

        $user->notify(new UserNotifications($user->name, "Your subscription has been paused!"));
        //Send fire base notification
        $device_tokens = FirebaseTokens::where('user_id', $user->id)->where('is_active', '1')->first();
        if(!$device_tokens) return;
        $data = [
            'message' => 'Your subscription has been paused!!',
        ];
        Helper::sendNotifyMobile($device_tokens->token, $data);
    }
}
