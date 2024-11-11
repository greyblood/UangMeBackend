<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Lender;
use App\Models\InvestmentHistory;

class LenderController extends Controller
{
    public function addInvestment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000',
                'bank' => 'required|in:BCA,BRI,BNI,Mandiri',
            ]);

            $user = JWTAuth::user();
            $lender = Lender::where('user_id', $user->id)->first();
            $lender->investment += $request->amount;
            $lender->save();

            $bankCodes = [
                'BCA' => '1123',
                'BRI' => '1124',
                'BNI' => '1125',
                'Mandiri' => '1126',
            ];

            $vaNumber = $bankCodes[$request->bank] . $user->phone;


            InvestmentHistory::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'bank' => $request->bank,
                'va_number' => $vaNumber,
            ]);

            return response()->json([
                'message' => 'Investment added successfully',
                'no_va' => $vaNumber,
                'investment' => $lender->investment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding the investment.'], 500);
        }
    }

    public function investmentHistory()
{
    $user = JWTAuth::user();
    $investmentHistory = InvestmentHistory::where('user_id', $user->id)
        ->orderBy('created_at', 'desc') 
        ->get();

    $historyData = $investmentHistory->map(function ($history) {
        return [
            'date' => $history->created_at->format('d F Y H:i:s'),
            'amount' => $history->amount,
            'bank' => $history->bank,
            'no_va' => $history->va_number,
        ];
    });
    return response()->json([
        'investment_history' => $historyData,
    ]);
}

}
