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
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
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
	# A warm result cache without the parse error, and src/Other.php with an error, which makes
	# it one of the files that are re-analysed when a symbol appears or disappears.
	cp src/Thing.php "$TMPDIR/BrokenThing.php"
	cp fixed/Thing.php src/Thing.php
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EParseError;\n\nfunction doOther(): int\n{\n\treturn "other";\n}\n' > src/Other.php
	../../bin/phpstan analyse --error-format raw > /dev/null 2>&1 || true
	# Breaking src/Thing.php re-analyses only it: src/User.php keeps its result.
	cp "$TMPDIR/BrokenThing.php" src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	# The first run had nothing to save, so the next one records the file stat of src/Thing.php,
	# and the cache is not rewritten after that.
	../../bin/phpstan analyse --error-format raw > /dev/null 2>&1 || true
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	# Fixing it declares the same symbols as before, so nothing else is re-analysed either.
	cp fixed/Thing.php src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_not_contains 'Syntax error' "$OUTPUT"
	assert_contains 'Other.php:7:Function ResultCacheE2EParseError\doOther() should return int but returns string.' "$OUTPUT"
}

function test_result_cache_parse_error_file_analysed_meanwhile() {
	cp src/Thing.php "$TMPDIR/BrokenThing.php"
	cp fixed/Thing.php src/Thing.php
	../../bin/phpstan analyse --error-format raw > /dev/null 2>&1 || true
	cp "$TMPDIR/BrokenThing.php" src/Thing.php
	../../bin/phpstan analyse --error-format raw > /dev/null 2>&1 || true
	# src/NewUser.php is analysed while src/Thing.php declares nothing, so it finds Thing missing,
	# and it has to be analysed again once src/Thing.php is fixed, even though its symbols are the
	# same as before it broke.
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EParseError;\n\nfunction doNewUser(): string\n{\n\treturn (new Thing())->doThing();\n}\n' > src/NewUser.php
	../../bin/phpstan analyse --error-format raw > /dev/null 2>&1 || true
	cp fixed/Thing.php src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
	assert_contains 'NewUser.php:7:Function ResultCacheE2EParseError\doNewUser() should return string but returns int.' "$OUTPUT"
}
