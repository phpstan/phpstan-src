<?php

namespace Bug13338;

function &test(): int {
    $x = 0;
    try {
        return $x;
    } finally {
        $x = 'test';
    }
}

$x = &test();
var_dump($x);
