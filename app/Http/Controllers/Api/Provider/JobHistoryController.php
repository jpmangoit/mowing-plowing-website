<?php

namespace App\Http\Controllers\Api\Provider;

use App\Events\MessageSent;
use App\Events\ProviderLiveLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\ChatMessage;
use App\Models\LawnHeight;
use App\Models\LawnSize;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderImageByProvider;
use App\Models\Property;
use App\Models\Proposal;
use App\Models\ProviderDetail;
use App\Models\Question;
use App\Models\Rating;
use App\Models\RecurringHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Traits\ChatMessages;
use App\Traits\OrderTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Mail\JobCompletedCustomerMail;
use App\Mail\JobCompletedProviderMail;
use Illuminate\Support\Facades\Mail;

class JobHistoryController extends ApiBaseController
{
    use ChatMessages,OrderTrait;

    public function jobs(Request $req)
    {
        try {
            if(auth()->user()->status != 1){
                return parent::resp(false, "Your account is not approved. Kindly wait for the admin to approve it...");
            }

            $today = Carbon::now()->format('Y-m-d');
            $week = Carbon::now()->subDays(7);
            $month = Carbon::now()->subMonths(1);
            $provider_id = auth()->user()->id;
            $query = Order::query();
            if(auth()->user()->providerDetails->industry_type != 3) $query->whereCategoryId(auth()->user()->providerDetails->industry_type);

            if ($req->type == 'available') {
                $this->jobs = $query->withinRadius()->withOptions()->with(['proposals' => function($query) use ($provider_id){
                    $query->whereProviderId($provider_id);
                }])->whereAssignedTo(Null)->wherePaymentStatus(2)->whereStatus(1)->latest()->get();
            } elseif ($req->type == 'active') {
                $this->today_jobs = Order::providerChecks()->withOptions()->whereStatus(2)->whereDate('date', $today)->latest()->get();
                $this->week_jobs = Order::providerChecks()->withOptions()->whereStatus(2)->whereDate('date','>=', $week)->latest()->get();
                $this->month_jobs = Order::providerChecks()->withOptions()->whereStatus(2)->whereDate('date','>=', $month)->latest()->get();
            } elseif ($req->type == 'complete') {
                $this->jobs = Order::providerChecks()->withOptions()->whereStatus(3)->latest()->get();
            } else {
                $this->jobs = Order::providerChecks()->withOptions()->whereIn('status',[2,3,4])->latest()->get();
            }

            return parent::resp(true,  $req->type == 'available' ? 'Here are available jobs.' : ($req->type == 'active' ? 'Here are active jobs.' : ($req->type == 'complete' ? 'Here are complete jobs.' : 'Here are total jobs.')) ,$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function sendProposal(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), ['order_id' => 'required']);
            if ($validator->fails()) {return parent::resp(false, 'Validation errors', null, $validator->errors());}

            $order = Order::whereId($req->order_id)->whereAssignedTo(null)->whereStatus(1)->first();

            if($order->category_id == 1){
            	$currentTimestamp = Carbon::now();

		// Add 2 minutes to the current timestamp
		$newTimestamp = $currentTimestamp->addMinutes(2);
            	Order::whereId($req->order_id)->update(['twomins' => $newTimestamp]);
                $data = $req->all();
                $data['provider_id'] = auth()->user()->id;
                Proposal::create($data);

                $message = auth()->user()->first_name." ".auth()->user()->last_name." has sent you proposal for order # ".$order->order_id;

                sendNotification(
                    $order->user_id,
                    auth()->id(),
                    'New job proposal',
                    $message,
                    'New job proposal',
                    $order->id
                );

                $this->sendSms($order->user->phone_number,$message);
            } else {
                $order->assigned_to = auth()->id();
                $order->provider_assigned_date = Carbon::now();
                $order->status = 2;
                $order->save();

                $message = auth()->user()->first_name." ".auth()->user()->last_name." has accepted your order # ".$order->order_id;

                sendNotification(
                    $order->user_id,
                    auth()->id(),
                    'Provider is assigned',
                    $message,
                    'Provider is assigned',
                    $order->id
                );

                $this->sendSms($order->user->phone_number,$message);
            }

            return parent::resp(true, 'Your proposal has been sent successfully.');
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function completeActiveJob (Request $req, $type, $id)
    {
        try {
            if ($type == 'on-my-way') {

                $this->onMyWayJob = Order::with('images')->find($id);


                if ($this->onMyWayJob->category_id == 1) {
                    $this->questions = Question::whereCategory(1)->get() ;
                } else {
                    $this->questions = Question::whereCategory(2)->get() ;
                }
                $this->onMyWayJob->update(['on_the_way' => 1 , 'on_the_way_date' => now()]);
                $message = 'I am on my way for this job #'.$this->onMyWayJob->order_id;

                sendNotification(
                    $this->onMyWayJob->user_id,
                    auth()->id(),
                    'On my Way',
                    $message,
                    'On my Way',
                    $this->onMyWayJob->id
                );
                $this->sendSms($this->onMyWayJob->user->phone_number, $message);
            } elseif ($type == 'reached-and-started-job') {

                $this->startJob = Order::with('images')->find($id);

                if ($req->file('images')) {
                    $images = $req->file('images');
                    foreach ($images as $image) {
                        $foldername = '/uploads/order-images-by-provider/'.$this->startJob->order_id.'/before-service/';
                        $filename = time().'-'.rand(00000,99999).'.'.$image->extension();
                        $image->move(public_path().$foldername,$filename);
                        OrderImageByProvider::create(['order_id' => $id, 'image' => $foldername.$filename, 'type' => 'before']);
                    }
                }

                $this->startJob->update(['at_location_and_started_job' => 1,'at_location_and_started_job_date' => now()]);
                $message = 'I have reached at your location and started my job #'.$this->startJob->order_id;
                sendNotification(
                    $this->startJob->user_id,
                    auth()->id(),
                    'Reached and started job',
                    $message,
                    'Reached and started job',
                    $this->startJob->id
                );
                $this->sendSms($this->startJob->user->phone_number, $message);
            } elseif ($type == 'job-completed') {

                $validator = Validator::make($req->all(), ['checked_questions' => 'required']);
                if ($validator->fails()) {return parent::resp(false, 'Validation errors', null, $validator->errors());}

                $this->completedJob = Order::with('images')->find($id);

                if ($req->file('images')) {
                    $images = $req->file('images');
                    foreach ($images as $image) {
                        $foldername = '/uploads/order-images-by-provider/'.$this->completedJob->order_id.'/after-service/';
                        $filename = time().'-'.rand(00000,99999).'.'.$image->extension();
                        $image->move(public_path().$foldername,$filename);
                        OrderImageByProvider::create(['order_id' => $id, 'image' => $foldername.$filename, 'type' => 'after']);
                    }
                }
                $this->completedJob->update(['finished_job' => 1,'finished_job_date' => now(),'checked_questions' => 1, 'status' => 3]);
                $message = 'Job has been completed successfully.';

                $smsMessage = 'Order # '.$this->completedJob->order_id.' has been completed by '.auth()->user()->first_name.' '.auth()->user()->last_name;

                $customer = User::find($completedJob->user_id);
                $serviceType = $completedJob->category_id == 1 ? 'lawn mowing' : 'snow removal';
                sendNotification(
                    $this->completedJob->user_id,
                    auth()->id(),
                    'Order # '.$this->completedJob->order_id.' is completed',
                    $smsMessage,
                    'Order Completed',
                    $this->completedJob->id
                );

                try {
                    Mail::to($customer->email)
                    ->send(new JobCompletedCustomerMail(
                        $customer->first_name,
                        $serviceType
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send customer completion email: ' . $e->getMessage());
                }

                // $this->sendSms($this->completedJob->user->phone_number, $smsMessage);

            } else {
                return parent::resp(false, "Please provide type. Its compulsary." );
            }

            return parent::resp(true, $message ,$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function providersChat($order_id)
    {
        try {
            $order = Order::whereId($order_id)->whereAssignedTo(auth()->id())->first();
            if(!$order) return parent::resp(false, "You do not have access to this chat");
            $this->order_id = $order->order_id;
            $this->customer = $order->user()->select(['id','first_name','last_name','image'])->first();
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

            return parent::resp(true, 'Message sent');
        } catch (\Throwable $th) {
            return parent::resp(false, "On the server, an unforeseen event occurred" . $th->getMessage());
        }
    }

    public function updateProviderLastKnownLocation(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'order_id' => 'required',
                'lat' => 'required',
                'lng' => 'required',
            ]);
            if ($validator->fails()) {return parent::resp(false, 'Validation errors', null, $validator->errors());}

            ProviderDetail::whereProviderId(auth()->id())->update([
                'last_known_lat' => $req->lat,
                'last_known_lng' => $req->lng,
            ]);

            $location = [
                'order_id' => $req->order_id,
                'lat' => $req->lat,
                'lng' => $req->lng,
            ];

            broadcast(new ProviderLiveLocationUpdated($location))->toOthers();

            return parent::resp(true,'Location updated.');

        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function availableJobsAndRatings()
    {
        try {
            if(auth()->user()->status != 1){
                return parent::resp(false, "Your account is not approved. Kindly wait for the admin to approve it...");
            }

            $this->totalJobs = Order::whereAssignedTo(auth()->user()->id)->wherePaymentStatus(2)->whereIn('status',[2,3,4])->count();
            $this->totalEarnings = round(Transaction::whereProviderId(auth()->user()->id)->whereType(2)->sum('amount'),2);
            $this->unreadNotificationsCount = Notification::whereReceiverId(auth()->id())->whereStatus("0")->count();
            $this->totalRating = 0;
            $this->responseOnTime = 0;
            $this->totalScore = 0;
            $this->completedJobs = 0;
            $this->completeJobsPerc = 0;
            $this->cancelJobsPerc = 0;
            $this->level = getProviderLevel();
            $totalReviews = Rating::whereProviderId(auth()->user()->id)->count();

            if ($totalReviews != 0) {
                $sumOfReviews = Rating::whereProviderId(auth()->user()->id)->sum('quality_rating');
                $this->totalRating = round($sumOfReviews / $totalReviews,1);

                $sumOfResponseOnTime = Rating::whereProviderId(auth()->user()->id)->sum('response_time_rating');
                $this->responseOnTime = round($sumOfResponseOnTime / $totalReviews,1);

                $this->responseOnTimePerc = round(($this->responseOnTime / 5) * 100,2);
                $this->totalRatingPerc = round(($this->totalRating / 5) * 100,2);
                $this->totalScore = round(($this->responseOnTimePerc + $this->totalRatingPerc) / 2,2);
            }

            if ($this->totalJobs != 0) {
                $this->completedJobs = Order::whereAssignedTo(auth()->user()->id)->wherePaymentStatus(2)->whereStatus(3)->latest()->count();
                $this->completeJobsPerc = round(($this->completedJobs / $this->totalJobs) * 100,2);

                $canceledJobs = Order::whereAssignedTo(auth()->user()->id)->wherePaymentStatus(2)->whereStatus(4)->latest()->count();
                $this->cancelJobsPerc = round(($canceledJobs / $this->totalJobs) * 100,2);
            }

            $provider_id = auth()->user()->id;
            $query = Order::query();

            if(auth()->user()->providerDetails->industry_type != 3) $query->whereCategoryId(auth()->user()->providerDetails->industry_type);
            $this->jobs = $query->with('images','property','period')->with(['proposals' => function($query) use ($provider_id){
                $query->whereProviderId($provider_id);
            }])->whereAssignedTo(Null)->wherePaymentStatus(2)->whereStatus(1)->latest()->take(2)->get();
            return parent::resp(true,'Here are available jobs.' ,$this->data);
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function orderDetail($id)
    {
        $provider_id = auth()->user()->id;
        $order_id = $id; // Replace 'order_id' with the actual parameter name

        $query = Order::query();

        // Filter based on the order ID
        $query->where('id', $order_id);
        if (auth()->user()->providerDetails->industry_type != 3) $query->whereCategoryId(auth()->user()->providerDetails->industry_type);
        $this->jobs = $query->with('images', 'property', 'period')->with(['proposals' => function ($query) use ($order_id) {
            $query->whereOrderId($order_id);
        }])->whereAssignedTo(null)->wherePaymentStatus(2)->whereStatus(1)->latest()->take(2)->get();

        $extractedJobs = $this->jobs->map(function ($job) {
            $periodAddress = $job->property ? $job->property->address : null;
            return [
                'date' => $job->date,
                'grand_total' => $job->grand_total,
                'id' => $job->id,
                'period' => $job->period,
                'property' => $periodAddress,
                'service_for' => $job->service_for,
                'proposals' => $job->proposals,
                'lat' => $job->lat,
                'lng' => $job->lng,
            ];
        });

        return parent::resp(true, 'Here is the job with the specified order ID.', ['jobs' => $extractedJobs]);
    }

    public function getJobDetail($id, $type)
    {
        $provider_id = auth()->user()->id;
        $query = Order::where('id', $id);
        $data=[];
        if (auth()->user()->providerDetails->industry_type != 3) {
            $query->whereCategoryId(auth()->user()->providerDetails->industry_type);
        }

        if ($type == 'available') {
            $this->jobs = $query->with('images', 'property', 'period')->with(['proposals' => function ($query) use ($id) {
                $query->whereOrderId($id);
            }])->whereAssignedTo(null)->wherePaymentStatus(2)->whereStatus(1)->latest()->take(2)->get();
            $data = $this->fetchDataForGetJobDetail($this->jobs);
        } elseif ($type == 'active') {
            $this->jobs = $query->with('images', 'property', 'period')->with(['proposals' => function ($query) use ($id) {
                $query->whereOrderId($id);
            }])->withOptions()
                ->whereStatus(2)
                ->latest()
                ->get();
                $data = $this->fetchDataForGetJobDetail($this->jobs);
        } elseif ($type == 'completed') {
            $this->jobs = $query->with('images', 'property', 'period')->with(['proposals' => function ($query) use ($id) {
                $query->whereOrderId($id);
            }])->withOptions()
                ->whereStatus(3)
                ->latest()
                ->get();

                $data = $this->fetchDataForGetJobDetail($this->jobs);
        } else {
            $this->jobs = Order::providerChecks()
                ->withOptions()
                ->whereIn('status', [2, 3, 4])
                ->latest()
                ->get();
                $data = $this->fetchDataForGetJobDetail($this->jobs);
        }

        // Assuming the parent class has a method named 'resp()' that generates a response.
        return parent::resp(
            true,
            $type == 'available' ? 'Here are available jobs.' : ($type == 'active' ? 'Here are active jobs.' : ($type == 'complete' ? 'Here are complete jobs.' : 'Here are total jobs.')),
            ['jobs' => $data] // Return the filtered data
        );
    }

    public function fetchDataForGetJobDetail($jobs){
        $data = [];
        foreach ($jobs as $job) {
            $periodAddress = $job->property ? $job->property->address : null;
            $data[] = [
                'id' => $job->id,
                'date' => $job->date,
                'grand_total' => $job->grand_total,
                'service_for' => $job->service_for,
                'period' => $job->period,
                'proposals' =>  $job->proposals,
                'lat' => $job->property->lat,
                'lng' => $job->property->lng,
                'property' => $periodAddress,
            ];
        }
        return $data;
    }
}
