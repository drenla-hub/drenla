<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $settings = [
            'site_name' => SiteSetting::getValue('site_name', 'Drenla'),
            'contact_email' => SiteSetting::getValue('contact_email', 'hello@drenla.com'),
            'primary_phone' => SiteSetting::getValue('primary_phone', '+254 700 000 000'),
            'office_location' => SiteSetting::getValue('office_location', 'Nairobi, Kenya'),
            'footer_text' => SiteSetting::getValue('footer_text', 'Drenla builds disciplined brands, environments, and delivery systems.'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email'],
            'primary_phone' => ['nullable', 'string', 'max:255'],
            'office_location' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string'],
        ]);

        SiteSetting::putValue('site_name', $data['site_name']);
        SiteSetting::putValue('contact_email', $data['contact_email']);
        SiteSetting::putValue('primary_phone', $data['primary_phone'] ?? null);
        SiteSetting::putValue('office_location', $data['office_location'] ?? null);
        SiteSetting::putValue('footer_text', $data['footer_text'] ?? null);

        return back()->with('status', 'Settings updated.');
    }
}
