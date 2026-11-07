#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15184() {
	composer install
	# The crash needs OPcache: forked workers switch it off, the --debug main process and
	# spawned workers (pcntl_fork disabled, as on Windows) keep it on.
	php -r 'if (!extension_loaded("Zend OPcache")) { fwrite(STDERR, "OPcache is not loaded, so this step cannot reproduce the crash.\n"); exit(1); }'
	../../bin/phpstan analyze --debug
	php -d disable_functions=pcntl_fork ../../bin/phpstan analyze && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
