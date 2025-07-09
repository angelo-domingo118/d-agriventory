<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\ClearsDashboardCache;

class ConsumableItem extends Model
{
    use HasFactory, ClearsDashboardCache;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consumable_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consumable_record_id',
        'item_specification_id',
        'initial_quantity',
        'current_quantity',
    ];

    /**
     * Get the consumable record this item belongs to.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ConsumableRecord::class, 'consumable_record_id');
    }

    /**
     * Get the specification for this consumable item.
     */
    public function specification(): BelongsTo
    {
        return $this->belongsTo(ItemSpecification::class, 'item_specification_id');
    }

    /**
     * Calculate the total value of all consumable items.
     *
     * @return float
     */
    public static function calculateTotalValue(): float
    {
        $avgPrices = ContractItem::query()
            ->select('item_specification_id', DB::raw('AVG(unit_price) as average_price'))
            ->groupBy('item_specification_id');

        return (float) DB::table('consumable_items')
            ->joinSub($avgPrices, 'avg_prices', function ($join) {
                $join->on('consumable_items.item_specification_id', '=', 'avg_prices.item_specification_id');
            })
            ->sum(DB::raw('consumable_items.current_quantity * avg_prices.average_price'));
    }
}
