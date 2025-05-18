<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::rename('programs', 'classes');
        Schema::table('classes', function (Blueprint $table) {
            $table->renameColumn('class_type_id', 'lesson_id');
        });
    }

    public function down()
    {
        Schema::rename('classes', 'programs');
        Schema::table('programs', function (Blueprint $table) {
            $table->renameColumn('lesson_id', 'class_type_id');
        });
    }

};
