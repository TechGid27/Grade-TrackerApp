<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 🔑 Track owner
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['quiz', 'test', 'exam', 'assignment', 'project']);
            $table->decimal('grade', 5, 2);
            $table->decimal('max_grade', 5, 2);
            $table->decimal('weight', 5, 2)->default(1.0);
            $table->date('date_taken')->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
};
