#!/bin/bash

function test_result_cache1() {
    echo -n > phpstan-baseline.neon
    ../../bin/phpstan -vvv
    assert_successful_code

    patch -b src/Bar.php < patch-1.patch
    cat baseline-1.neon > phpstan-baseline.neon
    ../../bin/phpstan -vvv
    assert_successful_code

    mv src/Bar.php.orig src/Bar.php
    echo -n > phpstan-baseline.neon
    ../../bin/phpstan -vvv
    assert_successful_code
} 2>&1
