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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name_assessment');
            
            $table->enum('type_quarter', ['preliminary','midterm','pre_final','final']);
            $table->enum('type_activity', ['quiz','exam', 'assignment', 'project']);

            $table->enum('mode',['f2f','online']);

            $table->decimal('score', 5, 2);
            $table->decimal('total_items', 5, 2);

            $table->date('date_taken')->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
};
