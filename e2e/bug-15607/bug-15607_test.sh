#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15607() {
	# https://github.com/phpstan/phpstan/issues/15607
	# round() refers to the RoundingMode enum. Its cached reflection holds the
	# stub file path not only of the enum, but also of its synthesized cases()
	# method. The cache in sys_get_temp_dir() survives moving the PHPStan
	# installation, so the next run must not crash importing the enum with a
	# path that points to the previous installation.
	cd ../..
	cp -R . "$TMPDIR"/phpstan-install-1
	cd e2e/bug-15607
	"$TMPDIR"/phpstan-install-1/bin/phpstan analyse -l 8 --debug test.php
	mv "$TMPDIR"/phpstan-install-1 "$TMPDIR"/phpstan-install-2
	"$TMPDIR"/phpstan-install-2/bin/phpstan analyse -l 8 --debug test.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
