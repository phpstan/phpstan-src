#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_moved_phpstan_installation() {
	# The reflection cache for PHP built-in symbols stores absolute paths to the
	# PhpStorm stub files, but its cache key contains only package versions. The
	# cache in sys_get_temp_dir() survives moving the PHPStan installation (for
	# example when the project with PHPStan in vendor/ is moved or the CI
	# workspace path changes), so the next run must not crash importing
	# reflections that point to the previous installation path.
	cd ../..
	cp -R . "$TMPDIR"/phpstan-install-1
	cd e2e/moved-phpstan-installation
	"$TMPDIR"/phpstan-install-1/bin/phpstan analyse -l 8 -vvv test.php
	mv "$TMPDIR"/phpstan-install-1 "$TMPDIR"/phpstan-install-2
	"$TMPDIR"/phpstan-install-2/bin/phpstan analyse -l 8 -vvv test.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
