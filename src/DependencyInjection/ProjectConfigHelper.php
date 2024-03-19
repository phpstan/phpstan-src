<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Definitions\Statement;
use function array_merge;
use function array_unique;
use function array_values;
use function is_array;
use function is_string;

class ProjectConfigHelper
{

	/**
	 * @param array<mixed> $projectConfig
	 * @return list<string>
	 */
	public static function getServiceClassNames(array $projectConfig): array
	{
		$services = array_merge(
			$projectConfig['services'] ?? [],
			$projectConfig['rules'] ?? [],
		);
		$classes = [];
		foreach ($services as $service) {
			$classes = array_merge($classes, self::getClassesFromConfigDefinition($service));
			if (!is_array($service)) {
				continue;
			}

			foreach (['class', 'factory', 'implement'] as $key) {
				if (!isset($service[$key])) {
					continue;
				}

				$classes = array_merge($classes, self::getClassesFromConfigDefinition($service[$key]));
			}
		}

		return array_values(array_unique($classes));
	}

	/**
	 * @param mixed $definition
	 * @return string[]
	 */
	private static function getClassesFromConfigDefinition($definition): array
	{
		if (is_string($definition)) {
			return [$definition];
		}

		if ($definition instanceof Statement) {
			$entity = $definition->entity;
			if (is_string($entity)) {
				return [$entity];
			} elseif (is_array($entity) && isset($entity[0]) && is_string($entity[0])) {
				return [$entity[0]];
			}
		}

		return [];
	}

}
