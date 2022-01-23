<?php

if (\PHP_VERSION_ID < 80100) {
	if (interface_exists('BackedEnum', false)) {
		return;
	}

	interface BackedEnum extends UnitEnum
	{
		public static function from(int|string $value): static;

		public static function tryFrom(int|string $value): ?static;
	}
}
