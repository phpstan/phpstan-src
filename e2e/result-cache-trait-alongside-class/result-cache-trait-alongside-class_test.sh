#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_trait_alongside_class() {
	../../bin/phpstan analyse
	patch -b src/FooTrait.php < changeFooTraitBody.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'FooTrait.php (in context of class ResultCacheE2ETraitAlongsideClass\ClassUsingFooTrait):10:Method ResultCacheE2ETraitAlongsideClass\ClassUsingFooTrait::doFooTrait() should return stdClass but returns Exception. [identifier=return.type]' "$OUTPUT"
}
