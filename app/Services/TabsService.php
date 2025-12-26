<?php

namespace App\Services;

use App\Repositories\TabsRepository;

class TabsService
{
    protected TabsRepository $tabsRepository;

    public function __construct(TabsRepository $tabsRepository)
    {
        $this->tabsRepository = $tabsRepository;
    }

    public function getAllTabs()
    {
        return $this->tabsRepository->getAll();
    }

    public function getTabById(int $id)
    {
        return $this->tabsRepository->findById($id);
    }

    public function createTab(array $data)
    {
        return $this->tabsRepository->create($data);
    }

    public function updateTab(int $id, array $data)
    {
        return $this->tabsRepository->update($id, $data);
    }

    public function deleteTab(int $id)
    {
        return $this->tabsRepository->delete($id);
    }
}



