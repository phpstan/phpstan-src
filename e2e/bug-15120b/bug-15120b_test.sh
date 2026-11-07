#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15120b() {
	# Restoring the result cache fetches the exported nodes of every changed file,
	# and resolving their PHPDocs asks the ReflectionProvider for classes. That
	# reaches StubPhpDocProvider, which runs the StubFilesExtensions - all in the
	# main process, before the deferred bootstrapFiles have run.
	composer install
	../../bin/phpstan analyse -vvv
	# a changed file using a trait - the name scope map reflects the trait
	patch -b src/Consumer.php < source-change.patch
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1 || true)
	echo "$OUTPUT"
	assert_not_contains 'Undefined constant' "$OUTPUT"
	assert_contains 'No errors' "$OUTPUT"
	mv src/Consumer.php.orig src/Consumer.php
	../../bin/phpstan analyse -vvv
	# a changed file with a generic @template bound - resolving it reflects the
	# bound's class to learn whether it is generic
	patch -b src/Repository.php < generic-change.patch
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1 || true)
	echo "$OUTPUT"
	assert_not_contains 'Undefined constant' "$OUTPUT"
	assert_contains 'No errors' "$OUTPUT"
	mv src/Repository.php.orig src/Repository.php
	../../bin/phpstan analyse -vvv
	# a changed file with a non-private property - exporting it asks whether the
	# property is virtual
	patch -b src/Entity.php < property-change.patch
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1 || true)
	echo "$OUTPUT"
	assert_not_contains 'Undefined constant' "$OUTPUT"
	assert_contains 'No errors' "$OUTPUT"
	mv src/Entity.php.orig src/Entity.php
	../../bin/phpstan analyse -vvv
}
