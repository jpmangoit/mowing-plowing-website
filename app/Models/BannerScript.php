<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class BannerScript extends Model
{
    use HasFactory;
    protected $table= 'banner_scripts';
    protected $fillable= ['description'];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('m-d-Y H:i:s');
    }
}
