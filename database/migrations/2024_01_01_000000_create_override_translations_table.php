<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('override_translations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('locale', 2)->index();
            $table->text('value')->nullable();
            $table->unique(['key', 'locale']);
        });
    }
};
