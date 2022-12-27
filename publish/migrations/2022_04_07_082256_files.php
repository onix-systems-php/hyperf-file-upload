<?php

declare(strict_types=1);
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class Files extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fileable_id')->nullable();
            $table->string('fileable_type')->nullable();
            $table->string('field_name')->nullable();
            $table->string('storage');
            $table->string('path');
            $table->string('name');
            $table->string('full_path');
            $table->string('domain');
            $table->string('url');
            $table->string('original_name');
            $table->integer('size');
            $table->string('mime');
            $table->json('presets')->nullable();
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
}
