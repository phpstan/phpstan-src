#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# @tag own-setup
function test_timecop() {
	cd ../..
	bin/phpstan analyse -l 8 -a tests/e2e/data/timecop.php -c tests/e2e/data/empty.neon tests/e2e/data/timecop.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag own-setup
function test_soap_with_extension() {
	cd ../..
	bin/phpstan analyse -l 8 -a tests/e2e/data/soap.php -c tests/e2e/data/empty.neon tests/e2e/data/soap.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_soap_without_extension() {
	cd ../..
	bin/phpstan analyse -l 8 -a tests/e2e/data/soap.php -c tests/e2e/data/empty.neon tests/e2e/data/soap.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag own-setup
function test_curl_setopt_without_curl() {
	cd ../..
	bin/phpstan analyse -l 8 -c tests/e2e/data/empty.neon tests/e2e/data/curl_setopt.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_anonymous_class_granularity() {
	cd ../..
	bin/phpstan analyse -l 8 tests/e2e/anon-class/Granularity.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag own-setup
function test_glibc_with_musl_loader_picks_glibc_turbo_build() {
	# A glibc system can carry a musl loader without being a musl system:
	# musl-tools, a musl cross-compilation sysroot, or anything else built
	# against musl installs /lib/ld-musl-<arch>.so.1 as well. PHPStan must
	# still pick the glibc turbo build there - picking the musl one makes
	# PHP refuse to load the extension at startup: "Unable to load dynamic
	# library '.../linux-musl-x86_64/phpstan_turbo-8.5.so'
	# (libc.musl-x86_64.so.1: cannot open shared object file)".
	cd ../..
	sudo touch /lib/ld-musl-x86_64.so.1
	OUTPUT=$(bin/phpstan diagnose -l 0 -c tests/e2e/data/empty.neon)
	echo "$OUTPUT"
	assert_contains 'Turbo platform: linux-gnu-' "$OUTPUT"
}

function test_musl_picks_musl_turbo_build() {
	# The mirror image of the leg above: an actual musl system must keep
	# picking the musl build - both on Alpine, and (since /etc/alpine-release
	# is not what makes a system musl) with that file taken away. The images
	# ship no php.ini, so memory_limit would default to 128M, which building
	# the DI container already exceeds.
	cd ../..
	DIAGNOSE='php -d memory_limit=-1 bin/phpstan diagnose -l 0 -c tests/e2e/data/empty.neon'
	OUTPUT=$(docker run --rm -v "$PWD:/repo" -w /repo php:8.2-cli-alpine sh -c "$DIAGNOSE")
	echo "$OUTPUT"
	assert_contains 'Turbo platform: linux-musl-' "$OUTPUT"
	OUTPUT=$(docker run --rm -v "$PWD:/repo" -w /repo php:8.2-cli-alpine sh -c "rm /etc/alpine-release && $DIAGNOSE")
	echo "$OUTPUT"
	assert_contains 'Turbo platform: linux-musl-' "$OUTPUT"
}
