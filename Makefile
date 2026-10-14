.PHONY: init up down seed migrate fresh test shell logs reset

init: up
	docker compose exec app sh -c 'i=0; until php artisan migrate --force; do i=$$((i+1)); if [ $$i -gt 30 ]; then exit 1; fi; sleep 2; done'
	docker compose exec app php artisan s3:bucket
	docker compose exec app php artisan db:seed --force

up:
	docker compose up -d --build

down:
	docker compose down

migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

test:
	docker compose exec app php artisan test

shell:
	docker compose exec app sh

logs:
	docker compose logs -f --tail=100

reset:
	docker compose down -v
	$(MAKE) init
