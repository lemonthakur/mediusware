<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function product_variant_info_one(){
        return $this->hasOne(ProductVariant::class, 'id', 'product_variant_one');
    }

    public function product_variant_info_two(){
        return $this->belongsTo(ProductVariant::class, 'product_variant_two');
    }

    public function product_variant_info_three(){
        return $this->belongsTo(ProductVariant::class, 'product_variant_three');
    }
}
