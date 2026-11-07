#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned_exported_nodes() {
	# src/Bar.php refers to a class that does not exist yet, so it has an error from the start.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Bar.php:10:Instantiated class ResultCacheE2EScannedExportedNodes\NewDep not found.' "$OUTPUT"
	# An edit inside a method body of a scanned file changes nothing the files depending on it
	# can see, so nothing is re-analysed - not the file depending on it, and not the file
	# with the error either. The same as for an analysed file, where only a change in the
	# exported nodes (a signature, a PHPDoc, a visibility) reaches the dependents.
	patch -b scanned/Dep.php < body.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	assert_not_contains 'Scanned files with changed symbols' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	# The new contents are recorded: the next run has nothing to notice.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Scanned files with changed symbols' "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	# A changed return type is an exported node change: the file depending on it is
	# re-analysed. A changed signature cannot make a new symbol available, so the file with
	# the error is left alone.
	mv scanned/Dep.php.orig scanned/Dep.php
	patch -b scanned/Dep.php < signature.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Foo.php:10:Method ResultCacheE2EScannedExportedNodes\Foo::doFoo() should return int but returns string.' "$OUTPUT"
	assert_contains 'Bar.php:10:Instantiated class ResultCacheE2EScannedExportedNodes\NewDep not found.' "$OUTPUT"
	# A file without any exported nodes appearing in the scanned directory cannot define the
	# missing symbol: nothing is re-analysed.
	mv new-file/NoSymbols.php scanned/NoSymbols.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	assert_contains 'Bar.php:10:Instantiated class ResultCacheE2EScannedExportedNodes\NewDep not found.' "$OUTPUT"
	# A file declaring a class may define a symbol reported as unknown, so the files with
	# errors are re-analysed - both of them - and the error about the class is gone.
	mv new-file/NewDep.php scanned/NewDep.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 2 files will be reanalysed.' "$OUTPUT"
	assert_not_contains 'Bar.php:10:Instantiated class ResultCacheE2EScannedExportedNodes\NewDep not found.' "$OUTPUT"
	assert_contains 'Foo.php:10:Method ResultCacheE2EScannedExportedNodes\Foo::doFoo() should return int but returns string.' "$OUTPUT"
}
