<?php

namespace App\Http\Middleware;

use Closure;
use Session, Config;
use Illuminate\Http\Request;
use Modules\Currency\App\Models\Currency;
use Modules\Language\App\Models\Language;
use Symfony\Component\HttpFoundation\Response;

class LangSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       // Set language session if not already set or if session language is invalid
        if (!Session::has('front_lang') || !Language::where('lang_code', Session::get('front_lang'))->exists()) {
            $default_lang = Language::find(1);

            Session::put('front_lang', $default_lang?->lang_code ?? 'en');
            Session::put('lang_dir', $default_lang?->lang_direction ?? 'left_to_right');
            Session::put('front_lang_name', $default_lang?->lang_name ?? 'English');
        }

        // Set app locale
        app()->setLocale(Session::get('front_lang'));

        if (!Session::has('currency_code') || !Currency::where('currency_code', Session::get('currency_code'))->exists()) {

            $default_currency = Currency::where('is_default', 'yes')->first()
                                ?? Currency::find(1);

            if ($default_currency) {
                Session::put('currency_name', $default_currency->currency_name);
                Session::put('currency_code', $default_currency->currency_code);
                Session::put('currency_icon', $default_currency->currency_icon);
                Session::put('currency_rate', $default_currency->currency_rate);
                Session::put('currency_position', $default_currency->currency_position);
            }
        }

        return $next($request);
    }
}
