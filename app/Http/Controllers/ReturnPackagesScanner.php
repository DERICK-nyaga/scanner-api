<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LinehaulDispatch;
use App\Models\ReturnedPackage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\LinehaulDispatchSummaryMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ReturnPackagesController extends Controller
{
    public function index()
    {
        return view('admin.returns.scanner');
    }

    public function storeHandover(Request $request)
    {
        $validated = $request->validate([
            'scannedPackages' => 'required|array|min:1',
            'linehaulCompany' => 'required|string',
            'linehaulEmail' => 'required|email',
            'handoverName' => 'required|string|min:3',
            'handoverPhone' => 'required|string|min:10',
        ]);

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $refNo = 'LH-' . date('Ymd') . '-' . rand(1000, 9999);

        // 1. Create Dispatch Record
        $dispatch = LinehaulDispatch::create([
            'dispatch_reference' => $refNo,
            'linehaul_company' => $validated['linehaulCompany'],
            'linehaul_email' => $validated['linehaulEmail'],
            'handover_person_name' => $validated['handoverName'],
            'handover_person_phone' => $validated['handoverPhone'],
            'otp_code' => $otp,
            'status' => 'pending_otp',
            'created_by_user_id' => Auth::id() ?? 1,
        ]);

        // 2. Attach Scanned Packages
        foreach ($validated['scannedPackages'] as $barcode) {
            ReturnedPackage::create([
                'linehaul_dispatch_id' => $dispatch->id,
                'package_code' => $barcode,
                'scanned_at' => now(),
            ]);
        }

        // 3. Dispatch SMS via Africa's Talking API
        $this->sendSmsNotification($validated['handoverPhone'], $validated['handoverName'], $otp, count($validated['scannedPackages']), $refNo);

        // 4. Dispatch Background Email to Linehaul Company
        Mail::to($validated['linehaulEmail'])->queue(new LinehaulDispatchSummaryMail($dispatch, $validated['scannedPackages']));

        return response()->json([
            'status' => 'success',
            'message' => 'Handover saved successfully! OTP and email summary dispatched.'
        ]);
    }

    private function sendSmsNotification($phone, $name, $otp, $count, $ref)
    {
        $formattedPhone = preg_replace('/^0/', '+254', $phone);
        $message = "Hello {$name}, {$count} packages (Ref: {$ref}) handed over to you. Verification OTP: {$otp}. Please provide this code to complete dispatch.";

        try {
            Http::withHeaders([
                'apiKey' => config('services.africastalking.key'),
                'Accept' => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to' => $formattedPhone,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            logger()->error('SMS Handover Dispatch Failed: ' . $e->getMessage());
        }
    }
}