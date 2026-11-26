<?php

namespace Bug15496;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

final class Line
{
	public int $quantity = 1;
}

final class Order
{
	public array $lines = [];

	public array $fallbackLines = [];
}

/** @param list<Order> $orders */
function test(array $orders, Order $native): void
{
	$order = $orders[0];
	foreach ($order->lines as $line) {
		assertType('Bug15496\Line', $line);
		assertNativeType('mixed', $line);
	}

	foreach ($native->lines as $line) {
		assertType('Bug15496\Line', $line);
		assertNativeType('Bug15496\Line', $line);
	}
}

/** @param Order $order */
function testNativeOnly(object $order): void
{
	foreach ($order->fallbackLines as $line) {
		assertType('mixed', $line);
		assertNativeType('Bug15496\Line', $line);
	}
}
