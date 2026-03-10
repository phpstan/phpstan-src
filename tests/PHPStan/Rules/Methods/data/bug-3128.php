<?php declare(strict_types = 1);

namespace Bug3128;

use stdClass;

final class HelloWorld
{
	/**
	 * @param array|stdClass[] $foo
	 */
	public function addTos($foo): void {
	}

	/**
	 * @param stdClass[]|array $bar
	 */
	public function addTosReversed($bar): void {
	}
}

$a = new HelloWorld();
$a->addTos([1]);
$a->addTosReversed([1]);
