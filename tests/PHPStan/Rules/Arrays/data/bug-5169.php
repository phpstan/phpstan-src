<?php declare(strict_types=1);

namespace Bug5169;

class HelloWorld
{
	/**
	 * @param array<mixed> $configs
	 *
	 * @return array<mixed>
	 */
	protected function merge(array $configs): array
	{
		$result = [];
		foreach ($configs as $config) {
			$result += $config;

			foreach ($config as $name => $dto) {
				$result[$name] += $dto;
			}
		}

		return $result;
	}

	public function merge2($mixed): void
	{
		$f = $mixed - $mixed;
		$f[$mixed] = true;
	}
}
