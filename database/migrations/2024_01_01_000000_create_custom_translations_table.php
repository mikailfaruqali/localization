<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('custom_translations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('locale', 10)->index();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['key', 'locale'], 'custom_translations_key_locale_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_translations');
    }
};
