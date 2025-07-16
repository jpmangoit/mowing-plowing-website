<?php

use App\Events\NewNotification;
use App\Jobs\SendNotification;
use App\Models\Card;
use App\Models\Level;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

if (!function_exists('settings')) {
    function settings($key) {
        return Setting::where('field_key',$key)->first()->field_value;
    }
}

if (!function_exists('getDefaultCard')) {
    function getDefaultCard($user_id = null) {
        return Card::whereUserId($user_id ?? auth()->id())->whereIsDefault('1')->first();
    }
}

if (!function_exists('sendNotification')) {
    function sendNotification($receiver_id, $sender_id, $title, $content, $flag, $order_id)
    {
        try {
            $notification = Notification::create([
                'receiver_id' => $receiver_id,
                'sender_id' => $sender_id,
                'title' => $title,
                'content' => $content,
                'flag' => $flag,
                'order_id' => $order_id
            ]);

            broadcast(new NewNotification($notification))->toOthers();
        } catch (\Throwable $th) {
            Log::error('Error in SendNotification job: ' . $th->getMessage());
        }
    }
}

if(!function_exists('getProviderLevel')) {
    function getProviderLevel($provider_id = null) {
        $completed_orders = Order::whereAssignedTo($provider_id ?? auth()->user()->id)->wherePaidToProvider(1)->wherePaymentStatus(2)->whereStatus(3)->latest()->count();
        $earnings = Transaction::whereProviderId($provider_id ?? auth()->user()->id)->whereStatus(2)->whereType(2)->sum('amount');
        $levels = Level::orderBy('level', 'desc')->latest()->get();

        foreach ($levels as $level) {
            if ($completed_orders >= $level->completed_orders && $earnings >= $level->earnings) {
                return $level->level;
            }
        }

        return 0;
    }
}
