<?php

namespace TurboFrame\Database;

abstract class Migration
{
    protected Schema $schema;

    public function __construct()
    {
        $this->schema = new Schema();
    }

    abstract public function up(): void;

    abstract public function down(): void;
}
