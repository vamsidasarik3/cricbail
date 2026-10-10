<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display the About page.
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Display the Contact page.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }

    /**
     * Handle contact form submission.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2500'],
        ]);

        return back()->with('status', 'Thank you for reaching out to CricBail. Your message has been received by our editorial desk.');
    }

    /**
     * Display the Privacy Policy page.
     */
    public function privacyPolicy(): View
    {
        return view('pages.privacy');
    }
}
