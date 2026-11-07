#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# @tag own-setup
function test_pcov_loaded_restarts() {
	../../bin/phpstan analyse --no-progress -vvv 2>&1 | tee -a output.txt
	assert_file_contains output.txt "Restarting process: pcov (pcov.enabled=1)"
}

# @tag own-setup
function test_pcov_enabled_restarts() {
	../../bin/phpstan analyse --no-progress -vvv 2>&1 | tee -a output.txt
	assert_file_contains output.txt "Restarting process: pcov (pcov.enabled=1)"
}

# @tag own-setup
function test_pcov_disabled_does_not_restart_for_pcov() {
	# the restart may still happen to activate OPcache, just not for pcov
	../../bin/phpstan analyse --no-progress -vvv 2>&1 | tee -a output.txt
	assert_file_not_contains output.txt "pcov (pcov.enabled"
}
