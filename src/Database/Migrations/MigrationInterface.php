<?php

namespace Yabasi\Database\Migrations;

use Yabasi\Database\Connection;

interface MigrationInterface
{
    public function up(Connection $connection): void;
    public function down(Connection $connection): void;
}