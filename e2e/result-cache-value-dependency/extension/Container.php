<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

/**
 * Stands for a compiled DI container the analysed code gets its services and parameters from.
 */
final class Container
{

	public static function getService(string $id): ?string
	{
		return self::read()['services'][$id] ?? null;
	}

	public static function getParameter(string $name): ?string
	{
		return self::read()['parameters'][$name] ?? null;
	}

	/**
	 * @return array{services: array<string, string>, parameters: array<string, string>}
	 */
	private static function read(): array
	{
		$contents = file_get_contents(dirname(__DIR__) . '/container.json');
		/** @var array{services: array<string, string>, parameters: array<string, string>} $container */
		$container = json_decode($contents === false ? '{}' : $contents, true);

		return $container;
	}

}
