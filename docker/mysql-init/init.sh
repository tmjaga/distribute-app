#!/bin/bash
set -e

mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
  CREATE DATABASE IF NOT EXISTS distribute_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL PRIVILEGES ON distribute_app_test.* TO 'laravel_user'@'%';
  FLUSH PRIVILEGES;
EOSQL