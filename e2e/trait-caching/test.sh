#!/bin/bash

function set_up() {
    git restore . 2>&1
    git clean -fd 2>&1
}

function test_trait_caching() {
    ../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
    assert_successful_code

    ../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
    assert_successful_code
} 2>&1

function test_trait_caching_one_changed() {
    ../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
    assert_successful_code

    patch -b data/TraitOne.php < TraitOne.patch

    OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ || true)
    echo "$OUTPUT"
    [ $(echo "$OUTPUT" | wc -l) -eq 1 ]
    assert_successful_code

    assert_contains 'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.' "$OUTPUT"
} 2>&1

function test_trait_caching_two_changed() {
    ../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
    assert_successful_code

    patch -b data/TraitTwo.php < TraitTwo.patch

    OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ || true)
    echo "$OUTPUT"
    [ $(echo "$OUTPUT" | wc -l) -eq 1 ]
    assert_successful_code

    assert_contains 'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.' "$OUTPUT"
} 2>&1

function test_trait_caching_both_changed() {
    ../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
    assert_successful_code

    patch -b data/TraitOne.php < TraitOne.patch
    patch -b data/TraitTwo.php < TraitTwo.patch

    OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ || true)
    echo "$OUTPUT"
    [ $(echo "$OUTPUT" | wc -l) -eq 2 ]
    assert_successful_code

    assert_contains 'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.' "$OUTPUT"
    assert_contains 'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.' "$OUTPUT"
} 2>&1
