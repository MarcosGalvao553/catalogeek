<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'produtos_catalogo';
    
    protected $fillable = [
        'name',
        'sku',
        'marca',
        'price_atacado',
        'price_varejo',
        'stock',
        'image_url',
        'product_tiny',
    ];

    protected $casts = [
        'price_atacado' => 'decimal:2',
        'price_varejo' => 'decimal:2',
        'stock' => 'integer',
        'product_tiny' => 'integer',
    ];

    public function image()
    {
        return $this->hasOne(ProductImage::class)->orderBy('id', 'asc');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
