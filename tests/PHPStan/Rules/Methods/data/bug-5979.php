<?php declare(strict_types = 1);

namespace Bug5979;

class HelloWorld
{
	/**
	 * @return list<array{string, class-string}>
	 */
	public function dataProviderForTestValidCommands(): array
	{
		$data = [
			// left out some commands here for simplicity ...
			// [...]
			[
				'migrations:execute',
				SplQueue::class,
			],
		];

		// this is only available with DBAL 2.x
		if (class_exists(ImportCommand::class)) {
			$data[] = [
				'dbal:import',
				ImportCommand::class,
			];
		}

		return $data;
	}
}
