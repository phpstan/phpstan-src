#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# A rule on CollectedDataNode reports that a call has no effect only when the declaration of the called
# function is analysed too. The collector tracks the file of the declaration, see
# DependencyTracker::trackAnalysedFileDependency().
function test_call_to_pure_function_declared_in_directory_not_analysed() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
}

function test_call_to_pure_function_with_its_declaration() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls srcc 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'function.resultUnused' "$OUTPUT"
	assert_contains '#never in CallsPureFunction# in path' "$OUTPUT"
	# the called function calls a function from src/Pure, which is not analysed
	assert_not_contains 'CallsTransitivelyPureFunction' "$OUTPUT"
}

# The called function is pure only when the function it calls is pure, so the call is reported
# only when both declarations are analysed.
function test_call_to_transitively_pure_function() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls src/Pure 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls src/Pure srcc 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'function.resultUnused' "$OUTPUT"
	assert_contains '#never in CallsPureFunction# in path' "$OUTPUT"
	assert_contains '#never in CallsTransitivelyPureFunction# in path' "$OUTPUT"
}

function test_call_to_pure_function_in_only_files() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls/CallsPureFunction.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls/CallsPureFunction.php srcc/pure-function.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'function.resultUnused' "$OUTPUT"
	assert_contains '#never in CallsPureFunction# in path' "$OUTPUT"
}

# Whether a method is pure depends on the declaration of the function it calls, but that decides only
# whether the calls to the method are reported, in the files of the calls, not what is reported
# in the file declaring the method.
function test_declaration_of_pure_method_without_the_declaration_it_depends_on() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Declares 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in DeclaresPureMethod# in path' "$OUTPUT"
	# the call to its own method is reported only when the function the method calls is analysed too
	assert_not_contains 'CallsOwnPureMethod' "$OUTPUT"
}

function test_declaration_of_pure_method_in_only_files() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Declares/DeclaresPureMethod.php src/Declares/CallsOwnPureMethod.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in DeclaresPureMethod# in path' "$OUTPUT"
	assert_not_contains 'CallsOwnPureMethod' "$OUTPUT"
}

function test_call_to_own_pure_method_with_the_declaration_it_depends_on() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Declares srcc 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'method.resultUnused' "$OUTPUT"
	assert_contains '#never in DeclaresPureMethod# in path' "$OUTPUT"
	assert_contains '#never in CallsOwnPureMethod# in path' "$OUTPUT"
}

# The dependencies of the files restored from the result cache come from the cache.
function test_result_cache_of_call_to_pure_function() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls src/Pure 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	echo '// changed' >> src/Pure/transitively-pure-function.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw src/Calls src/Pure 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
}

# Whether a call with a named argument is reported depends on the subclasses renaming the parameter,
# which can be anywhere, see DependencyTracker::trackAnalysedProjectDependency().
function test_named_argument_depends_on_the_whole_project() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Named srcc 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'CallsWithNamedArgument' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'argument.parameterRenamedInSubtype' "$OUTPUT"
	assert_contains '#never in CallsWithNamedArgument# in path' "$OUTPUT"
	assert_contains '#never in CallsPureFunction# in path' "$OUTPUT"
	assert_contains '#never in CallsTransitivelyPureFunction# in path' "$OUTPUT"
}
