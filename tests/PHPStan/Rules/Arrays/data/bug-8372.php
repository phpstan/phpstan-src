<?php declare(strict_types=1);

namespace Bug8372;

class Test
{
	/**
	 * @param array<mixed> $path
	 */
	function test(string $method, array $path): void
	{
		if (!key_exists($method, $path)) {
			return;
		}

		if (!is_array($path[$method])) {
			return;
		}

		if (!key_exists('parameters', $path[$method])) {
			$path[$method]['parameters'] = [];

			$path[$method]['parameters'][] = 'foo';
			$path[$method]['parameters'][] = 'bar';
			$path[$method]['parameters'][] = 'baz';
		}
	}
}
