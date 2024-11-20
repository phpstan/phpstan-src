<?php

namespace FinalPrivateMethodPhpVersions;

if (PHP_VERSION_ID >= 80000) {
	class FooBarPhp8orHigher
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID < 80000) {
	class FooBarPhp7
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID > 70400) {
	class FooBarPhp74OrHigher
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 80100) {
	class FooBarBaz
	{

		final private function foo(): void
		{
		}
	}
}
