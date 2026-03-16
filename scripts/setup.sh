#!/bin/bash
set -e

[ -f .env ] || cp .env.example .env

bash scripts/keys.sh

pnpm install

pnpm --filter @wb-ip-ams/shared-types run build

[ -f app/frontend/.env ] || cp app/frontend/.env.example app/frontend/.env
