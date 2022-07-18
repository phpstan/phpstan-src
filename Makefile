.PHONY: tests

build: cs tests phpstan

tests:
	php vendor/bin/paratest --runner WrapperRunner --no-coverage

tests-integration:
	php vendor/bin/paratest --runner WrapperRunner --no-coverage --group exec

tests-levels:
	php vendor/bin/paratest --runner WrapperRunner --no-coverage --group levels

tests-coverage:
	php vendor/bin/paratest --runner WrapperRunner

lint:
	php vendor/bin/parallel-lint --colors \
		--exclude tests/PHPStan/Analyser/data \
		--exclude tests/PHPStan/Rules/Methods/data \
		--exclude tests/PHPStan/Rules/Functions/data \
		--exclude tests/PHPStan/Rules/Operators/data/invalid-inc-dec.php \
		--exclude tests/PHPStan/Rules/Arrays/data/offset-access-without-dim-for-reading.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-declarations.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-enum-cases.php \
		--exclude tests/PHPStan/Rules/Classes/data/enum-sanity.php \
		--exclude tests/PHPStan/Rules/Classes/data/extends-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/implements-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/interface-extends-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/trait-use-error.php \
		--exclude tests/PHPStan/Rules/Properties/data/default-value-for-native-property-type.php \
		--exclude tests/PHPStan/Rules/Arrays/data/empty-array-item.php \
		--exclude tests/PHPStan/Rules/Classes/data/invalid-promoted-properties.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-promoted-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/default-value-for-promoted-property.php \
		--exclude tests/PHPStan/Rules/Operators/data/invalid-assign-var.php \
		--exclude tests/PHPStan/Rules/Functions/data/arrow-function-nullsafe-by-ref.php \
		--exclude tests/PHPStan/Levels/data/namedArguments.php \
		--exclude tests/PHPStan/Rules/Keywords/data/continue-break.php \
		--exclude tests/PHPStan/Rules/Properties/data/read-only-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/read-only-property-phpdoc-and-native.php   \
		--exclude tests/PHPStan/Rules/Properties/data/overriding-property.php \
		--exclude tests/PHPStan/Rules/Constants/data/overriding-final-constant.php \
		--exclude tests/PHPStan/Rules/Properties/data/intersection-types.php \
		--exclude tests/PHPStan/Rules/Classes/data/first-class-instantiation-callable.php \
		--exclude tests/PHPStan/Rules/Classes/data/instantiation-callable.php \
		src tests

cs:
	composer install --working-dir build-cs && php build-cs/vendor/bin/phpcs

cs-fix:
	php build-cs/vendor/bin/phpcbf

phpstan:
	php bin/phpstan clear-result-cache -q && php -d memory_limit=448M bin/phpstan

phpstan-result-cache:
	php -d memory_limit=448M bin/phpstan

phpstan-generate-baseline:
	php -d memory_limit=448M bin/phpstan --generate-baseline

phpstan-pro:
	php -d memory_limit=448M bin/phpstan --pro

composer-require-checker:
	php build/composer-require-checker.phar check --config-file $(CURDIR)/build/composer-require-checker.json
