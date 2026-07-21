#!/bin/bash
mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<-EOSQL

  CREATE DATABASE changpopwiki;
  CREATE DATABASE changpopwiki_cargo;

  CREATE USER IF NOT EXISTS
    'wikiuser'@'%' IDENTIFIED BY '$MARIADB_PASSWORD',
    'cargouser'@'%' IDENTIFIED BY '$CARGOUSER_PASSWORD';

  GRANT ALL PRIVILEGES ON changpopwiki.* TO 'wikiuser'@'%';
  GRANT ALL PRIVILEGES ON changpopwiki_cargo.* TO 'cargouser'@'%';

EOSQL