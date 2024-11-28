<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Adapter;
use PHPStan\DependencyInjection\ProjectConfig\ProjectConfig;
use RuntimeException;
use function get_debug_type;
use function is_array;

final class PhpAdapter implements Adapter
{

	/**
	 * @return array<array-key, mixed>
	 */
	public function load(string $file): array
	{
		$configuration = require $file;
		if (is_array($configuration)) {
			return $configuration;
		} elseif ($configuration instanceof ProjectConfig) {
			return $configuration->toArray();
		}

		throw new RuntimeException('Unexpected configuration type: ' . get_debug_type($configuration));
	}

}
