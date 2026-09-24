<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ebook extends Model
{
    protected $primaryKey = 'ebook_id';
    public $timestamps = false;

    protected $fillable = [
        'category_id', 'author_id', 'title', 'publisher', 'publication_year',
        'description', 'file_url', 'access_status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'author_id');
    }
}
