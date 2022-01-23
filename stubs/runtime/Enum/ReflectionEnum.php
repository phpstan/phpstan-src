<?php

if (\PHP_VERSION_ID < 80100) {
	if (class_exists('ReflectionEnum', false)) {
		return;
	}

	class ReflectionEnum extends ReflectionClass
	{
		public function __construct(object|string $objectOrClass) {}

		public function hasCase(string $name): bool
		{
		}

		public function getCases(): array
		{
		}

		public function getCase(string $name): ReflectionEnumUnitCase
		{
		}

		public function isBacked(): bool
		{
		}

		public function getBackingType(): ?ReflectionType
		{
		}
	}
}
