<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('operator.dashboard');
    }
}
