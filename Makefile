.PHONY: up down build restart logs db-init admin-dev

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
	docker-compose exec -T mysql sh -c 'MYSQL_PWD="$$MYSQL_ROOT_PASSWORD" mysql -u root ads' < install.sql

admin-dev:
	cd admin/public/web && npm run dev
