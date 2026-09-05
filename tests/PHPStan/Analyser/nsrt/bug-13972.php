<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13972;

use function gettype;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function getAssignment(string $flagKey, string|bool $defaultValue): string|bool
	{
			$type = gettype($defaultValue);

			return match ($type) {
				'string' => $this->getString($defaultValue),
				'boolean' => $this->getBool($defaultValue),
			};
	}

	public function getAssignmentMatchAsserts(string $flagKey, string|bool $defaultValue): void
	{
		$type = gettype($defaultValue);

		match ($type) {
			'string' => assertType('string', $defaultValue),
			'boolean' => assertType('bool', $defaultValue),
		};
	}

	public function getAssignmentIf(string $flagKey, string|bool $defaultValue): string|bool
	{
		$type = gettype($defaultValue);

		if ($type === 'string') {
			assertType('string', $defaultValue);
			assertNativeType('string', $defaultValue);

			return $this->getString($defaultValue);
		}

		assertType('bool', $defaultValue);
		assertNativeType('bool', $defaultValue);

		return $this->getBool($defaultValue);
	}

	public function getBool(bool $default): bool
	{
		return true;
	}

	public function getString(string $default): string
	{
		return 'toto';
	}

}
