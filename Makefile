SRC_DIR="src/"
SRC_FILES= $(shell find $(SRC_DIR) -name "*.php")

dist/roger-q.phar: box.json.dist tools/box.phar bin/roger-q.php $(SRC_FILES) composer.lock
	composer install --optimize-autoloader --no-dev --no-suggest --quiet
	./tools/box.phar compile --quiet

tools/box.phar:
	wget --directory-prefix=tools --quiet https://github.com/humbug/box/releases/download/4.2.0/box.phar
	chmod +x tools/box.phar

tools/php-cs-fixer.phar:
	wget https://cs.symfony.com/download/php-cs-fixer-v3.phar -O tools/php-cs-fixer.phar
	chmod +x tools/php-cs-fixer.phar

tools/phpstan.phar:
	wget --directory-prefix=tools --quiet https://github.com/phpstan/phpstan/releases/download/1.9.17/phpstan.phar
	chmod +x tools/phpstan.phar

phpcs: tools/php-cs-fixer.phar tools/phpstan.phar
	composer install --optimize-autoloader --no-dev --no-suggest --quiet
	tools/php-cs-fixer.phar fix --dry-run --stop-on-violation -v
	tools/phpstan.phar analyze --level=8 --no-progress bin/ src/

fix-cs: tools/php-cs-fixer.phar
	tools/php-cs-fixer.phar fix -v

dist: dist/roger-q.phar

clean:
	rm tools/ dist/ vendor/ -fr

.PHONY: clean phpcs fix-cs
