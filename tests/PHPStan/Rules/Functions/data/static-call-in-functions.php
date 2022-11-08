<?php

namespace StaticCallsInFunctions;

class HelloWorld
{
	/** @var \Closure[] */
	private static $resolvers;

	/** @return \Closure[] */
	public static function getResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'a' => static function ($one, $two) {
					return $one ?? $two;
				},
				'b' => static function ($one, $two, $three) {
					return $one ?? $two ?? $three;
				},
			];

			foreach (['c', 'd', 'e'] as $name) {
				self::$resolvers[$name] = static function ($one, $two) {
					return self::$resolvers['a']($one, $two);
				};

				self::$resolvers[$name] = static fn ($one, $two) => self::$resolvers['a']($one, $two);
			}
		}

		return self::$resolvers;
	}
}
