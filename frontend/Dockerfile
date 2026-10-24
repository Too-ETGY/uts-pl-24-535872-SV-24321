# ---- dev: running development/test target ----
FROM node:24.22.1-alpine AS dev

WORKDIR /app

# Source is expected to be bind-mounted at /app
COPY package*.json ./
RUN npm ci || true

EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ---- build: production asset compilation ----
FROM node:24.22.1-alpine AS build

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- production: serve with nginx ----
FROM nginx:1.27-alpine AS production

COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
