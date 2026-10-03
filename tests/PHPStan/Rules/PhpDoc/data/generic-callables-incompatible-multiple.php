<?php

namespace GenericCallablesIncompatibleMultiple;

/**
 * @param array{callable<T of InvalidA>(T): void, callable<U of InvalidB>(U): void} $callables
 */
function testMultipleCallables(array $callables): void
{
}
