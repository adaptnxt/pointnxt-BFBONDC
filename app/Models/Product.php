<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'upc',
        'asin',
        'title',
        'description',
        'images',
        'manufacturer',
        'ingredients',
        'brand',
        'model',
        'weight',
        'dimension',
        'category',
        'price',
        'lowest_recorded_price',
        'highest_recorded_price'
    ];
}
