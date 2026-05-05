<?php

namespace App\Services\Fee;

use App\Enums\Fee\FeeHeadCategory;
use App\Models\Fee\FeeHead;
use App\Repositories\Fee\FeeHeadRepository;
use Illuminate\Http\Request;

class FeeHeadService
{
    public function __construct(
        protected FeeHeadRepository $repository
    ) {}

    /**
     * Get index data for fee heads
     */
    public function getIndexData(Request $request): array
    {
        $filters = $request->only(['search', 'category', 'is_active', 'is_optional']);
        $feeHeads = $this->repository->getPaginatedWithFilters($filters, 15);

        return [
            'feeHeads' => $feeHeads,
            'filters' => $filters,
            'categories' => $this->getCategories(),
        ];
    }

    /**
     * Get create data
     */
    public function getCreateData(): array
    {
        return [
            'categories' => $this->getCategories(),
            'frequencies' => $this->getFrequencies(),
            'nextOrder' => $this->repository->getNextAvailableOrder(),
        ];
    }

    /**
     * Get edit data
     */
    public function getEditData(FeeHead $feeHead): array
    {
        return [
            'feeHead' => $feeHead,
            'categories' => $this->getCategories(),
            'frequencies' => $this->getFrequencies(),
        ];
    }

    /**
     * Create fee head
     */
    public function create(array $data): FeeHead
    {
        return $this->repository->create($data);
    }

    /**
     * Update fee head
     */
    public function update(FeeHead $feeHead, array $data): bool
    {
        return $this->repository->update($feeHead, $data);
    }

    /**
     * Toggle active status of fee head
     */
    public function toggleActive(FeeHead $feeHead): bool
    {
        return $this->repository->update($feeHead, [
            'is_active' => ! $feeHead->is_active,
        ]);
    }

    /**
     * Delete fee head
     */
    public function delete(FeeHead $feeHead): bool
    {
        return $this->repository->delete($feeHead);
    }

    /**
     * Get categories for dropdown
     */
    private function getCategories(): array
    {
        return collect(FeeHeadCategory::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ])->toArray();
    }

    /**
     * Get frequencies for dropdown
     */
    private function getFrequencies(): array
    {
        return [
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'yearly', 'label' => 'Yearly'],
            ['value' => 'once', 'label' => 'One Time'],
        ];
    }
}
