<?php declare(strict_types = 1);

namespace Bug3616;

class Factory {
	public static function a(): void { echo 'A'; }
	public static function b(): void { echo 'B'; }
}

class HelloWorld
{
	/** @var array<string, callable():void> */
	const FACTORIES = [
		'a' => [Factory::class, 'a'],
		'b' => [Factory::class, 'b']
	];

	public function withLiteral(): void
	{
		(self::FACTORIES['a'])();
	}

	public function withVariable(string $id): void
	{
		if (!isset(self::FACTORIES[$id])) {
			return;
		}

		(self::FACTORIES[$id])();
	}
}

class HelloWorld2
{
	const FACTORIES = [
		'a' => [Factory::class, 'a'],
		'b' => [Factory::class, 'b']
	];

	public function withLiteral(): void
	{
		(self::FACTORIES['a'])();
	}

	public function withVariable(string $id): void
	{
		if (!isset(self::FACTORIES[$id])) {
			return;
		}

		(self::FACTORIES[$id])();
	}
}
