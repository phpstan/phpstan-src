#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_file_dependency() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesFunction.php:10:Function ResultCacheE2EFileDependency\usesFunction() should return 'none' but returns 'function-1'." "$OUTPUT"
	assert_contains "UsesMethod.php:10:Function ResultCacheE2EFileDependency\usesMethod() should return 'none' but returns 'method-1'." "$OUTPUT"
	assert_contains "UsesStaticMethod.php:10:Function ResultCacheE2EFileDependency\usesStaticMethod() should return 'none' but returns 'static-method-1'." "$OUTPUT"
	assert_contains 'Holder.php:17:Method ResultCacheE2EFileDependency\Holder::get() should return int but returns string.' "$OUTPUT"
	assert_contains 'UsesHolder.php:9:Function ResultCacheE2EFileDependency\usesHolder() should return int but returns string.' "$OUTPUT"
	assert_contains 'UsesDeprecatedConfig.php:7:Deprecated config asks for an error.' "$OUTPUT"
	assert_not_contains 'Config asks for an error.' "$OUTPUT"
	# The paths declared with the deprecated RuleErrorBuilder::fileDependency() are declared on the
	# scope of the rule's node.
	printf 'quiet\n' > data/deprecated.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Deprecated config asks for an error.' "$OUTPUT"
	# A rule declares its dependency on src/Config.php whether it reports an error or not. The file
	# declares no symbols, so no change of it reaches its exported nodes - any change re-analyses the
	# files of the rule's nodes anyway, next to the changed file itself: src/UsesConfig.php and
	# src/ConfigHolder.php. Not src/UsesConfigHolder.php depending on src/ConfigHolder.php - what
	# src/ConfigHolder.php declares did not change.
	printf "<?php\n\nreturn 'report';\n" > src/Config.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '3' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesConfig.php:7:Config asks for an error.' "$OUTPUT"
	assert_contains 'ConfigHolder.php:10:Config asks for an error.' "$OUTPUT"
	printf "<?php\n\n// a change that keeps the error\nreturn 'report';\n" > src/Config.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '3' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesConfig.php:7:Config asks for an error.' "$OUTPUT"
	# Dynamic return type extensions of a function, a method and a static method declared in
	# src/functions.php and src/Repository.php read a data file each. A change of one re-analyses the
	# file with the call - not the one declaring the function or the method.
	printf 'function-2\n' > data/function.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesFunction.php:10:Function ResultCacheE2EFileDependency\usesFunction() should return 'none' but returns 'function-2'." "$OUTPUT"
	printf 'method-2\n' > data/method.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesMethod.php:10:Function ResultCacheE2EFileDependency\usesMethod() should return 'none' but returns 'method-2'." "$OUTPUT"
	printf 'static-method-2\n' > data/static-method.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesStaticMethod.php:10:Function ResultCacheE2EFileDependency\usesStaticMethod() should return 'none' but returns 'static-method-2'." "$OUTPUT"
	# Scope::getFile() is not the file to re-analyse when the extension runs outside the walk of the
	# analysed file: the type of Holder::$value is inferred from the constructor on a scope of
	# src/Holder.php, whenever the property is first needed - in the analysis of src/Holder.php, or of
	# src/UsesHolder.php reading it - and it is cached. So it is what src/Holder.php declares that
	# depends on data/holder.txt: a change of it re-analyses src/Holder.php and the files depending on it.
	printf 'int\n' > data/holder.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Holder.php:17:Method ResultCacheE2EFileDependency\Holder::get() should return int but returns string.' "$OUTPUT"
	assert_not_contains 'UsesHolder.php:9:Function ResultCacheE2EFileDependency\usesHolder() should return int but returns string.' "$OUTPUT"
	# Deleting a declared file re-analyses the files depending on it too, and it stays watched while
	# it is missing: creating it again re-analyses them again.
	rm data/function.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesFunction.php:10:Function ResultCacheE2EFileDependency\usesFunction() should return 'none' but returns 'missing'." "$OUTPUT"
	printf 'function-3\n' > data/function.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains "UsesFunction.php:10:Function ResultCacheE2EFileDependency\usesFunction() should return 'none' but returns 'function-3'." "$OUTPUT"
	# The same for what src/Holder.php declares.
	rm data/holder.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Holder.php:17:Method ResultCacheE2EFileDependency\Holder::get() should return int but returns string.' "$OUTPUT"
	assert_contains 'UsesHolder.php:9:Function ResultCacheE2EFileDependency\usesHolder() should return int but returns string.' "$OUTPUT"
	printf 'int\n' > data/holder.txt
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Holder.php:17:Method ResultCacheE2EFileDependency\Holder::get() should return int but returns string.' "$OUTPUT"
	assert_not_contains 'UsesHolder.php:9:Function ResultCacheE2EFileDependency\usesHolder() should return int but returns string.' "$OUTPUT"
	# And for a declared file that is analysed itself: deleted, it is no longer analysed, and the files
	# of the rule's nodes are re-analysed; created again, all three are - but not the three files with
	# errors, the file declares no symbol they could be waiting for.
	rm src/Config.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Config asks for an error.' "$OUTPUT"
	printf "<?php\n\nreturn 'report';\n" > src/Config.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals '3' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesConfig.php:7:Config asks for an error.' "$OUTPUT"
}
