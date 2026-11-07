#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_value_dependency() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesCache.php:7:Service cache does not exist.' "$OUTPUT"
	assert_contains "UsesLocale.php:10:Function ResultCacheE2EValueDependency\usesLocale() should return 'none' but returns 'cs'." "$OUTPUT"
	assert_contains "UsesLocaleToo.php:10:Function ResultCacheE2EValueDependency\usesLocaleToo() should return 'none' but returns 'cs'." "$OUTPUT"
	assert_contains 'Holder.php:17:Method ResultCacheE2EValueDependency\Holder::isDebug() should return int but returns string.' "$OUTPUT"
	assert_contains 'UsesHolder.php:9:Function ResultCacheE2EValueDependency\usesHolder() should return int but returns string.' "$OUTPUT"
	assert_contains 'UsesMayThrow.php:9:Dead catch - RuntimeException is never thrown in the try block.' "$OUTPUT"
	assert_contains 'UsesRegion.php:7:Region eu is used.' "$OUTPUT"
	assert_contains 'UsesView.php:7:View dashboard does not exist.' "$OUTPUT"
	assert_contains 'UsesMake.php:7:Call to an undefined method object::run().' "$OUTPUT"
	assert_not_contains 'UsesMailer.php' "$OUTPUT"
	# The extensions read container.json, which the result cache knows nothing about. A rule and a
	# dynamic return type extension both declare the service they ask about - it is recorded once.
	# A change of its value re-analyses only the file asking about it.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["services"]["mailer"] = "ResultCacheE2EValueDependency\\Logger"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesMailer.php:7:Call to an undefined method ResultCacheE2EValueDependency\Logger::send().' "$OUTPUT"
	# Values nobody asked about do not re-analyse anything.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["timezone"] = "UTC"; $c["services"]["router"] = "ResultCacheE2EValueDependency\\Logger"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	# A value that was missing is watched too.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["services"]["cache"] = "ResultCacheE2EValueDependency\\Logger"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Service cache does not exist.' "$OUTPUT"
	# Every file asking about the same value is re-analysed.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["locale"] = "en"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesLocale.php:10:Function ResultCacheE2EValueDependency\usesLocale() should return 'none' but returns 'en'." "$OUTPUT"
	assert_contains "UsesLocaleToo.php:10:Function ResultCacheE2EValueDependency\usesLocaleToo() should return 'none' but returns 'en'." "$OUTPUT"
	# Scope::getFile() is not the file to re-analyse when the extension runs outside the walk of the
	# analysed file: the type of Holder::$debug is inferred from the constructor on a scope of
	# src/Holder.php, whenever the property is first needed, and it is cached. So it is what
	# src/Holder.php declares that depends on the value: a change of it re-analyses src/Holder.php and
	# the files depending on it.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["debug"] = "1"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Holder.php' "$OUTPUT"
	# Inside a trait, the value is asked about in the analysis of the class using it - that's the
	# file re-analysed, and the error is reported in the trait.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["services"]["logger"] = "ResultCacheE2EValueDependency\\Mailer"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesLogger.php:7:Call to an undefined method ResultCacheE2EValueDependency\Mailer::log().' "$OUTPUT"
	assert_contains 'LoggerTrait.php (in context of class ResultCacheE2EValueDependency\UsesLoggerTrait):10:Call to an undefined method ResultCacheE2EValueDependency\Mailer::log().' "$OUTPUT"
	# A dynamic throw type extension tracks the values it reads too.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["throwing"] = "yes"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Dead catch' "$OUTPUT"
	# So does a collector: the data it collected is collected again in the analysis of the file.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["region"] = "us"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesRegion.php:7:Region us is used.' "$OUTPUT"
	assert_not_contains 'Region eu is used.' "$OUTPUT"
	# So does a parameter closure this extension: $this in the closure follows the parameter.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["closureThis"] = "ResultCacheE2EValueDependency\\Logger"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesWithThis.php:8:Call to an undefined method ResultCacheE2EValueDependency\Logger::send().' "$OUTPUT"
	# So does a type-specifying extension: the narrowed type follows the parameter.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["allowedClass"] = "ResultCacheE2EValueDependency\\Logger"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesIsAllowed.php:8:Call to an undefined method ResultCacheE2EValueDependency\Logger::send().' "$OUTPUT"
	# A rule tracking a directory: a file the pattern doesn't match changes nothing...
	echo 'notes' > views/notes.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	# ...a matching file appearing re-analyses the file that looked for it.
	echo '<p>dashboard</p>' > views/dashboard.html
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'View dashboard does not exist.' "$OUTPUT"
	# ...and so does a matching file whose contents change.
	echo '<p>dashboard, changed</p>' > views/dashboard.html
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	../../bin/phpstan analyse -vv --error-format raw 2>&1 && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	# A dynamic return type extension tracking a class named in a string: the class appearing - outside
	# the analysed paths, so no fallback for new files kicks in - re-analyses the file naming it...
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EValueDependency\\Lib;\n\nclass Gadget extends Base\n{\n}\n' > lib/Gadget.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'UsesMake.php' "$OUTPUT"
	# ...a method body edited in its parent class does not...
	sed -i.bak 's/return 1;/return 2;/' lib/Base.php && rm lib/Base.php.bak
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	# ...and a signature changed there does.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EValueDependency\\Lib;\n\nclass Base\n{\n\n\tpublic function run(): string\n\t{\n\t\treturn \x27x\x27;\n\t}\n\n}\n' > lib/Base.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesMake.php:7:Function ResultCacheE2EValueDependency\usesMake() should return int but returns string.' "$OUTPUT"
	# A class reflection extension tracking on the class: every file using the class is analysed again,
	# also one that got the magic method from the reflection cache without asking the extension.
	php -r '$c = json_decode(file_get_contents("container.json"), true); $c["parameters"]["greeting"] = "1"; file_put_contents("container.json", json_encode($c, JSON_PRETTY_PRINT));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '3' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesMagic.php:7:Function ResultCacheE2EValueDependency\usesMagic() should return string but returns int.' "$OUTPUT"
	assert_contains 'UsesMagicToo.php:7:Function ResultCacheE2EValueDependency\usesMagicToo() should return string but returns int.' "$OUTPUT"
}
