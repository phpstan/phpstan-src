<?php declare(strict_types = 1);

namespace Bug3759;

class A
{
	/**
	 * @return mixed[]
	 */
	function modules(): array
	{
		$modules = moduleList();
		if (!$modules) {
			return [];
		}

		return $modules['major'] + $modules['minor'] + $modules['patch'];
	}

	/**
	 * @return mixed[]
	 */
	function moduleList(): array
	{
		return [
			'major' => ['x' => 'x'],
			'minor' => ['y' => 'y'],
			'patch' => ['z' => 'z'],
		];
	}
}
