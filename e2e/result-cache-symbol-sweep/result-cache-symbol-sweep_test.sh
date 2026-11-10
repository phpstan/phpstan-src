#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function assert_same_as_cold_run() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
}

function test_inline_ignored_error_waits_for_the_symbol() {
	# src/User.php has no error left after its ignore, but the ignored error waits for the class.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolSweep;\n\nfunction doUser(): object\n{\n\treturn new Thing(); // @phpstan-ignore class.notFound\n}\n' > src/User.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# Declaring it re-analyses src/User.php too, where the ignore now matches nothing.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolSweep;\n\nclass Thing\n{\n\n}\n' > src/Thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	assert_same_as_cold_run
	assert_contains 'User.php:7:No error with identifier class.notFound is reported on line 7.' "$OUTPUT"
}

function test_inline_ignored_error_waits_for_a_symbol_in_an_existing_file() {
	# src/User.php has no error left after its ignore, but the ignored error waits for the class.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolSweep;\n\nfunction doUser(): object\n{\n\treturn new Thing(); // @phpstan-ignore class.notFound\n}\n' > src/User.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# The same when the class is declared in a file that already exists.
	printf '\nclass Thing\n{\n\n}\n' >> src/Other.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	assert_same_as_cold_run
	assert_contains 'User.php:7:No error with identifier class.notFound is reported on line 7.' "$OUTPUT"
}

function test_deleted_file_removes_a_symbol() {
	# src/Caller.php has an error because App\doThing() exists, but it does not depend on its file:
	# the function is only named in a string.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolSweep;\n\nfunction doThing(): void\n{\n}\n' > src/thing.php
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolSweep;\n\nfunction doCaller(): ?\\ReflectionFunction\n{\n\ttry {\n\t\treturn new \\ReflectionFunction('"'"'ResultCacheE2ESymbolSweep\\\\doThing'"'"');\n\t} catch (\\ReflectionException $e) {\n\t\treturn null;\n\t}\n}\n' > src/Caller.php
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) || true
	echo "$OUTPUT"
	assert_contains 'Caller.php:9:Dead catch - ReflectionException is never thrown in the try block.' "$OUTPUT"
	# Deleting the file re-analyses src/Caller.php, the file with an error.
	rm src/thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	assert_same_as_cold_run
	assert_not_contains 'Dead catch' "$OUTPUT"
}
