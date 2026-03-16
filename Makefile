up: # Запуск приложения
	docker compose up -d

down: # Остановка приложения
	docker compose down

restart: down up

clear: clear-cache clear-metrics

ci: # composer install
	docker compose exec -u 1000 php-fpm \
	composer install

cu: # composer update
	docker compose exec -u 1000 php-fpm \
	composer update

env: # composer dump-env dev
	docker compose exec -u 1000 php-fpm \
	composer dump-env dev

test: # php bin/phpunit
	docker compose exec -u 1000 php-fpm \
	php bin/phpunit --coverage-html=public/coverage --coverage-clover=coverage.xml

clear-metrics:
	docker compose exec -u 1000 php-fpm \
    php bin/console artprima:prometheus:metrics:clear

clear-cache:
	docker compose exec -u 1000 php-fpm \
    php bin/console c:c

init: env up ci dump-env test
