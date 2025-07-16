<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    // protected function redirectTo($request)
    // {
    //     if (! $request->expectsJson()) {
    //         return route('web.login');
    //     }
    // }

    protected function redirectTo($request)
    {
        // If admin is masquerading, don't redirect
        if (session()->has('admin_id') && session()->has('is_masquerading')) {
            return null;
        }
        
        if (! $request->expectsJson()) {
            return route('web.login');
        }
    }
}
