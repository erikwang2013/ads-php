.PHONY: up down build restart logs

up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build --no-cache

restart:
	docker-compose restart

logs:
	docker-compose logs -f

db-init:
	docker-compose exec mysql mysql -u root -p${DB_PASSWORD:-root} ads < /docker-entrypoint-initdb.d/ads-tenant/migration/create_tenants.sql
	docker-compose exec mysql mysql -u root -p${DB_PASSWORD:-root} ads < /docker-entrypoint-initdb.d/ads-platform/migration/create_campaign_tables.sql
	docker-compose exec mysql mysql -u root -p${DB_PASSWORD:-root} ads < /docker-entrypoint-initdb.d/ads-account/migration/create_platform_accounts.sql
	docker-compose exec mysql mysql -u root -p${DB_PASSWORD:-root} ads < /docker-entrypoint-initdb.d/ads-alert/migration/create_alerts.sql

admin-dev:
	cd admin && npm run dev
