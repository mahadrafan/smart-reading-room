<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('loans', 'fine_paid_at')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->timestamp('fine_paid_at')->nullable()->after('approved_at');
            });
        }

        if (! Schema::hasColumn('loans', 'fine_paid_by')) {
            Schema::table('loans', function (Blueprint $table) {
                // Tanpa FK agar kompatibel dengan SQL lama (user_id bertipe INT)
                // dan instalasi baru Laravel (user_id bertipe BIGINT).
                $table->unsignedBigInteger('fine_paid_by')->nullable()->after('fine_paid_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['fine_paid_at', 'fine_paid_by']);
        });
    }
};
