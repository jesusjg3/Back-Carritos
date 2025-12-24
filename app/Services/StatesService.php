<?php

namespace App\Services;

use App\Repositories\StateRepository;

class StatesService
{
    protected StateRepository $stateRepository;

    public function __construct(StateRepository $stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }

    /**
     * Obtener todos los estados
     */
    public function getAllStates()
    {
        return $this->stateRepository->getAll();
    }

    /**
     * Obtener un estado por ID
     */
    public function getStateById(int $id)
    {
        return $this->stateRepository->findById($id);
    }

    /**
     * Crear un estado
     */
    public function createState(array $data)
    {
        // Aquí podrías agregar validaciones de negocio
        return $this->stateRepository->create($data);
    }

    /**
     * Actualizar un estado
     */
    public function updateState(int $id, array $data)
    {
        return $this->stateRepository->update($id, $data);
    }

    /**
     * Eliminar un estado
     */
    public function deleteState(int $id)
    {
        return $this->stateRepository->delete($id);
    }
}
