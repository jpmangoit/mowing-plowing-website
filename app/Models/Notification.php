<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiver_id',
        'sender_id',
        'title',
        'content',
        'flag',
        'order_id'
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('m-d-Y H:i:s');
    }
}
