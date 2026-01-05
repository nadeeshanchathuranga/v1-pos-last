<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'Operator'
        DB::statement("ALTER TABLE users MODIFY COLUMN role_type ENUM('Admin', 'Cashier', 'Manager', 'Operator') DEFAULT 'Cashier'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE users MODIFY COLUMN role_type ENUM('Admin', 'Cashier', 'Manager') DEFAULT 'Cashier'");
    }
};
