<?php

namespace App\Http\Controllers\Client;

use App\Models\BannerScript;
use App\Http\Controllers\Client\LawnMowingController;
use App\Http\Requests\Signup;
use App\Mail\EmailVerification;
use App\Mail\RegistrationMail;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\OrderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\City;
use App\Models\CityList;
use App\Models\Order;
use App\Models\Property;
use Carbon\Carbon;
use DB;
use App\Mail\PasswordReset;
use Illuminate\Support\Facades\Password;

class AuthenticationController extends ClientBaseController
{
    use OrderTrait;

    public function signupIndex(Request $req)
    {
        return view('client.auth.signup', $this->data);
        // return view('client.auth.registration');
    }

    public function banner()
    {
        $banner = BannerScript::first();
        return $banner;
    }

    public function homePage()
    {
        $sekected_city = City::all()->pluck('name');
        $city_list = CityList::whereIn('ID', $sekected_city)->with('state')->get();
        $banner = $this->banner(); function getCategoryText($categoryId)
        {
            switch ($categoryId) {
                case 1:
                    return "Lawn Mowing";
                case 2:
                    return "Snow Plowing";
                default:
                    return "Unknown";
            }
        }

        // Convert status to text
        function getStatusText($status)
        {
            switch ($status) {
                case 1:
                    return "Pending";
                case 2:
                    return "Accepted";
                case 3:
                    return "Completed";
                case 4:
                    return "Canceled";
                default:
                    return "Unknown";
            }
        }

        $orders = Order::latest()
            ->take(10)
            ->get(['category_id', 'status', 'lat', 'lng', 'property_id', 'assigned_to', 'on_the_way', 'at_location_and_started_job', 'finished_job'])
            ->filter(function ($order) {
                return is_numeric($order->lat) && is_numeric($order->lng);
            });

        foreach ($orders as $order) {
            // Add category and status text
            $order->category_text = getCategoryText($order->category_id);

            // Dynamic status logic
            if ($order->finished_job == 1) {
                $order->dynamic_status = 'Completed';
            } elseif ($order->at_location_and_started_job == 1) {
                $order->dynamic_status = 'Reached & Started Job';
            } elseif ($order->on_the_way == 1) {
                $order->dynamic_status = 'On My Way';
            } else {
                $order->dynamic_status = getStatusText($order->status); // Fallback to status column
            }

            // Fetch the full address from the properties table
            $fullAddress = Property::where('id', $order->property_id)->value('address');

            if ($fullAddress) {
                // Extract city and state from the address
                $addressParts = explode(',', $fullAddress);
                if (count($addressParts) >= 3) {
                    $city = trim($addressParts[count($addressParts) - 3]); // City
                    $state = trim($addressParts[count($addressParts) - 2]); // State
                    $order->address = "$city, $state";
                } else {
                    $order->address = $fullAddress; // Fallback to full address if city/state can't be extracted
                }
            } else {
                $order->address = 'Address not available';
            }

            // Fetch the assigned user's name
            $user = User::find($order->assigned_to);
            $order->assigned_to_name = $user ? $user->first_name . ' ' . $user->last_name : 'Not Assigned';
        }

        $orderImages = DB::table('order_image_by_providers as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id') // Join with the orders table
            ->join('properties as p', 'o.property_id', '=', 'p.id') // Join with the properties table
            ->join('users as u', 'o.assigned_to', '=', 'u.id') // Join with the users table
            ->selectRaw('oi.order_id,
                        MAX(CASE WHEN oi.type = "before" THEN oi.image END) AS before_image, 
                        MAX(CASE WHEN oi.type = "after" THEN oi.image END) AS after_image,
                        p.address AS property_location,
                        CONCAT(u.first_name, " ", u.last_name) AS provider_name,
                        o.finished_job_date ')
            ->whereIn('oi.type', ['before', 'after'])
            ->groupBy('oi.order_id', 'p.address', 'u.first_name', 'u.last_name', 'o.finished_job_date')
            ->orderByRaw('MAX(oi.created_at) DESC') // Sort by the latest created_at
            ->limit(4) // Get the latest 4 orders
            ->get();

        $orderImages->transform(function ($item) {
            // Split the address by commas
            $addressParts = explode(',', $item->property_location);

            // Check if there are at least three parts (Street Address, City, State, Country)
            if (count($addressParts) >= 3) {
                $city = trim($addressParts[count($addressParts) - 3]);  // City (third to last part)
                $state = trim($addressParts[count($addressParts) - 2]);  // State (second to last part)

                // We can format the property location as City, State
                $item->property_location = "$city, $state";
            } else {
                // If we don't have enough parts, retain the original address (it might be missing data)
                $item->property_location = trim($item->property_location);
            }

            // Format the finished job date
            $item->finished_job_date = Carbon::parse($item->finished_job_date)->format('m/d/Y');

            return $item;
        });

        // Pass the filtered orders to the view
        return view('homepage', compact('city_list', 'banner', 'orders', 'orderImages'));
    }

    public function signup(Request $req)
    {
        try {

            $validator = Validator::make($req->all(), [
                'email' => 'required|email|unique:users,email',
                'phone_number' => 'required|unique:users|max:10',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $req['phone_number'] = "+1" . $req->phone_number;

            $user = User::create([
                'email' => $req->email,
                'phone_number' => $req->phone_number,
                'type' => 'customer',
            ]);
            $user->save();

            return redirect(route('registration'))->with(['email' => $req->email, 'phone_number' => $req->phone_number]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function verifyEmailIndex()
    {
        if (!session('email')) return redirect(route('web.login'));
        return view('client.auth.verify-email', ['email' => session('email')]);
    }

    public function verifyEmail(Request $req)
    {
        try {
            $data = $req->except('_token');
            $otp = '';
            foreach ($data['otp'] as $key => $value) {
                $otp .= $value;
            };
            $user = User::where('email', $req->email)->first();

            if ($otp != $user->otp) {
                return redirect()->back()->with(['email' => $req->email, 'error' => "Your verification code is not correct"]);
            } else {
                $user->email_verified_at = now();
                $otp = rand(111111, 999999);
                $user->otp = $otp;
                $user->save();

                $this->sendSms($user->phone_number, 'This is your 6 digit code ' . $otp);

                return redirect(route('registration'))->with(['success' => 'Phone number has been verified. Now kindly create your account', 'email' => $user->email]);
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function verifyPhoneNumberIndex()
    {
        if (!session('phone_number') || !session('email')) return redirect(route('signup'));
        return view('client.auth.verify-phone-number', ['phone_number' => session('phone_number'), 'email' => session('email')]);
    }

    public function verifyPhoneNumber(Request $req)
    {
        $data = $req->except('_token');
        $otp = '';
        foreach ($data['otp'] as $key => $value) {
            $otp .= $value;
        };
        $user = User::where('email', $req->email)->first();

        if ($otp != $user->otp) {
            return redirect()->back()->with(['email' => $user->email, 'phone_number' => $user->phone_number, 'error' => "Your verification code is not correct"]);
        } else {
            $user->phone_number_verified_at = now();
            $user->otp = null;
            $user->save();
            return redirect(route('registration'))->with(['success' => 'Phone number has been verified. Now kindly create your account', 'email' => $user->email]);
        }
    }

    public function registrationIndex()
    {
        if (session('email')) return view('client.auth.registration', ['email' => session('email')]);
        return redirect(route('signup'));
    }

    public function registration(Request $req)
    {
        $user_ip = $req->user_ip;
        $property_id = $req->property_id;
        $order_id = $req->order_id;
        $summaryRegister = $req->input('summaryRegister');
        try {
            $validatorRules = [
                'email' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'password' => 'required|confirmed',
                'image' => 'mimes:jpg,jpeg,png',
                'address' => 'required',
                'lat' => 'required',
                'lng' => 'required',
            ];

            if (!empty($summaryRegister)) {
                $validatorRules['email'] .= '|email|unique:users,email';
                $validatorRules['phone_number'] = 'required|unique:users';
            }

            $validator = Validator::make($req->all(), $validatorRules);


            if ($validator->fails()) {
                if (!empty($summaryRegister)) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors(),
                    ]);
                } else {
                    return back()->withInput()->with(['email' => $req->email, 'errors' => $validator->errors()]);
                }
            }

            if (!empty($summaryRegister)) {
                $user = User::create([
                    'email' => $req->email,
                    'phone_number' => $req->phone_number,
                    'type' => 'customer',
                ]);
            }


            $user = User::where('email', $req->email)->first();
            if (!$user) {
                if (empty($summaryRegister)) {
                    return back()->with('error', 'Account does not exist with this email');
                }
            }


            $user->first_name = $req->first_name;
            $user->last_name = $req->last_name;
            $user->password = Hash::make($req->password);
            $user->address = $req->address;
            $user->lat = $req->lat;
            $user->lng = $req->lng;
            $user->referral_link = "/referral-link/" . Str::random(16);
            $user->status = 1;

            if ($req->hasFile('image')) {
                $foldername = '/uploads/clients/profile-pics/';
                $filename = time() . '-' . rand(0000000, 9999999) . '.' . $req->file('image')->extension();
                $req->file('image')->move(public_path() . $foldername, $filename);
                $user->image = $foldername . $filename;
            }

            $user->customer_id = $this->stripe->customers->create([
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ])->id;

            $user->save();

            Wallet::create(['user_id' => $user->id]);

            if ($req->input('referral_link')) {

                $referred_by = User::whereReferralLink(substr($req->input('referral_link'), -31))->first();
                if (!$referred_by) {
                    return redirect()->back()->with('error', "This referral link does not exist.");
                }

                $user->referred_by = $referred_by->id;
                $user->save();

                Wallet::whereUserId($referred_by->id)->first()->increment('amount', settings('referral_bonus'));

                sendNotification(
                    $referred_by->id,
                    $user->id,
                    'Congratulations on bonus',
                    "You have been awarded a bonus for referring a friend @ Mowing and Plowing",
                    'Congratulations on bonus',
                    null
                );

                $this->sendSms($referred_by->phone_number, "You have been awarded a bonus for referring a friend");
            }

            Auth::login($user);
            Mail::to($user->email)->send(new RegistrationMail($user->name, $user->email, $req->password, "https://staging.mowingandplowing.com/login"));
            // Mail::to($user->email)->send(new RegistrationMail($user->first_name, $user->email,$req->password, "https://mowingandplowing.com/login"));
            $lawnMowingController = new LawnMowingController();
            $lawnMowingController->updateUserDetail($user_ip, $property_id, $user->id, $order_id);

            if (!empty($summaryRegister)) {
                sendNotification(
                    $user->id,
                    $user->id,
                    'Welcome',
                    "Welcome to @ Mowing and Plowing",
                    'Welcome',
                    null
                );
                $this->sendSms($user->phone_number, 'Welcome to ');
                return response()->json([
                    'success' => true,
                    'message' => "Registered Successfully",
                ]);
            } else {
                sendNotification(
                    $user->id,
                    $user->id,
                    'Welcome',
                    "Welcome to @ Mowing and Plowing",
                    'Welcome',
                    null
                );
                $this->sendSms($user->phone_number, 'Welcome to ');
                return redirect()->route('dashboard')->with('success', "Welcome to Mowing and Plowing");
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "Something unexpected happened on the server. " . $th->getMessage());
        }
    }


    public function resendCode(Request $req)
    {
        try {
            if ($req->resend_for === 'email') {
                $user = User::where('email', $req->email)->first();
            } elseif ($req->resend_for === 'admin-email') {
                $user = Admin::where('email', $req->email)->first();
            } elseif ($req->resend_for === 'phone_number') {
                $user = User::where('phone_number', $req->phone_number)->first();
            }
            $otp = rand(111111, 999999);
            $user->otp = $otp;
            $user->save();

            if ($req->resend_for === 'email' || $req->resend_for === 'admin-email') {
                Mail::to($req->email)->send(new EmailVerification($user->first_name, $otp));
            } elseif ($req->resend_for === 'phone_number') {
                $this->sendSms($user->phone_number, 'This is your 6 digit code ' . $otp);
            }

            return response()->json(['success' => true, 'message' => 'New verification code has been sent']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Something unexpected happened on server. ' . $th->getMessage()]);
        }
    }

    // Login Index
    public function loginIndex(Request $req)
    {
        if (Auth::check()) return redirect(route('dashboard'));

        return view('client.auth.login');
    }

    // Login
    public function login(Request $request)
    {
        $summaryLogin = $request->input('summaryLogin');
        $user_ip = $request->user_ip;
        $property_id = $request->property_id;
        $order_id = $request->order_id;

        if (!Auth::attempt(
            ['email' => $request->email, 'password' => $request->password, 'status' => 1, 'type' => 'customer'],
            $request->remember_me == 'on' ? true : false
        )) {

            if (!empty($summaryLogin)) {
                return json_encode(array('success' => false, 'message' => "Email or password is not correct or your account is not active"));
            } else {
                return redirect()->back()->with('error', 'Email or password is not correct or your account is not active');
            }
        }

        $user = Auth::user();
        $lawnMowingController = new LawnMowingController();
        $lawnMowingController->updateUserDetail($user_ip, $property_id, $user->id, $order_id);

        User::find(auth()->id())->update([
            'last_login_device' => 'web',
            'default_password' => null,
        ]);

        if (!empty($summaryLogin)) {
            $this->sendSms($user->phone_number, 'Welcome to ');
            return json_encode(array('success' => true, 'message' => "Login Successfully"));
        } else {
            $this->sendSms($user->phone_number, 'Welcome to ');
            return redirect(route('dashboard'));
        }
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect(route('web.login'));
    }

    public function editProfile()
    {
        return view('client.auth.edit-profile');
    }

    public function updateProfile(Request $req)
    {

        $req->validate([
            'email' => 'required|max:255',
            'phone_number' => 'required|max:255',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'address' => 'required',
        ]);

        if ($req->hasFile('image')) {
            $image = $req->file('image');
            $foldername = '/uploads/customer/profile_pic/';
            $filename = time() . '-' . rand(00000, 99999) . '.' . $image->extension();
            $image->move(public_path() . $foldername, $filename);
            User::where('email', $req->email)->update(['image' => $foldername . $filename]);
        }


        $customer_data = [
            'first_name' => $req->first_name,
            'last_name' => $req->last_name,
            'address' => $req->address,
        ];
        
                if ($req->password) {
            $req->validate([
                'password' => 'confirmed',
            ]);
            $customer_data['password'] = hash::make($req->password);
        }
        
        User::where('email', $req->email)->update($customer_data);
        $user = User::where('email', $req->email)->first();
        sendNotification(
            $user->id,
            $user->id,
            'Profile Details Upates',
            "Your Profile Details are Updated",
            'Profile Details Upates',
            null
        );
        $this->sendSms($user->phone_number, 'Your Profile Details are Updated');
        return back()->with('success', 'Profile updated');
    }

    public function resetPasswordEmail(Request $req)
    {
        return view('client.auth.reset-password-email');
    }

    // public function verifyResetPasswordEmailIndex(Request $req)
    // {
    //     $email = $req->email;
    //     $user = User::whereEmail($req->email)->first();

        
    //     if (!$user->otp) {
    //         $otp = rand(111111, 999999);
    //         $user->otp = $otp;
    //         $user->save();
            
    //         Mail::to($req->email)->send(new EmailVerification('Sir/Madam', $otp));
    //     }

    //     return view('client.auth.verify-reset-password-email', compact('email'));
    // }

    
    public function verifyResetPasswordEmailIndex(Request $req)
    {
        $email = $req->email;
        $user = User::whereEmail($email)->first();
    
        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }
    
        // Generate a password reset token
        $token = Password::broker()->createToken($user);
        $toke = DB::table('password_resets')->where('email', $email)->first();
        // dd($toke->token);
    
        $encodedToken = urlencode($token); // Encode token for URL

        // $resetLink = url(route('forget-password.password.reset', [
        //     'token' => $encodedToken, 
        //     'email' => $email
        // ], false));

        $resetLink = url(route('forget-password.password.reset', [], false) . "?token=$encodedToken&email=$email");

        Mail::to($email)->send(new PasswordReset($user->first_name ?? 'User', $resetLink));
    
        return redirect()->back()->with('success', 'Password reset link has been sent to your email.');
    }
    
    // public function verifyResetPasswordEmailIndex(Request $req)
    // {
    //     $email = $req->email;
    //     $user = User::whereEmail($email)->first();
    
    //     if (!$user) {
    //         return redirect()->back()->with('error', 'User not found.');
    //     }
    
    //     // Generate OTP only if it does not exist or needs to be refreshed
    //     if (!$user->otp || now()->diffInMinutes($user->updated_at) > 1) {  
    //         $otp = rand(111111, 999999);
    //         $user->otp = $otp;
    //         $user->save();
    
    //         Mail::to($email)->send(new PasswordReset($user->first_name ?? 'Sir/Madam', $otp));
    //     }
    
    //     return view('client.auth.verify-reset-password-email', compact('email'));
    // }


    public function verifyResetPasswordEmail(Request $req)
    {
        try {
            $data = $req->except('_token');
            $otp = '';
            foreach ($data['otp'] as $key => $value) {
                $otp .= $value;
            };
            $user = User::where('email', $req->email)->first();

            if ($otp != $user->otp) {
                return redirect()->back()->with(['error' => "Your verification code is not correct"]);
            } else {
                $user->otp = null;
                $user->save();
                return redirect(route('forget-password.reset-password.index'))->with('email', $req->email);
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    // public function resetPasswordIndex(Request $req)
    // {
    //     if (!session('email')) return redirect(route('web.login'));
    //     return view('client.auth.reset-password', ['email' => session('email')]);
    // }

    // public function resetPasswordIndex(Request $req, $token = null)
    // {
    //     $decodedToken = urldecode($token); // Decode the token
    
    //     // dd($req->all(), $decodedToken); // Debugging
    
    //     if (!$decodedToken || !$req->email) {
    //         return redirect(route('web.login'))->with('error', 'Invalid or expired password reset link.');
    //     }
    
    //     return view('client.auth.reset-password', [
    //         'email' => $req->email,
    //         'token' => $decodedToken
    //     ]);
    // }

    public function resetPasswordIndex(Request $req)
{
    $decodedToken = urldecode($req->token);

    if (!$decodedToken || !$req->email) {
        return redirect(route('web.login'))->with('error', 'Invalid or expired password reset link.');
    }

    return view('client.auth.reset-password', [
        'email' => $req->email,
        'token' => $decodedToken
    ]);
}

    // public function resetPassword(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         'password' => 'required|confirmed',
    //     ]);
    //     if ($validator->fails()) {
    //         return back()->with(['errors' => $validator->errors(), 'email' => $req->email]);
    //     }

    //     $user = User::where('email', $req->email)->first();
    //     $user->password = Hash::make($req->password);
    //     $user->save();

    //     return redirect(route('web.login'))->with('success', "Password reset successfully");
    // }

    public function resetPassword(Request $req)
{
    // dd($req->all());
    $validator = Validator::make($req->all(), [
        'email' => 'required|email',
        'password' => 'required|confirmed',
    ]);

    if ($validator->fails()) {
        return back()->with(['errors' => $validator->errors()]);
    }

    // Reset the password using Laravel's built-in method
    $status = Password::broker()->reset(
        $req->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        }
    );
    // dd($status);

    if ($status == Password::PASSWORD_RESET) {
        return redirect(route('web.login'))->with('success', 'Password reset successfully');
    } else {
        return back()->withErrors(['email' => __($status)]);
    }
}

    public function authCheck()
    {
        return response()->json(['authenticated' => auth()->check()]);
    }

    public function deleteAccount(Request $req)
    {
        try {
            $user = User::find(auth()->id());
            $user->email = $user->email . "-account-deleted-" . rand(1111111, 9999999);
            $user->phone_number = $user->phone_number . "-account-deleted-" . rand(1111111, 9999999);
            $user->google_id = null;
            $user->status = 3;
            $user->save();

            Auth::logout();

            return redirect(route('web.login'))->with('Your account has been deleted');
        } catch (\Throwable $th) {
            return redirect()->back()->with("Something unexpected happened on server. " . $th->getMessage());
        }
    }
}
