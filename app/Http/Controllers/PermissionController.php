<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Permission::query()
            ->orderBy('id')
            ->get(['id', 'name', 'description']));
    }
}
