test:
	vendor/bin/phpunit

lint:
	vendor/bin/phpstan analyse src tests
	vendor/bin/php-cs-fixer fix
