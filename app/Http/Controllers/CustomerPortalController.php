<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function index()
    {
        return view('customer');
    }
}
