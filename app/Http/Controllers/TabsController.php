<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTabRequest;
use App\Http\Requests\UpdateTabRequest;
use App\Services\TabsService;
use Illuminate\Http\JsonResponse;

class TabsController extends Controller
{
    protected TabsService $tabsService;

    public function __construct(TabsService $tabsService)
    {
        $this->tabsService = $tabsService;
    }

    public function index(): JsonResponse
    {
        $tabs = $this->tabsService->getAllTabs();
        return response()->json($tabs);
    }

    public function show(int $id): JsonResponse
    {
        $tab = $this->tabsService->getTabById($id);
        return response()->json($tab);
    }

    public function store(StoreTabRequest $request): JsonResponse
    {
        $tab = $this->tabsService->createTab($request->validated());
        return response()->json($tab, 201);
    }

    public function update(UpdateTabRequest $request, int $id): JsonResponse
    {
        $tab = $this->tabsService->updateTab($id, $request->validated());
        return response()->json($tab);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tabsService->deleteTab($id);
        return response()->json(null, 204);
    }
}



