.PHONY: tests

build: cs tests phpstan

tests: install-paratest
	XDEBUG_MODE=off php tests/vendor/bin/paratest --runner WrapperRunner --no-coverage

tests-integration: install-paratest
	php tests/vendor/bin/paratest --runner WrapperRunner --no-coverage --group exec

tests-golden-reflection:
	php vendor/bin/phpunit tests/PHPStan/Reflection/ReflectionProviderGoldenTest.php

lint:
	XDEBUG_MODE=off php vendor/bin/parallel-lint --colors \
		--exclude tests/PHPStan/Analyser/data \
		--exclude tests/PHPStan/Analyser/nsrt \
		--exclude tests/PHPStan/Rules/Methods/data \
		--exclude tests/PHPStan/Rules/Functions/data \
		--exclude tests/PHPStan/Rules/Names/data \
		--exclude tests/PHPStan/Rules/Operators/data/invalid-inc-dec.php \
		--exclude tests/PHPStan/Rules/Arrays/data/offset-access-without-dim-for-reading.php \
		--exclude tests/PHPStan/Rules/Classes/data/bug-11891.php \
		--exclude tests/PHPStan/Rules/Classes/data/bug-13768.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-declarations.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-enum-cases.php \
		--exclude tests/PHPStan/Rules/Classes/data/enum-sanity.php \
		--exclude tests/PHPStan/Rules/Classes/data/extends-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/implements-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/interface-extends-error.php \
		--exclude tests/PHPStan/Rules/Classes/data/trait-use-error.php \
		--exclude tests/PHPStan/Rules/Methods/data/method-in-enum-without-body.php \
		--exclude tests/PHPStan/Rules/Properties/data/default-value-for-native-property-type.php \
		--exclude tests/PHPStan/Rules/Arrays/data/empty-array-item.php \
		--exclude tests/PHPStan/Rules/Classes/data/invalid-promoted-properties.php \
		--exclude tests/PHPStan/Rules/Classes/data/duplicate-promoted-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/default-value-for-promoted-property.php \
		--exclude tests/PHPStan/Rules/Operators/data/invalid-assign-var.php \
		--exclude tests/PHPStan/Rules/Functions/data/arrow-function-nullsafe-by-ref.php \
		--exclude tests/PHPStan/Levels/data/namedArguments.php \
		--exclude tests/PHPStan/Rules/Keywords/data/continue-break.php \
		--exclude tests/PHPStan/Rules/Keywords/data/continue-break-property-hook.php \
		--exclude tests/PHPStan/Rules/Properties/data/invalid-callable-property-type.php \
		--exclude tests/PHPStan/Rules/Properties/data/properties-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/read-only-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/read-only-property-phpdoc-and-native.php   \
		--exclude tests/PHPStan/Rules/Properties/data/read-only-property-readonly-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/overriding-property.php \
		--exclude tests/PHPStan/Rules/Constants/data/overriding-final-constant.php \
		--exclude tests/PHPStan/Rules/Properties/data/intersection-types.php \
		--exclude tests/PHPStan/Rules/Classes/data/first-class-instantiation-callable.php \
		--exclude tests/PHPStan/Rules/Classes/data/instantiation-callable.php \
		--exclude tests/PHPStan/Rules/Classes/data/bug-9402.php \
		--exclude tests/PHPStan/Rules/Constants/data/class-as-class-constant.php \
		--exclude tests/PHPStan/Rules/Constants/data/value-assigned-to-class-constant-native-type.php \
		--exclude tests/PHPStan/Rules/Constants/data/overriding-constant-native-types.php \
		--exclude tests/PHPStan/Rules/Methods/data/bug-10043.php \
		--exclude tests/PHPStan/Rules/Methods/data/bug-7859.php \
		--exclude tests/PHPStan/Rules/Methods/data/bug-8081.php \
		--exclude tests/PHPStan/Rules/Methods/data/bug-9014.php \
		--exclude tests/PHPStan/Rules/Methods/data/bug-10101.php \
		--exclude tests/PHPStan/Rules/Methods/data/final-method-by-phpdoc.php \
		--exclude tests/PHPStan/Rules/Traits/data/conflicting-trait-constants-types.php \
		--exclude tests/PHPStan/Rules/Types/data/invalid-union-with-mixed.php \
		--exclude tests/PHPStan/Rules/Types/data/invalid-union-with-never.php \
		--exclude tests/PHPStan/Rules/Types/data/invalid-union-with-void.php \
		--exclude tests/PHPStan/Rules/Constants/data/dynamic-class-constant-fetch.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-position.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-position2.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-position-nested.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-strict-nonsense.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-strict-nonsense-bool.php \
		--exclude tests/PHPStan/Rules/Keywords/data/declare-inline-html.php \
		--exclude tests/PHPStan/Rules/Classes/data/extends-readonly-class.php \
		--exclude tests/PHPStan/Rules/Classes/data/instantiation-promoted-properties.php \
		--exclude tests/PHPStan/Rules/Classes/data/bug-11592.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-hooks-bodies-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-hooks-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-hooks-visibility-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-hooked-properties-in-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-hooked-properties-with-bodies.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-non-hooked-properties-in-abstract-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/non-abstract-hooked-properties-in-abstract-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/non-abstract-hooked-properties-in-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/hooked-properties-in-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/hooked-properties-without-bodies-in-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/readonly-property-hooks.php \
		--exclude tests/PHPStan/Rules/Properties/data/readonly-property-hooks-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/static-hooked-properties.php \
		--exclude tests/PHPStan/Rules/Properties/data/static-hooked-property-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/virtual-hooked-properties.php \
		--exclude tests/PHPStan/Rules/Classes/data/bug-12281.php \
		--exclude tests/PHPStan/Rules/Traits/data/bug-12281.php \
		--exclude tests/PHPStan/Rules/Classes/data/invalid-hooked-properties.php \
		--exclude tests/PHPStan/Parser/data/cleaning-property-hooks-before.php \
		--exclude tests/PHPStan/Parser/data/cleaning-property-hooks-after.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-private-property-hook.php \
		--exclude tests/PHPStan/Rules/Properties/data/existing-classes-property-hooks.php \
		--exclude tests/PHPStan/Rules/Properties/data/set-property-hook-parameter.php \
		--exclude tests/PHPStan/Rules/Properties/data/overriding-final-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/private-final-property-hooks.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-final-property-hook.php \
		--exclude tests/PHPStan/Rules/Properties/data/final-property-hooks-in-interface.php \
		--exclude tests/PHPStan/Rules/Properties/data/final-property-hooks.php \
		--exclude tests/PHPStan/Rules/Properties/data/final-properties.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-in-interface-explicit-abstract.php \
		--exclude tests/PHPStan/Rules/Constants/data/final-private-const.php \
		--exclude tests/PHPStan/Rules/Properties/data/abstract-final-property-hook-parse-error.php \
		--exclude tests/PHPStan/Rules/Playground/data/promote-missing-override.php \
		--exclude tests/PHPStan/Rules/Traits/data/trait-attributes.php \
		--exclude tests/PHPStan/Rules/Classes/data/non-class-attribute-class.php \
		--exclude tests/PHPStan/Rules/Classes/data/enum-cannot-be-attribute.php \
		--exclude tests/PHPStan/Rules/Classes/data/class-attributes.php \
		--exclude tests/PHPStan/Rules/Classes/data/enum-attributes.php \
		--exclude tests/PHPStan/Rules/Cast/data/void-cast.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-hook-attributes-nodiscard.php \
		--exclude tests/PHPStan/Rules/Functions/data/arrow-function-typehints-nodiscard.php \
		--exclude tests/PHPStan/Rules/Functions/data/closure-typehints-nodiscard.php \
		--exclude tests/PHPStan/Rules/Functions/data/typehints-nodiscard.php \
		--exclude tests/PHPStan/Rules/Methods/data/typehints-nodiscard.php \
		--exclude tests/PHPStan/Rules/Cast/data/deprecated-cast.php \
		--exclude tests/PHPStan/Rules/Classes/data/deprecated-attr-on-class.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-override-attr-missing.php \
		--exclude tests/PHPStan/Rules/Properties/data/override-attr-on-property.php \
		--exclude tests/PHPStan/Rules/Properties/data/property-override-attr.php \
		src tests

install-paratest:
	composer install --working-dir tests

.PHONY: cs-install
cs-install:
	git clone https://github.com/phpstan/build-cs.git || true
	git -C build-cs fetch origin && git -C build-cs reset --hard origin/2.x
	composer install --working-dir build-cs

.PHONY: cs
cs: cs-install
	XDEBUG_MODE=off php build-cs/vendor/bin/phpcs

.PHONY: cs-fix
cs-fix: cs-install
	XDEBUG_MODE=off php build-cs/vendor/bin/phpcbf

phpstan:
	php bin/phpstan clear-result-cache -q && php -d memory_limit=448M bin/phpstan

phpstan-result-cache:
	php -d memory_limit=448M bin/phpstan

phpstan-fix:
	php -d memory_limit=448M bin/phpstan --fix

phpstan-generate-baseline:
	php -d memory_limit=448M bin/phpstan --generate-baseline

phpstan-generate-baseline-php:
	php -d memory_limit=448M bin/phpstan analyse --generate-baseline phpstan-baseline.php

phpstan-pro:
	php -d memory_limit=448M bin/phpstan --pro

name-collision:
	php vendor/bin/detect-collisions --configuration build/collision-detector.json

composer-dependency-analyser:
	php vendor/bin/composer-dependency-analyser --config build/composer-dependency-analyser.php
