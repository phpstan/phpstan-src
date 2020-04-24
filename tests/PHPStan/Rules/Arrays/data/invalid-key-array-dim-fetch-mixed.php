<?php

namespace InvalidKeyArrayDimFetchMixed;

$a = [];

/** @var mixed $foo */
$foo = doFoo();
$bar = doFoo();

$a[$foo];
$a[$bar];
