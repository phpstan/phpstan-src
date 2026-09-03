<?php declare(strict_types = 1);

namespace Bug15153;

class MyClass
{

	public function check(string $method): bool
	{
		[$class, $function] = explode('::', $method);

		return !($class === '' || is_null($function) || $function === '');
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function guarded(array $list): void
	{
		[$first] = $list;

		if (is_null($first)) {
			echo 'never';
		}
	}

}
