<?php

namespace App\Livewire\Positions;

use App\Livewire\Concerns\HasSimpleCrudModal;
use App\Livewire\Forms\PositionForm;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Kelola Jabatan')]
class Index extends Component
{
    use HasSimpleCrudModal;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public PositionForm $form;

    public string $search = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingPositionId = null;

    protected function simpleCrudModalId(): string
    {
        return 'positionModal';
    }

    protected function simpleCrudEntityLabel(): string
    {
        return 'jabatan';
    }

    protected function simpleCrudDeleteAction(): string
    {
        return 'delete-position';
    }

    protected function simpleCrudModelClass(): string
    {
        return Position::class;
    }

    protected function simpleCrudEditingIdProperty(): string
    {
        return 'editingPositionId';
    }

    /**
     * Store a newly created position.
     */
    public function store(PositionService $positionService): void
    {
        $this->storeRecord($positionService);
    }

    /**
     * Update an existing position.
     */
    public function update(PositionService $positionService): void
    {
        $this->updateRecord($positionService);
    }

    /**
     * Delete a position after confirmation.
     */
    #[On('delete-position')]
    public function delete(int $id, PositionService $positionService): void
    {
        $this->deleteRecord($id, $positionService);
    }

    /**
     * Render the Livewire component view.
     */
    public function render(PositionService $positionService): View
    {
        $positions = $positionService->getPaginatedPositions(
            search: $this->search,
            perPage: $this->perPage
        );

        return view('livewire.positions.index', [
            'positions' => $positions,
        ]);
    }
}
