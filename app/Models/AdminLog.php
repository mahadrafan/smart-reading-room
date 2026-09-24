<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    protected $table = 'admin_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'table_name', 'record_id', 'description',
    ];

    // helper singkat: AdminLog::catat('Tambah', 'books', $id, 'keterangan...')
    public static function catat($action, $tableName, $recordId, $description, $adminId = null)
    {
        return self::create([
            'admin_id'    => $adminId ?? auth()->id() ?? 1,
            'action'      => $action,
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'description' => $description,
        ]);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
