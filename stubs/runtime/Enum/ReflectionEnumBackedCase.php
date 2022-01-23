<?php

if (\PHP_VERSION_ID < 80100) {
	if (class_exists('ReflectionEnumBackedCase', false)) {
		return;
	}

	class ReflectionEnumBackedCase extends ReflectionEnumUnitCase
	{
		public function getBackingValue(): int|string
		{
		}
	}
}
