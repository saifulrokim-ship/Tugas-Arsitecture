<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pergantian bahasa UI (PRD §10): English + Indonesia, pilihan disimpan di
 * session, default English.
 */
class LocaleController extends Controller
{
    public const SUPPORTED = ['en', 'id'];

    public function store(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'en';
        }

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return back();
    }
}
