SRC_DIR="src/"
SRC_FILES= $(shell find $(SRC_DIR) -name "*.php")

dist/roger-q.phar: box.json.dist tools/box bin/roger-q.php $(SRC_FILES) composer.lock
	composer install --optimize-autoloader --no-dev --no-suggest --quiet
	./tools/box compile --quiet

tools/box:
	wget --directory-prefix=tools --quiet https://github.com/humbug/box/releases/download/3.1.2/box.phar
	mv tools/box.phar tools/box
	chmod +x tools/box

tools/php-cs-fixer:
	wget --directory-prefix=tools --quiet https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar
	mv tools/php-cs-fixer-v2.phar tools/php-cs-fixer
	chmod +x tools/php-cs-fixer

tools/phpstan:
	wget --directory-prefix=tools --quiet https://github.com/phpstan/phpstan-shim/raw/0.10.5/phpstan
	chmod +x tools/phpstan

phpcs: tools/php-cs-fixer tools/phpstan
	composer install --optimize-autoloader --no-dev --no-suggest --quiet
	tools/php-cs-fixer fix --dry-run --stop-on-violation -v
	tools/phpstan analyze --level=7 --no-progress bin/ src/

fix-cs: tools/php-cs-fixer
	tools/php-cs-fixer fix -v

dist: dist/roger-q.phar

clean:
	rm tools/ dist/ vendor/ -fr

.PHONY: clean phpcs fix-cs
