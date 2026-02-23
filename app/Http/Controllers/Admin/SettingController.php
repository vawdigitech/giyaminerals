<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('group', 'overtime')->get()->keyBy('key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'overtime_enabled' => 'required|boolean',
            'overtime_threshold_hours' => 'required|numeric|min:0|max:24',
            'overtime_bonus_percentage' => 'required|integer|min:0|max:200',
        ]);

        Setting::set(
            'overtime_enabled',
            (bool) $validated['overtime_enabled'],
            'boolean',
            'overtime',
            'Enable or disable overtime calculation'
        );

        Setting::set(
            'overtime_threshold_hours',
            (float) $validated['overtime_threshold_hours'],
            'decimal',
            'overtime',
            'Hours above standard working hours to qualify for overtime'
        );

        Setting::set(
            'overtime_bonus_percentage',
            (int) $validated['overtime_bonus_percentage'],
            'integer',
            'overtime',
            'Overtime bonus as percentage of daily rate'
        );

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
