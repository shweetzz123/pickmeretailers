<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * The keys the admin payment settings screen manages.
     *
     * @var array<int, string>
     */
    public const GCASH_KEYS = [
        'gcash_number',
        'gcash_name',
        'gcash_qr_path',
        'gcash_instructions',
    ];

    public function edit()
    {
        $settings = [
            'gcash_number' => Setting::get('gcash_number', ''),
            'gcash_name' => Setting::get('gcash_name', ''),
            'gcash_instructions' => Setting::get('gcash_instructions', ''),
            'gcash_qr_path' => Setting::get('gcash_qr_path'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gcash_number' => ['nullable', 'string', 'max:30'],
            'gcash_name' => ['nullable', 'string', 'max:255'],
            'gcash_instructions' => ['nullable', 'string', 'max:2000'],
            'gcash_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        Setting::putMany([
            'gcash_number' => $validated['gcash_number'] ?? null,
            'gcash_name' => $validated['gcash_name'] ?? null,
            'gcash_instructions' => $validated['gcash_instructions'] ?? null,
        ]);

        if ($request->hasFile('gcash_qr')) {
            $file = $request->file('gcash_qr');
            $name = 'gcash-qr-'.uniqid().'.'.($file->guessExtension() ?: 'png');
            $file->move(public_path('payment-qr'), $name);

            Setting::put('gcash_qr_path', $name);
        }

        return redirect()->back()->with('success', 'GCash payment settings updated.');
    }
}
