<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class ForceHttps extends Middleware{
    public function handle($request, Closure $next){
        if(!request->secure() && app()->environment('production'))
        {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);
    }
}