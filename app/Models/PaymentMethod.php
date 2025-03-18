<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'metodo_pago';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tipo',
        'card_number',
        'card_holder_name',
        'expiration_date',
        'is_default',
    ];

    /**
     * Get the user that owns the payment method.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}