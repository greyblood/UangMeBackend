<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Borrower;
use App\Models\User;
use App\Models\Lender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'phone' => 'required|string|unique:users',
            'no_ktp' => 'required|string|unique:users',
            'foto_ktp' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'penghasilan' => 'required|numeric',
            'npwp' => 'nullable|string|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:borrower,lender',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::create([
            'nama' => $request->nama,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'phone' => $request->phone,
            'no_ktp' => $request->no_ktp,
            'foto_ktp' => $request->file('foto_ktp')->store(path: 'ktp_photos', options: 'public'),
            'penghasilan' => $request->penghasilan,
            'npwp' => $request->npwp,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        if ($request->role === 'borrower') {
            $loanLimit = $request->penghasilan * 0.30;

            Borrower::create([
                'user_id' => $user->id,
                'limit' => $loanLimit,
            ]);
        }
        elseif ($request->role == 'lender') {
            Lender::create([
                'user_id' => $user->id,
                'investment' => 0,  // Initial investment for lender
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $credentials = $request->only('phone', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
        $user = JWTAuth::user();
        return response()->json(['token' => $token], 200);
    }

    public function me()
    {
        try {
            $user = JWTAuth::user();    
            $data = [];
            if ($user->role == 'borrower') {
                $borrower = Borrower::where('user_id', $user->id)->first();
                $data['loan_limit'] = $borrower ? $borrower->limit : 0;
            } elseif ($user->role == 'lender') {
                $lender = Lender::where('user_id', $user->id)->first();
                $data['investment'] = $lender ? $lender->investment : 0;
            }
    
            return response()->json([
                'user' => $user,
                'data' => $data,
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not get user data'], 500);
        }
    }
    
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not invalidate token'], 500);
        }
    }
}
