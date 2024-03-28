<?php

function doFoo(mixed $filter): void {
    \PHPStan\Testing\assertType('non-falsy-string|false', filter_var("no", FILTER_VALIDATE_REGEXP));
}
