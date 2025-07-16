<?php

namespace App\Http\Controllers\Client;

use App\Models\ChatMessage;
use App\Models\FavoriteProvider;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\Rating;
use App\Models\Transaction;
use App\Traits\ChatMessages;
use App\Traits\OrderTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Mail\OrderCancelledMail;
use App\Models\User;
use App\Mail\JobCompletedCustomerMail;
use App\Mail\JobCompletedProviderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class JobHistoryController extends ClientBaseController
{
    use ChatMessages,OrderTrait;

    public function jobs($pageSlug)
    {
        $this->pageSlug = $pageSlug;
        $pageSlugArray = explode('-',$pageSlug);
        $this->pageTitle = ucfirst($pageSlugArray[0]).' '.ucfirst($pageSlugArray[1]).' '.ucfirst($pageSlugArray[2] ?? '');
        $this->jobs = Order::whereUserId(auth()->id())->where('payment_status',2)->whereStatus($pageSlug == 'upcoming-jobs' ? 1 : ($pageSlug == 'ongoing-jobs' ? 2 : ($pageSlug == 'completed-jobs' ? 3 : ($pageSlug == 'cancelled-jobs' ? 4 : 2))))->latest()->get();

        return view('client.job-history.jobs',$this->data);
    }

    public function jobsDetails($id)
    {
        $this->jobDetails = Order::whereId($id)->whereUserId(auth()->id())->first();
        return view('client.job-history.job-details',$this->data);
    }

    public function jobsDetailsUpdate(Request $req,$id)
    {
        $order = Order::find($id);
        Order::whereUserId(auth()->id())->whereId($id)->update([
            'instructions' => $req->instructions
        ]);

        $message = "Instruction for # " . $order->order_id . " has been updated by " . auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)";

        sendNotification(
            $order->assigned_to,
            auth()->id(),
            "New message on order # " . $order->order_id,
            $message,
            'Instruction',
            $order->id
        );
        return back()->with('success', 'Instructions has been updated');
    }

    public function cancelJobWarning($id)
    {
        $this->job = Order::whereId($id)->whereUserId(auth()->id())->first();
        return view('client.job-history.__cancel',$this->data);
    }

    public function cancelJob($id)
    {
        $order = Order::whereId($id)->whereUserId(auth()->id())->first();
        if($order->on_the_way) return redirect()->back()->with('error','Order can not be cancelled because provider is on the way.');
        $order->status = 4;
        $order->cancel_order_date = now();
        $order->cancel_reason = 'Canceled by '.auth()->user()->first_name." ".auth()->user()->last_name." (Customer)";
        $order->cancellation_charges = $order->assigned_to ? settings('cancel_job_charges') : null;
        $order->save();

        // if($order->assigned_to) {

        //     $message = "Order # ".$order->order_id." has been cancelled by ".auth()->user()->first_name." ".auth()->user()->last_name." (Customer)";

        //     sendNotification(
        //         $order->assigned_to,
        //         auth()->id(),
        //         'Order cancelled',
        //         $message,
        //         'Order cancelled',
        //         $order->id
        //     );

        //     $this->sendSms($order->provider->phone_number,$message);
        // }

        $ccEmails = [];
        if ($order->assigned_to) {
            $user = User::find($order->assigned_to);
            $message = "Order # ".$order->order_id." has been cancelled by ".auth()->user()->first_name." ".auth()->user()->last_name." (Customer)";
            if ($user) {
                $ccEmails[] = $user->email;
            }
            // sendNotification(
            //     $order->assigned_to,
            //     auth()->id(),
            //     'Order cancelled',
            //     $message,
            //     'Order cancelled',
            //     $order->id
            // );


            // $this->sendSms($order->provider->phone_number, $message);
        }
    
        // Send cancellation email with CC
        Mail::to('mowingandplowingcle@gmail.com')
            ->cc(array_merge($ccEmails, ['jaspreet.mangoit@gmail.com'])) // Adding admin@example.com to CC
            ->send(new OrderCancelledMail($order));

        return redirect()->back()->with('success','Order has been cancelled');
    }

    public function upcomingJobsProposals($id)
    {
        $this->pageTitle = 'Proposals';
        $this->breadCrumbs = ['Job History','Upcoming Jobs','Proposals'];
        $this->providers = Proposal::whereOrderId($id)->get();
        $this->order = Order::whereId($id)->whereUserId(auth()->id())->first();
        $this->favorites = FavoriteProvider::whereUserId(auth()->id())->pluck('provider_id')->toArray();
        return view('client.providers.index',$this->data);
    }

    public function acceptProposal($id)
    {
        $proposal = Proposal::find($id);
        $order = Order::whereId($proposal->order_id)->whereUserId(auth()->id())->first();
        $order->assigned_to = $proposal->provider_id;
        $order->provider_assigned_date  = Carbon::now();
        $order->status  = 2;
        $order->save();

        Proposal::whereOrderId($proposal->order_id)->delete();

        $message = 'Your proposal has been accepted for order # '.$order->order_id;

        sendNotification(
            $order->assigned_to,
            auth()->id(),
            'Proposal accepted',
            $message,
            'Proposal accepted',
            $order->id
        );

        $this->sendSms($proposal->user->phone_number,$message);

        return redirect(route('job-history.jobs','ongoing-jobs'))->with('success',"Proposal has been accepted");
    }

    public function cancelProposal($id)
    {
        $proposal = Proposal::find($id);
        $proposal->delete();

        sendNotification(
            $proposal->provider_id,
            auth()->id(),
            'Proposal cancelled',
            "Your proposal for order # " . $proposal->order->order_id . " has been cancelled by " . auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)",
            'Proposal cancelled',
            $proposal->order->id
        );

        $this->sendSms($proposal->user->phone_number,"Your proposal for order # " . $proposal->order->order_id . " has been cancelled by " . auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)");
        return redirect(route('job-history.upcoming-jobs.proposals',$proposal->order_id))->with('success',"Proposal has been canceled");
    }

    public function toggleFavoriteProvider($id)
    {
        $favoriteProvider = FavoriteProvider::whereProviderId($id)->first();
        $user = User::find($id);
        if(!$favoriteProvider){
            FavoriteProvider::create(['user_id'=>auth()->id(),'provider_id'=>$id]);
            $message = 'Favorite provider has been added';
            sendNotification(
                $favoriteProvider,
                auth()->user()->id,
                'Favourite Provider',
                "Your are now favourite provider of customer ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)",
                'Favourite Provider',
                null
            );
            $this->sendSms($user->phone_number,"Your are now favourite provider of customer ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)");
        } else {
            $favoriteProvider->delete();
            $message = 'Favorite provider has been removed';
            sendNotification(
                $favoriteProvider,
                auth()->user()->id,
                'Removed Favourite Provider',
                "Your have been removed from favorite provider by ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)",
                'Removed Favourite Provider',
                null
            );
            $this->sendSms($user->phone_number,"Your are now favourite provider of customer ". auth()->user()->first_name . " " . auth()->user()->last_name . " (Customer)");
        }

        return redirect()->back()->with('success',$message);
    }

    public function ongoingJobsDetails($id)
    {
        $this->job = Order::whereId($id)->whereUserId(auth()->id())->first();
        if(!$this->job) return back()->with('error','You do not have access to this order');
        return view('client.job-history.ongoing-jobs-details',$this->data);
    }

    public function providersChat($order_id)
    {
        $this->order = Order::whereId($order_id)->whereUserId(auth()->id())->first();
        if(!$this->order) return back()->with('error','You do not have access to this chat');
        $this->messages = ChatMessage::whereOrderId($order_id)->get();
        return view('client.job-history.providers-chat',$this->data);
    }

    public function sendMessage(Request $req)
    {
        $order = Order::find($req->order_id);
        try {
            $validator = Validator::make($req->all(), [
                'message' => 'required',
                'order_id' => 'required',
                'provider_id' => 'required',
                'order_no' => 'required',
            ]);
            if ($validator->fails()) {
                return parent::resp(false, 'Validation errors', null, $validator->errors());
            }

            $user = User::find( $req->user_id);
            
            $this->sendSms($user->phone_number, "New message on order # ".$req->order_no);
            $this->sendChatMessage($req);

            sendNotification(
                $req->provider_id,
                auth()->id(),
                "New message on order # " . $req->order_no,
                $req->message,
                'Chat',
                $order->id
            );

            return parent::resp(true, 'Message sent');
        } catch (\Throwable $th) {
            return parent::resp(false, "On the server, an unforeseen event occurred" . $th->getMessage());
        }
    }

    public function rateTheJob($id)
    {
        return view('client.job-history.ratings');
    }

    public function saveTheJobRatings(Request $req,$id)
    {
        try {
            $validator = Validator::make($req->all(), [
                'response_time_rating' => 'required',
                'quality_rating' => 'required',
            ]);
            if ($validator->fails()) {
                return parent::resp(false, 'Validation errors', null, $validator->errors());
            }

            $order = Order::whereUserId(auth()->id())->whereId($id)->first();

            Rating::updateOrCreate(['order_id' => $order->id,],[
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'provider_id' => $order->assigned_to,
                'comment' => $req->comment ?? null,
                'response_time_rating' => $req->response_time_rating,
                'quality_rating' => $req->quality_rating,
            ]);
            sendNotification(
                $order->assigned_to,
                auth()->id(),
                'Customer rated your job',
                auth()->user()->first_name . " " . auth()->user()->last_name . " has rated your job for order # " . $order->order_id,
                'Customer rated your job',
                $order->id
            );
            
            $user = User::find($order->assigned_to);
            $this->sendSms($user->phone_number,auth()->user()->first_name . " " . auth()->user()->last_name . " has rated your job for order # " . $order->order_id);
            Session::flash('success','Job has been rated');

            return parent::resp(true, 'Job has been rated');
        } catch (\Throwable $th) {
            return parent::resp(false,"Something unexpected happened on server. ".$th->getMessage());
        }
    }

    public function markJobAsCompleted($id)
    {
        $order = Order::whereId($id)->whereUserId(auth()->id())->first();
        if(!$order) return redirect()->back()->with('error','Order does not exist');

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
                // dd($th);
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
        // $this->sendSms($user->phone_number,auth()->user()->first_name . " " . auth()->user()->last_name . " marked order as completed # " . $order->order_id);

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

        return redirect()->back()->with('success', 'Order marked as completed');
    }
}
