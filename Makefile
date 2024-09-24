COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_ALLOW_SUPERUSER

.PHONY: install phpvendor
install: phpvendor

phpvendor: php/vendor/autoload.php

.PHONY: composer
composer php/vendor/autoload.php: php/composer.lock
	cd php; composer install

