<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Notification;
use App\Models\FavoriteProvider;
use App\Models\Proposal;
use App\Models\Property;
use App\Models\Rating;
use App\Models\Transaction;
use App\Traits\ChatMessages;
use App\Traits\OrderTrait;
use App\Mail\JobCompletedCustomerMail;
use App\Mail\JobCompletedProviderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JobHistoryController extends ApiBaseController
{
    use ChatMessages,OrderTrait;

    public function jobs($type)
    {
        try {
            $this->jobs = Order::whereUserId(auth()->id())->where('payment_status',2)->whereStatus($type == 'upcoming-jobs' ? 1 : ($type == 'ongoing-jobs' ? 2 : ($type == 'completed-jobs' ? 3 : 4)))->latest()->with('property','period','images','provider','rating')->get();
            $this->cancelJobFee = settings('cancel_job_charges');

            return parent::resp(true, 'Here are '. $type .'.',$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function jobsDetails($id)
    {
        try {
            $this->jobDetails = Order::with('provider','images','beforeImages','afterImages','rating')->find($id);

            return parent::resp(true, 'Here is your job detail.',$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function cancelJob($id)
    {
        try {
            $order = Order::find($id);
            if($order->on_the_way) return parent::resp(false, 'Order can not be cancelled because provider is on the way.');
            $order->status = 4;
            $order->cancel_order_date = now();
            $order->cancel_reason = 'Canceled by '.auth()->user()->first_name." ".auth()->user()->last_name." (Customer)";
            $order->cancellation_charges = $order->assigned_to ? settings('cancel_job_charges') : null;
            $order->save();

            $message = "Order # " . $order->order_id . " has been cancelled.jaspreet";
            $favoriteProviders = FavoriteProvider::with('user')->whereUserId(auth()->user()->id)->get();

            foreach ($favoriteProviders as $favoriteProvider) {
                sendNotification(
                    $favoriteProvider->provider_id,
                    auth()->id(),
                    'Order cancelled',
                    $message,
                    'Order cancelled',
                    $order->id
                );
            }
    
            if ($order->assigned_to) {
                $this->sendSms($order->provider->phone_number, $message);
            }

            return parent::resp(true, 'Order has been cancelled.');
        } catch (\Throwable $th) {
           return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }

    }

    public function upcomingJobsProposals(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), ['order_id' => 'required']);
            if ($validator->fails()) {return parent::resp(false, 'Validation errors', null, $validator->errors());}

            $this->proposals = Proposal::whereOrderId($req->order_id)->with('provider')->get();

            return parent::resp(true, 'Here are proposals for this job.',$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function acceptProposal(Request $req, $id)
    {
        try {
            $proposal = Proposal::find($id);
            $order = Order::find($proposal->order_id);
            $order['assigned_to'] = $proposal->provider_id;
            $order['provider_assigned_date'] = now();
            $order['status'] = 2;
            $order->save();

            Proposal::whereOrderId($proposal->order_id)->delete();

            $message = 'Your proposal has been accepted for order # '.$order->order_id;

            sendNotification(
                $order->assigned_to,
                auth()->id(),
                'Proposal accepted',
                $message,
                'Proposal Accepted',
                $order->id
            );

            // $this->sendSms($proposal->user->phone_number,$message);
            $this->sendSms($proposal->user->phone_number,$message);

            return parent::resp(true, 'Proposal has been accepted successfully.',$order);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function declineProposal(Request $req, $id)
    {
        try {
            $proposal = Proposal::find($id);
            $proposal->delete();

            sendNotification(
                $proposal->provider_id,
                auth()->id(),
                'Proposal cancelled',
                "Your proposal for order # ".$proposal->order->order_id." has been cancelled by ".auth()->user()->first_name." ".auth()->user()->last_name." (Customer)",
                'Proposal Cancelled',
                null
            );
            // $this->sendSms($proposal->user->phone_number,"Your proposal for order # ".$proposal->order->order_id." has been cancelled by ".auth()->user()->first_name." ".auth()->user()->last_name." (Customer)");
            $this->sendSms($proposal->user->phone_number,"Your proposal for order # ".$proposal->order->order_id." has been cancelled by ".auth()->user()->first_name." ".auth()->user()->last_name." (Customer)");
            return parent::resp(true, 'Proposal has been deleted successfully.');
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function ongoingJobsDetails($id)
    {
        $this->job = Order::with('images','provider','rating')->find($id);
        return parent::resp(true, 'Here are Ongoing-jobs detail.',$this->data);
    }

    public function providerLastLocation($id)
    {
        $this->provider = User::with('providerLastLocation')->select(['id','image'])->find($id);
        return parent::resp(true, 'Here is provider last known location.',$this->data);
    }

    public function toggleFavoriteProvider($id)
    {
        try {
            $favoriteProvider = FavoriteProvider::whereProviderId($id)->first();

            if(!$favoriteProvider){
                FavoriteProvider::create(['user_id'=>auth()->id(),'provider_id'=>$id]);
                $message = 'Favorite provider has been added';
                sendNotification(
                    $id,
                    auth()->user()->id,
                    'Favourite Provider',
                    "Your are now favourite provider of customer ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)",
                    'Favourite Provider',
                    null
                );
            }else{
                $favoriteProvider->delete();
                $message = 'Favorite provider has been removed';
                sendNotification(
                    $id,
                    auth()->user()->id,
                    'Removed Favourite Provider',
                    "Your have been removed from favorite provider by ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)",
                    'Removed Favourite Provider',
                    null
                );
            }

            return parent::resp(true, $message);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function providerDetails(Request $req,$id)
    {
        try {
            $this->providerDetail = User::find($id);
            $this->level = getProviderLevel($id);
            $this->calculateAllRatings($id);

            return parent::resp(true, 'Here is provider detail.',$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function customersChat($order_id)
    {
        try {
            $order = Order::whereId($order_id)->whereUserId(auth()->id())->first();
            if(!$order) return parent::resp(false, "You do not have access to this chat");
            $this->order_id = $order->order_id;
            $this->provider = $order->provider()->select(['id','first_name','last_name','image'])->first();
            $this->messages = ChatMessage::whereOrderId($order_id)->get();

            return parent::resp(true, "Chat returned successfully" ,$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }

    }

    public function sendMessage(Request $req)
    {
        $order= Order::find($req->order_id);
        try {
            $validator = Validator::make($req->all(), [
                'message' => 'required',
                'order_id' => 'required',
                'user_id' => 'required',
                'order_no' => 'required',
            ]);
            if ($validator->fails()) {
                return parent::resp(false, 'Validation errors', null, $validator->errors());
            }
            $user = User::find( $req->user_id);
            
            $this->sendSms($user->phone_number, "New message on order # ".$req->order_no);
            $this->sendChatMessage($req);

            sendNotification(
                $req->user_id,
                auth()->id(),
                "New message on order # ".$req->order_no,
                $req->message,
                "Chat",
                $order->id
            );

            // $user = User::find( $req->user_id);
            // $this->sendSms($user->phone_number, "New message on order # ".$req->order_no);
            return parent::resp(true, 'Message sent');
        } catch (\Throwable $th) {
            return parent::resp(false, "On the server, an unforeseen event occurred" . $th->getMessage());
        }
    }

    public function calculateAllRatings($provider_id)
    {
        $this->totalScore = 0;
        $this->qualityRatingPerc = 0;
        $this->responseOnTimePerc = 0;
        $this->completeJobsPerc = 0;
        $this->cancelJobsPerc = 0;
        $totalRatingsCount = Rating::whereProviderId($provider_id)->count();
        $totalJobs = Order::whereAssignedTo($provider_id)->wherePaymentStatus(2)->whereIn('status',[2,3,4])->count();

        if ($totalRatingsCount) {
            $sumOfQualityRatings = Rating::whereProviderId($provider_id)->sum('quality_rating');
            $qualityRating = $sumOfQualityRatings / $totalRatingsCount;

            $sumOfResponseOnTimeRatings = Rating::whereProviderId($provider_id)->sum('response_time_rating');
            $responseOnTimeRating = $sumOfResponseOnTimeRatings / $totalRatingsCount;

            $this->responseOnTimePerc = ($responseOnTimeRating / 5) * 100;
            $this->qualityRatingPerc = ($qualityRating / 5) * 100;
            $this->totalScore = ($this->responseOnTimePerc + $this->qualityRatingPerc) / 2;
        }

        if ($totalJobs) {
            $completedJobs = Order::whereAssignedTo($provider_id)->wherePaymentStatus(2)->whereStatus(3)->latest()->count();
            $this->completeJobsPerc = ($completedJobs / $totalJobs) * 100;

            $canceledJobs = Order::whereAssignedTo($provider_id)->wherePaymentStatus(2)->whereStatus(4)->latest()->count();
            $this->cancelJobsPerc = ($canceledJobs / $totalJobs) * 100;
        }
    }
    public function updateInstructions(Request $req,$id)
    {
        $validator = Validator::make($req->all(), ['instructions' => 'required']);
        if ($validator->fails()) {return parent::resp(false, 'Validation errors', null, $validator->errors());}

        $order = Order::whereId($id)->whereUserId(auth()->id())->first();
        if(!$order) return parent::resp(false, "Order does not exist");

        $order->instructions = $req->instructions;
        $order->save();

        $message = "Instruction for # " . $order->order_id . " has been updated by " . auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)";

        sendNotification(
            $order->assigned_to,
            auth()->id(),
            "New message on order # " . $order->order_id,
            $message,
            'Instruction',
            $order->id
        );

        $user = User::find($order->assigned_to);
        $this->sendSms($user->phone_number, $message);
        return parent::resp(true, "Instructions updated successfully");
    }


    public function markJobAsCompleted($id)
    {
        $order = Order::whereId($id)->whereUserId(auth()->id())->wherePaymentStatus(2)->first();
        if(!$order) return parent::resp(false, "Order does not exist");

        if($order->paid_to_provider != 1 && $order->provider->provider_account_id) {
            
            try {
                $account = $this->stripe->accounts->retrieve(
                    $order->provider->provider_account_id,
                    []
                );

                if($account->payouts_enabled) {
                    $transfer = $this->stripe->transfers->create([
                        'amount' => $order->provider_amount * 100,
                        'currency' => 'usd',
                        'destination' => $order->provider->provider_account_id,
                        'transfer_group' => $order->order_id,
                    ]);

                    Transaction::create([
                        'user_id' => $order->user_id,
                        'provider_id' => $order->assigned_to,
                        'order_id' => $order->id,
                        'transaction_id' => $transfer->id,
                        'amount' => $order->provider_amount,
                        'status' => 2,
                        'type' => 2,
                        'category_id' => $order->category_id,
                        'account' => 'card',
                        'stripe_response' => json_encode($transfer)
                    ]);

                    $order->paid_to_provider = 1;
                }

            } catch (\Throwable $th) {
            }
        }

        $order->status_by_customer = 1;
        $order->save();

        sendNotification(
            $order->assigned_to,
            auth()->id(),
            'Job has completed',
            auth()->user()->first_name . " " . auth()->user()->last_name . " marked order as completed # " . $order->order_id,
            'Job has completed',
            $order->id
        );
        // $user = User::find($order->assigned_to);
        $customer = User::find($order->user_id);
        $provider = User::find($order->assigned_to);
        $serviceType = $order->category_id == 1 ? 'lawn mowing' : 'snow removal';
        
        try {
            Mail::to($customer->email)
            ->send(new JobCompletedCustomerMail(
                $customer->first_name,
                $serviceType
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send customer completion email: ' . $e->getMessage());
        }
    
        // Send email to provider
        try {
            Mail::to($provider->email)
                ->send(new JobCompletedProviderMail(
                    $provider->first_name,
                    $serviceType
                ));
        } catch (\Exception $e) {
            Log::error('Failed to send provider completion email: ' . $e->getMessage());
        }
        // $this->sendSms($user->phone_number,auth()->user()->first_name . " " . auth()->user()->last_name . " marked order as completed # " . $order->order_id);
        return parent::resp(true,'Order marked as completed');
    }

    public function orderDetail($id) 
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    
        $property = Property::find($order->property_id);
    
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
    
        return response()->json([
            'order' => [
                'lat' => $property->lat,
                'lng' => $property->lng,
                'assigned_to' => $order->assigned_to,
                'id' => $order->id,
                'date' => $order->created_at,
                'grand_total' => $order->grand_total,
                'on_the_way' => $order->on_the_way,
                'at_location_and_started_job'=> $order->at_location_and_started_job,
                'service_for' => $order->service_for,
                 'finished_job' => $order->finished_job,
            ],
            'property_address' => $property->address
        ]);
    }
}
