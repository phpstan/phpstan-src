<?php declare(strict_types = 1);

namespace Bug12094;

class HelloWorld
{
	public function classExistsWithVariable(string $cls): bool
	{
		if (class_exists($cls, false)) {
			return true;
		}

		include_once $cls . '.php';

		if (class_exists($cls, false)) {
			return true;
		}
		return false;
	}

	public function classExistsWithConstant(): void
	{
		if (!class_exists('SomeClass12094')) {
			include_once 'SomeClass12094.php';

			if (class_exists('SomeClass12094')) {
				echo "loaded";
			}
		}
	}

	public function interfaceExistsWithVariable(string $cls): bool
	{
		if (interface_exists($cls, false)) {
			return true;
		}

		include_once $cls . '.php';

		if (interface_exists($cls, false)) {
			return true;
		}
		return false;
	}

	public function traitExistsWithVariable(string $cls): bool
	{
		if (trait_exists($cls, false)) {
			return true;
		}

		include_once $cls . '.php';

		if (trait_exists($cls, false)) {
			return true;
		}
		return false;
	}

	public function enumExistsWithVariable(string $cls): bool
	{
		if (enum_exists($cls, false)) {
			return true;
		}

		include_once $cls . '.php';

		if (enum_exists($cls, false)) {
			return true;
		}
		return false;
	}

	public function functionExistsWithVariable(string $fn): bool
	{
		if (function_exists($fn)) {
			return true;
		}

		include_once $fn . '.php';

		if (function_exists($fn)) {
			return true;
		}
		return false;
	}

	public function classExistsWithMethodCall(string $cls, object $loader): bool
	{
		if (class_exists($cls, false)) {
			return true;
		}

		$loader->loadClasses();

		if (class_exists($cls, false)) {
			return true;
		}
		return false;
	}

	public function definedWithImpureCall(string $name): bool
	{
		if (defined('SOME_CONST_12094')) {
			return true;
		}

		include_once 'constants.php';

		if (defined('SOME_CONST_12094')) {
			return true;
		}
		return false;
	}
}
