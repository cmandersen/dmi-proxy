.PHONY: up down build shell logs lint test fresh

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

shell:
	docker compose exec web bash

logs:
	docker compose logs -f

lint:
	docker compose exec web vendor/bin/pint .

test:
	docker compose exec web php artisan test

fresh:
	docker compose down -v
	docker compose up -d --build
