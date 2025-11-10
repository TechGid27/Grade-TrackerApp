<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
       Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 🔑 Track owner
            $table->string('name');
            $table->string('color')->nullable();
            $table->decimal('target_grade', 5, 2)->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};
