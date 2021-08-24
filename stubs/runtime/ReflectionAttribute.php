<?php

if (\PHP_VERSION_ID < 80000) {
	if (class_exists('ReflectionAttribute', false)) {
		return;
	}

	final class ReflectionAttribute
	{
		public function getName(): string
		{
		}

		public function getTarget(): int
		{
		}

		public function isRepeated(): bool
		{
		}

		public function getArguments(): array
		{
		}

		public function newInstance(): object
		{
		}

		private function __clone()
		{
		}

		private function __construct()
		{
		}
	}
}
