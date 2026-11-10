#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_parse_error() {
	# src/Thing.php has a parse error, and src/User.php uses the class it would declare. The parse
	# error carries an exception, so src/Thing.php is left out of the result cache, but the rest
	# of it is saved.
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	test -f tmp/resultCache.php
	# Only src/Thing.php is analysed again, and the result cache is not rewritten.
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	# Fixing the parse error declares Thing, so the error that src/User.php had in the result
	# cache goes away.
	cp fixed/Thing.php src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'not found' "$OUTPUT"
}

function test_result_cache_parse_error_keeps_symbols() {
	# A warm result cache without the parse error. src/User.php uses Thing, and src/Other.php has
	# an error, which makes it one of the files re-analysed when a symbol appears or disappears.
	cp src/Thing.php "$TMPDIR/BrokenThing.php"
	cp fixed/Thing.php src/Thing.php
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EParseError;\n\nfunction doOther(): int\n{\n\treturn "other";\n}\n' > src/Other.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# Breaking src/Thing.php re-analyses only it, not src/User.php and src/Other.php.
	cp "$TMPDIR/BrokenThing.php" src/Thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	# Saving it broken differently does not either.
	printf '\n// still broken\n' >> src/Thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# Fixing it declares the same symbols as before it broke, so it is re-analysed alone too. A
	# comment makes the file differ from what it was before it broke: the cache may still hold
	# that version, if no run had a reason to rewrite it, and then nothing is re-analysed.
	{ cat fixed/Thing.php; printf '\n// fixed\n'; } > src/Thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
	assert_contains 'Other.php:7:Function ResultCacheE2EParseError\doOther() should return int but returns string.' "$OUTPUT"
}

function test_result_cache_parse_error_file_analysed_meanwhile() {
	cp src/Thing.php "$TMPDIR/BrokenThing.php"
	cp fixed/Thing.php src/Thing.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	cp "$TMPDIR/BrokenThing.php" src/Thing.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# src/NewUser.php is analysed while src/Thing.php declares nothing, so it finds Thing missing.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EParseError;\n\nfunction doNewUser(): string\n{\n\treturn (new Thing())->doThing();\n}\n' > src/NewUser.php
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	# Fixing src/Thing.php makes its symbols appear again even though they are the same as before
	# it broke: it is re-analysed with src/User.php using it and src/NewUser.php, which has an error.
	cp fixed/Thing.php src/Thing.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '3' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
	assert_contains 'NewUser.php:7:Function ResultCacheE2EParseError\doNewUser() should return string but returns int.' "$OUTPUT"
}
