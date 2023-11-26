<?php declare(strict_types = 1);

namespace Bug6720;

/** @return string|void */
function a() {}

function b(?string $a): void {}

$a = a();
b($a);
