#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug10449b() {
	../../bin/phpstan analyze
	git apply patch.diff
	rm phpstan-baseline.neon
	mv after-phpstan-baseline.neon phpstan-baseline.neon
	../../bin/phpstan analyze -vvv && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
