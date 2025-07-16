<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MasqueradeController extends AdminBaseController
{
    public function masquerade($id)
    {
        // Store the admin's ID and masquerading flag
        session()->put('admin_id', Auth::guard('admin')->id());
        session()->put('is_masquerading', true);
        
        // Login as the user using the web guard
        Auth::guard('web')->loginUsingId($id, true);
        
        // Redirect to the user's dashboard
        return redirect()->route('dashboard');
    }
    public function stopMasquerade()
    {
        // Get the admin's ID from the session
        $adminId = session()->get('admin_id');
        
        if ($adminId) {
            // Login back as the admin
            $admin = User::findOrFail($adminId);
            Auth::login($admin);
            
            // Remove the masquerade session data
            session()->forget(['admin_id', 'is_masquerading']);
            
            return redirect()->route('admin.dashboard.index');
        }
        
        return redirect()->route('admin.login');
    }
}
