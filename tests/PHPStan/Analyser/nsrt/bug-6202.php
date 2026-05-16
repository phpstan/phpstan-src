<?php declare(strict_types = 1);

namespace Bug6202;

use Exception;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<string,mixed> $array
	 */
	public function sayHello(array $array): void
	{
		if (isset($array['mightExist']) && !is_string($array['mightExist'])) {
			throw new Exception('Has to be string if set');
		}

		assertType('string', $array['mightExist'] ?? '');

		if (!isset($array['hasToExist']) || !is_string($array['hasToExist'])) {
			throw new Exception('Has to exist as string');
		}
		assertType('string', $array['hasToExist']);
	}

	/**
	 * @param array<string, mixed> $array
	 */
	public function otherIsTypeFunctions(array $array): void
	{
		if (isset($array['intKey']) && !is_int($array['intKey'])) {
			throw new Exception();
		}
		assertType('int', $array['intKey'] ?? 0);

		if (isset($array['arrayKey']) && !is_array($array['arrayKey'])) {
			throw new Exception();
		}
		assertType('array<mixed>', $array['arrayKey'] ?? []);

		if (isset($array['boolKey']) && !is_bool($array['boolKey'])) {
			throw new Exception();
		}
		assertType('bool', $array['boolKey'] ?? false);
	}

	/**
	 * @param array<string, mixed> $array
	 */
	public function orPattern(array $array): void
	{
		if (!isset($array['key']) || !is_string($array['key'])) {
			throw new Exception();
		}
		assertType('string', $array['key']);
	}

	/**
	 * @param array<string, mixed> $array
	 */
	public function instanceofCheck(array $array): void
	{
		if (isset($array['obj']) && !$array['obj'] instanceof \stdClass) {
			throw new Exception();
		}
		assertType('stdClass', $array['obj'] ?? new \stdClass());
	}

	/**
	 * @param array<string, mixed> $array
	 */
	public function nestedArrayDimFetch(array $array): void
	{
		if (isset($array['nested']['key']) && !is_string($array['nested']['key'])) {
			throw new Exception();
		}
		assertType('string', $array['nested']['key'] ?? '');
	}

	/**
	 * @param array<string, mixed> $array
	 */
	public function directAccessAfterGuard(array $array): void
	{
		if (isset($array['mightExist']) && !is_string($array['mightExist'])) {
			throw new Exception();
		}

		if (isset($array['mightExist'])) {
			assertType('string', $array['mightExist']);
		}
	}
}
