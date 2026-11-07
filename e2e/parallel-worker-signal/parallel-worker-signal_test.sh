#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_parallel_worker_signal() {
	# A parallel worker killed by a signal writes nothing of its own -
	# no PHP error, no shutdown function, an empty capture file. The
	# main process has to name the signal; treating the dead worker
	# like one that quit cleanly leaves the whole run with nothing but
	# "Some parallel worker jobs have not finished", which is what made
	# https://github.com/phpstan/phpstan/issues/15131 undiagnosable.
	OUTPUT=$(../../bin/phpstan analyse --no-progress 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	# Not echoed: bashunit reports a test whose output says "killed" as an error,
	# whatever its assertions found.
	assert_contains 'killed by signal 9 (SIGKILL)' "$OUTPUT"
}
