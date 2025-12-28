<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Settings\Models\CompanySetting;

class CompanySettingController extends Controller
{
    /**
     * Display the company settings form.
     */
    public function index(): View
    {
        $settings = CompanySetting::getSettings();

        return view('settings::company.index', compact('settings'));
    }

    /**
     * Update the company settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_name_ar' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,gif,svg|max:2048',
            'address' => 'nullable|string|max:1000',
            'address_ar' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'commercial_register' => 'nullable|string|max:100',
            'default_vat_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|size:3',
            'bank_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'swift' => 'nullable|string|max:20',
        ]);

        $settings = CompanySetting::getSettings();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            // Store new logo
            $path = $request->file('logo')->store('company', 'public');
            $settings->logo_path = $path;
        }

        // Handle logo removal
        if ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        // Update settings
        $settings->company_name = $validated['company_name'];
        $settings->company_name_ar = $validated['company_name_ar'];
        $settings->address = $validated['address'];
        $settings->address_ar = $validated['address_ar'];
        $settings->phone = $validated['phone'];
        $settings->email = $validated['email'];
        $settings->website = $validated['website'];
        $settings->tax_id = $validated['tax_id'];
        $settings->commercial_register = $validated['commercial_register'];
        $settings->default_vat_rate = $validated['default_vat_rate'];
        $settings->currency = $validated['currency'];

        // Bank details as JSON
        $settings->bank_details = [
            'bank_name' => $validated['bank_name'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'swift' => $validated['swift'] ?? null,
        ];

        $settings->save();

        return redirect()->route('settings.company.index')
            ->with('success', 'Company settings updated successfully.');
    }
}
