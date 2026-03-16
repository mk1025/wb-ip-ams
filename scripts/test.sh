#!/bin/bash
set -e

(cd app/auth-service && ./vendor/bin/phpunit --no-coverage)

(cd app/ip-service && ./vendor/bin/phpunit --no-coverage)

(cd app/gateway && ./vendor/bin/phpunit --no-coverage)

pnpm --filter frontend run test --run
