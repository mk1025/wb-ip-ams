FROM node:22-alpine AS builder

RUN corepack enable && corepack prepare pnpm@10.30.3 --activate

WORKDIR /app

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY packages/shared-types/package.json ./packages/shared-types/
COPY app/frontend/package.json ./app/frontend/

RUN pnpm install --frozen-lockfile

COPY packages/shared-types ./packages/shared-types
COPY app/frontend ./app/frontend

RUN pnpm --filter @wb-ip-ams/shared-types run build

ARG VITE_API_BASE_URL=http://localhost:3000/api

RUN pnpm --filter frontend run build

FROM nginx:alpine

COPY --from=builder /app/app/frontend/dist /usr/share/nginx/html
COPY app/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
