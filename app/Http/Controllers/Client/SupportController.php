<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\OrderTrait;

class SupportController extends ClientBaseController
{
    use OrderTrait;
    public function index()
    {
        return view('client.support.index');
    }

    public function createSupportTicket(Request $req)
    {
        $req->validate([
            'detail' => 'required',
        ]);

        auth()->user()->ticket()->create([
            'user_id' => auth()->id(),
            'detail' => $req->detail
        ]);
        $user = User::find(auth()->id());
        $this->sendSms($user->phone_number,"Support ticket has been generated");
        return redirect()->back()->with('success','Support ticket has been generated');
    }
}
