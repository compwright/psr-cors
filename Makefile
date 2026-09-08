test:
	vendor/bin/phpunit tests --display-all-issues

lint:
	vendor/bin/phpstan analyse src tests
	vendor/bin/php-cs-fixer fix
