<?php

namespace App\Http\Controllers;

use App\Services\Line\Ims\LineImsSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LineImsSettingsController extends Controller
{
    public function edit(LineImsSettingsService $settings): View
    {
        return view('settings.line-ims', [
            'receptionEnabled' => $settings->receptionEnabled(),
        ]);
    }

    public function update(Request $request, LineImsSettingsService $settings): RedirectResponse
    {
        $request->validate([
            'reception_enabled' => ['nullable', 'boolean'],
        ]);

        $settings->save([
            'reception_enabled' => $request->boolean('reception_enabled'),
        ]);

        return redirect()
            ->route('settings.line_ims.edit')
            ->with('success', 'บันทึกการตั้งค่ารับข้อความสร้าง IMS แล้ว');
    }
}
