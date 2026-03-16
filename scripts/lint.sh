#!/bin/bash
set -e

(cd app/auth-service && ./vendor/bin/phpstan analyse --memory-limit=2G)

(cd app/ip-service && ./vendor/bin/phpstan analyse --memory-limit=2G)

(cd app/gateway && ./vendor/bin/phpstan analyse --memory-limit=2G)

pnpm --filter frontend run lint
