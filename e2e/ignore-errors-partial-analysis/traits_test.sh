#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# The error in a trait is reported in the file of each analysed class using it, and only
# when the trait is analysed too. An ignore for the class file is matched then.
function test_class_and_its_trait_analysed() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes src/Traits src/Nested 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'UsesSharedTrait.php was not matched' "$OUTPUT"
	assert_not_contains 'UsesNestedTrait.php was not matched' "$OUTPUT"
	assert_contains '#never in Classes directory# in path' "$OUTPUT"
	# the trait is analysed only in the context of the classes using it, and not all of them are analysed
	assert_not_contains '#never in SharedTrait#' "$OUTPUT"
	# a trait out of the analysed paths of the project reports nothing even in the whole project
	assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
}

function test_class_analysed_but_not_its_trait() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'UsesSharedTrait.php' "$OUTPUT"
	assert_not_contains 'UsesNestedTrait.php' "$OUTPUT"
	assert_not_contains '#never in Classes directory#' "$OUTPUT"
	assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
}

function test_class_and_its_trait_analysed_but_not_the_trait_the_trait_uses() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes src/Traits 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'UsesSharedTrait.php was not matched' "$OUTPUT"
	assert_not_contains 'UsesNestedTrait.php' "$OUTPUT"
	assert_not_contains '#never in Classes directory#' "$OUTPUT"
}

function test_trait_analysed_but_not_the_classes_using_it() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Traits 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_not_contains '#never in SharedTrait#' "$OUTPUT"
}

function test_whole_project() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'UsesSharedTrait.php was not matched' "$OUTPUT"
	assert_not_contains 'TestUsesSharedTrait.php was not matched' "$OUTPUT"
	assert_not_contains 'UsesNestedTrait.php was not matched' "$OUTPUT"
	assert_contains '#never in SharedTrait# in path' "$OUTPUT"
	assert_contains '#never in Classes directory# in path' "$OUTPUT"
	assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
	assert_not_contains 'UsesSharedTraitWithOwnError.php' "$OUTPUT"
	assert_not_contains 'EnumUsesSharedTrait.php' "$OUTPUT"
	assert_not_contains 'AnonymousUsesSharedTrait.php' "$OUTPUT"
	assert_contains '#never in ChildOfTraitUser# in path' "$OUTPUT"
	assert_contains '#never in UsesExcludedTrait# in path' "$OUTPUT"
	assert_contains '#never in ClassAndTrait# in path' "$OUTPUT"
}

# An entry with count is matched by the class's own error, but not by the error of its trait.
function test_count_matched_only_by_the_class_without_its_trait() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'UsesSharedTraitWithOwnError.php' "$OUTPUT"
}

function test_enum_and_anonymous_class_analysed_but_not_their_trait() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'EnumUsesSharedTrait.php' "$OUTPUT"
	assert_not_contains 'AnonymousUsesSharedTrait.php' "$OUTPUT"
}

# A trait excluded from the analysis reports no errors even when the whole project is analysed.
function test_class_using_trait_excluded_from_analysis() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in UsesExcludedTrait# in path' "$OUTPUT"
}

# The errors of a parent's trait are reported in the parent class, not in the child, so the child is analysed
# entirely even when its parent's trait is not.
function test_child_of_class_using_trait() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Inherit 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in ChildOfTraitUser# in path' "$OUTPUT"
	assert_not_contains 'UsesSharedTrait.php' "$OUTPUT"
}

# The trait is analysed in the context of its users, which are not known, so neither is what is reported in the file.
function test_file_declaring_class_and_trait() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Mixed 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains '#never in ClassAndTrait#' "$OUTPUT"
}

# Which files declare a trait is known on every level, not only on the ones where NotAnalysedTraitRule needs it.
function test_file_declaring_trait_on_level_0() {
	OUTPUT=$(../../bin/phpstan analyse -l 0 --error-format raw src/Mixed src/Traits 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains '#never in ClassAndTrait#' "$OUTPUT"
	assert_not_contains '#never in SharedTrait#' "$OUTPUT"
}

# Without a level only the custom rules run, but the traits of the analysed files are still known, also from the result cache.
function test_traits_with_custom_ruleset() {
	for RUN in 1 2; do
		OUTPUT=$(../../bin/phpstan analyse -c phpstan-custom-ruleset.neon -vv --error-format raw src/Classes src/Mixed 2>&1) && CODE=0 || CODE=$?
		echo "$OUTPUT"
		assert_same "1" "$CODE"
		assert_not_contains 'UsesSharedTrait.php' "$OUTPUT"
		assert_not_contains 'UsesNestedTrait.php' "$OUTPUT"
		assert_not_contains '#never in ClassAndTrait#' "$OUTPUT"
		assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
	done
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
}

# The trait files a file restored from the result cache uses, and whether it declares a trait, come from the cache.
# The cache is not used when the analysed paths change, so it is the same partial analysis again.
function test_result_cache_of_the_same_partial_analysis() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes src/Mixed 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	echo '// changed' >> src/Classes/UsesLibTrait.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw src/Classes src/Mixed 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_not_contains 'UsesSharedTrait.php' "$OUTPUT"
	assert_not_contains 'UsesNestedTrait.php' "$OUTPUT"
	assert_not_contains 'UsesSharedTraitWithOwnError.php' "$OUTPUT"
	assert_not_contains '#never in Classes directory#' "$OUTPUT"
	assert_not_contains '#never in ClassAndTrait#' "$OUTPUT"
	assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
	assert_contains '#never in UsesExcludedTrait# in path' "$OUTPUT"
}
