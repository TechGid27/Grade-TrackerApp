<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProtectedController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'message' => 'You are authorized!',
            'user' => $request->user(), 
        ]);
    }
}
