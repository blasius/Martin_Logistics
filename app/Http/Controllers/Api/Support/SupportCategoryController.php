<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use Illuminate\Http\Request;

class SupportCategoryController extends Controller
{
    public function index(Request $request)
    {
        return SupportCategory::query()
            ->where('is_active', true)
            ->withCount('tickets')
            ->orderBy('name')
            ->get();
    }
}
