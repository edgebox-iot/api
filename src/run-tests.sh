#!/usr/bin/env bash

./bin/console doctrine:database:drop --force -e test
./bin/console doctrine:database:create -e test
./bin/console doctrine:migrations:migrate -n  -e test
./vendor/bin/phpunit
