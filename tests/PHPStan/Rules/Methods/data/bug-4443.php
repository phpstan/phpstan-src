<?php

namespace Bug4443;

class HelloWorld
{
	/** @var array<mixed> */
	private static ?array $arr = null;

	private static function setup(): void
	{
		self::$arr = null;
	}

	/** @return array<mixed> */
	public static function getArray(): array
	{
		if (self::$arr === null) {
			self::$arr = [];
			self::setup();
		}
		return self::$arr;
	}
}

HelloWorld::getArray();
