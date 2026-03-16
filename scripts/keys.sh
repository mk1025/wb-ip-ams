#!/bin/bash
set -e

if grep -q "JWT_PRIVATE_KEY" .env 2>/dev/null; then
    echo "keys already exist in .env"
    exit 0
fi

openssl genrsa -out private.pem 4096
openssl rsa -in private.pem -pubout -out public.pem

echo "JWT_PRIVATE_KEY=$(openssl base64 -A -in private.pem)" >> .env
echo "JWT_PUBLIC_KEY=$(openssl base64 -A -in public.pem)" >> .env

rm private.pem public.pem

echo "done"
