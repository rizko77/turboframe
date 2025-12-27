<?php

use TurboFrame\Database\Migration;
use TurboFrame\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('posts');
    }
};