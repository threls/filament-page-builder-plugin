<?php

namespace Threls\FilamentPageBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language') 
            ?? $request->get('locale') 
            ?? config('app.locale', 'en');
        
        $availableLocales = config('filament-language-switch.locales', ['en']);
        
        if (in_array($locale, $availableLocales)) {
            App::setLocale($locale);
        }
        
        return $next($request);
    }
}