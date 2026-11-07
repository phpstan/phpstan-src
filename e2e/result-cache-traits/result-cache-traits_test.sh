#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_traits() {
	../../bin/phpstan analyse
	patch -b src/FooTrait.php < renameFooTraitMethod.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'ClassMentioningClassUsingBarTrait.php:10:Call to an undefined method ResultCacheE2ETraits\ClassUsingBarTrait::doFooTrait(). [identifier=method.notFound]' "$OUTPUT"
}
