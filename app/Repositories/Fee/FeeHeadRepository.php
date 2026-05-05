<?php

namespace App\Repositories\Fee;

use App\Models\Fee\FeeHead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class FeeHeadRepository
{
    private const CACHE_KEY = 'fee_heads';

    private const CACHE_TTL = 3600;

    /**
     * Get paginated fee heads with filters
     */
    public function getPaginatedWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = FeeHead::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('code', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $boolVal = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $boolVal);
        }

        if (isset($filters['is_optional'])) {
            $query->where('is_optional', $filters['is_optional']);
        }

        $result = $query->ordered()->paginate($perPage);

        return $result;
    }

    /**
     * Get all active fee heads
     */
    public function getAllActive(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY.'.active',
            self::CACHE_TTL,
            fn () => FeeHead::active()->ordered()->get()
        );
    }

    /**
     * Get fee heads for dropdown
     */
    public function getForDropdown(): array
    {
        return Cache::remember(
            self::CACHE_KEY.'.dropdown',
            self::CACHE_TTL,
            fn () => FeeHead::active()->ordered()->pluck('name', 'id')->toArray()
        );
    }

    /**
     * Get next available order (max order + 1)
     */
    public function getNextAvailableOrder(): int
    {
        $maxOrder = FeeHead::max('sort_order');

        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Create fee head
     */
    public function create(array $data): FeeHead
    {
        // If no sort_order provided or is null, auto-assign next available order
        if (! isset($data['sort_order']) || $data['sort_order'] === '' || $data['sort_order'] === null) {
            $data['sort_order'] = $this->getNextAvailableOrder();
        } else {
            // If specific order is provided, shift all existing orders >= requested order
            $requestedOrder = (int) $data['sort_order'];
            $this->shiftOrdersAtOrAbove($requestedOrder);
            $data['sort_order'] = $requestedOrder;
        }

        $feeHead = FeeHead::create($data);
        $this->clearCache();

        return $feeHead;
    }

    /**
     * Shift all orders at or above a given position
     */
    private function shiftOrdersAtOrAbove(int $fromOrder): void
    {
        FeeHead::where('sort_order', '>=', $fromOrder)
            ->increment('sort_order');
    }

    /**
     * Update fee head
     */
    public function update(FeeHead $feeHead, array $data): bool
    {
        // If sort_order is being updated, handle order shifting
        if (isset($data['sort_order']) && $data['sort_order'] !== '' && $data['sort_order'] !== null) {
            $newOrder = (int) $data['sort_order'];
            $oldOrder = $feeHead->sort_order;

            if ($newOrder !== $oldOrder) {
                if ($newOrder > $oldOrder) {
                    // Moving down: shift orders between old and new position up
                    FeeHead::where('sort_order', '>', $oldOrder)
                        ->where('sort_order', '<=', $newOrder)
                        ->where('id', '!=', $feeHead->id)
                        ->decrement('sort_order');
                } else {
                    // Moving up: shift orders between new and old position down
                    FeeHead::where('sort_order', '>=', $newOrder)
                        ->where('sort_order', '<', $oldOrder)
                        ->where('id', '!=', $feeHead->id)
                        ->increment('sort_order');
                }
            }
        } else {
            // If sort_order is not provided, auto-assign to end
            $data['sort_order'] = $this->getNextAvailableOrder();
        }

        $updated = $feeHead->update($data);
        $this->clearCache();

        return $updated;
    }

    /**
     * Delete fee head
     */
    public function delete(FeeHead $feeHead): bool
    {
        $deleted = $feeHead->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY.'.active');
        Cache::forget(self::CACHE_KEY.'.dropdown');
    }
}
