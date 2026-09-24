<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = ['category_name', 'description'];

    public function books()
    {
        return $this->hasMany(Book::class, 'category_id', 'category_id');
    }
}
