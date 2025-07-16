<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\BankDetail;
use App\Models\User;
use Carbon\Carbon;

class AuthenticationController extends AdminBaseController
{

    // Login Index
    public function loginIndex()
    {

        if (Auth::guard('admin')->check()) {
            return redirect(route('admin.dashboard.index'));
        }

        return view('admin.auth.login', $this->data);
    }

    // Login
    public function login(Request $request)
    {

        if (!Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password, 'status' => 1])) {
            return redirect()->back()->with('error', 'Email or password is not correct or your account is not active');
        }

        return redirect(route('admin.dashboard.index'));
    }

    // Logout
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect(route('admin.login'));
    }

    public function resetPasswordEmail(Request $req)
    {
        return view('admin.auth.reset-password-email');
    }

    public function verifyResetPasswordEmailIndex(Request $req)
    {
        $email = $req->email;
        $user = Admin::whereEmail($req->email)->first();

        if (!$user->otp) {
            $otp = rand(111111, 999999);
            $user->otp = $otp;
            $user->save();

            Mail::to($req->email)->send(new EmailVerification('Sir/Madam', $otp));
        }

        return view('admin.auth.verify-reset-password-email', compact('email'));
    }


    public function verifyResetPasswordEmail(Request $req)
    {
        try {
            $data = $req->except('_token');
            $otp = '';
            foreach ($data['otp'] as $key => $value) {
                $otp .= $value;
            };
            $user = Admin::where('email', $req->email)->first();

            if ($otp != $user->otp) {
                return redirect()->back()->with(['error' => "Your verification code is not correct"]);
            } else {
                $user->otp = null;
                $user->save();
                return redirect(route('admin.forget-password.reset-password.index'))->with('email', $req->email);
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "Something unexpected happened on server. " . $th->getMessage());
        }
    }

    public function resetPasswordIndex(Request $req)
    {
        if (!session('email')) return redirect(route('admin.login'));
        return view('admin.auth.reset-password', ['email' => session('email')]);
    }

    public function resetPassword(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'password' => 'required|confirmed',
        ]);
        if ($validator->fails()) {
            return back()->with(['errors' => $validator->errors(), 'email' => $req->email]);
        }

        $user = Admin::where('email', $req->email)->first();
        $user->password = Hash::make($req->password);
        $user->save();

        return redirect(route('admin.login'))->with('success', "Password reset successfully");
    }

    public function addCardForm(Request $req)
    {
        $provider = $req->account;
        return view('admin.users.providers.add-card', ['data' => $provider]);
    }

    public function addStripeAccount(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'ssn_last_4' => 'required',
                'city' => 'required',
                'state' => 'required',
                'postal_code' => 'required',
                'dob' => 'required',
                'address' => 'required',
                'routing_number' => 'required',
                'account_number' => 'required'
            ]);
            // return back()->withInput()->with(['email' => $req->email, 'errors' => $validator->errors()]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                ]);
            }

            $dob = $req->dob;
            $dob = explode('-', $dob);

            $provider = User::find($req->provider_id);

            $account = $this->stripe->accounts->create([
                'type' => 'custom',
                'country' => 'US',
                'business_type' => 'individual',
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'tos_acceptance' => [
                    'date' => Carbon::now()->timestamp,
                    'ip' => $req->ip(),
                ],
                'individual' => [
                    'ssn_last_4' => $req->ssn_last_4,
                    'first_name' => $provider->first_name,
                    'last_name' => $provider->last_name,
                    'dob' => ['day' => $dob[2], 'month' => $dob[1], 'year' => $dob[0]],
                    'phone' =>  $provider->phone_number,
                    'email' => $provider->email ? $provider->email : $provider->unverified_email,
                    'address' => [
                        'city' => $req->city,
                        'line1' => $req->address,
                        'postal_code' => $req->postal_code,
                        'state' => $req->state
                    ],
                ],
                'external_account' => [
                    'object' => 'bank_account',
                    'country' => 'US',
                    'currency' => 'usd',
                    'routing_number' => $req->routing_number,
                    'account_number' => $req->account_number,
                ],
                'business_profile' => [
                    'mcc' => '1520',
                    'url' => 'mowingandplowing.com'
                ]
            ]);

            if ($account->capabilities->card_payments != 'Inactive' && $account->capabilities->transfers != 'Inactive') {

                $this->bank = new BankDetail();
                $this->bank->provider_id = $req->provider_id;
                $this->bank->account_id = $account->id;
                $this->bank->routing_number = $req->routing_number;
                $this->bank->account_number = $req->account_number;
                $this->bank->bank_name = $account->external_accounts->data[0]->bank_name;
                $this->bank->ssn_last_4 = $req->ssn_last_4;
                $this->bank->city = $req->city;
                $this->bank->state = $req->state;
                $this->bank->postal_code = $req->postal_code;
                $this->bank->address = $req->address;
                $this->bank->dob = $req->dob;
                $this->bank->save();

                $this->user = User::find($req->provider_id);
                $this->user->provider_account_id = $account->id;
                $this->user->update();

                return parent::resp(true, "Your bank account has been created.");
            } else {

                return parent::resp(false, 'There was a problem while adding your bank account');
            }
        } catch (\Throwable $th) {
            return parent::resp(false, "Something unexpected happened on server. " . $th->getMessage());
        }
    }
}
