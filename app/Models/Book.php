<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books';
    protected $primaryKey = 'book_id';
    public $timestamps = false;

   protected $fillable = [
    'category_id', 'author_id', 'title', 'publisher', 'publication_year',
    'description', 'location', 'stock', 'available_stock', 'is_active',
    'cover_image', 'created_by', 'updated_by', 'updated_at',
];

    // hanya buku yang masih aktif di katalog (buku "dihapus" = is_active 0)
    public function scopeAktif($query)
    {
        return $query->where('is_active', 1);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'author_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'book_id', 'book_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'book_id', 'book_id');
    }

    // admin yang menambahkan / terakhir mengedit buku ini
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
