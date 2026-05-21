<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11014;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param string[] $values **/
	public function sayHello(array $values): void
	{
		foreach ($values as $value) {
			assertType('string', $value);
			assertNativeType('mixed', $value);
			if (!is_string($value)) {
				throw new \Exception();
			}
		}
	}

	/** @param string[] $values **/
	public function sayHello2(array $values): void
	{
		array_map(function ($item) {
			assertType('string', $item);
			assertNativeType('mixed', $item);
			if (!is_string($item)) {
				throw new \Exception();
			}
			return $item;
		}, $values);
	}

	/** @param string[] $values **/
	public function sayHello3(array $values): void
	{
		array_map(fn ($item) => is_string($item) ? $item : throw new \Exception(), $values);
	}

	/** @param string[] $values **/
	public function sayHello4(array $values): void
	{
		array_filter($values, function ($item) {
			assertType('string', $item);
			assertNativeType('mixed', $item);
			return is_string($item);
		});
	}

	/** @param string[] $values **/
	public function sayHello5(array $values): void
	{
		array_filter($values, fn ($item) => is_string($item));
	}

	/** @param string[] $values **/
	public function arrayMapArrow(array $values): void
	{
		array_map(fn ($item) => assertNativeType('mixed', $item), $values);
	}

	/** @param string[] $values **/
	public function typedClosure(array $values): void
	{
		array_map(function (string $item) {
			assertType('string', $item);
			assertNativeType('string', $item);
		}, $values);
	}

	public function constantArrayFilter(): void
	{
		array_filter(
			[
				'curl_multi_add_handle',
				'curl_multi_exec',
				'curl_multi_init',
			],
			static function ($function_name) {
				assertType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $function_name);
				assertNativeType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $function_name);

				return true;
			},
		);
	}

	public function constantArrayMap(): void
	{
		array_map(
			static function ($function_name) {
				assertType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $function_name);
				assertNativeType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $function_name);

				return true;
			},
			[
				'curl_multi_add_handle',
				'curl_multi_exec',
				'curl_multi_init',
			],
		);
	}

	public function constantArrayForeach(): void
	{
		$a = [
			'curl_multi_add_handle',
			'curl_multi_exec',
			'curl_multi_init',
		];

		foreach ($a as $b) {
			assertType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $b);
			assertNativeType("'curl_multi_add_handle'|'curl_multi_exec'|'curl_multi_init'", $b);
		}
	}
}
