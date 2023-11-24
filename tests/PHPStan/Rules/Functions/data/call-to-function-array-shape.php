<?php declare(strict_types=1);

namespace CallToFunctionArrayShape;

/** @param array{opt?: int, req: int} $a */
function test(array $a): void
{}

test(['foo' => 1, 'req' => 1]);
test(['foo' => 1]);
test(rand() ? ['foo' => 1] : ['req' => 1, 'foo2' => 2]);
