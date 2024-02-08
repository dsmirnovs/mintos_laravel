<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @property int $channel_id
 * @property string $name
 * @property string $begin_date_time
 * @property string $end_date_time
 */
class Customer extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'mintos_clients';

    /**
     * check if customer exists
     * @param int $customerId
     * @return int
     */
    public function isCustomerExists(int $customerId): int
    {
        $customers = $this::query()
            ->where('unique_client_id', $customerId)
            ->get();
        return $customers->count();
    }
}
