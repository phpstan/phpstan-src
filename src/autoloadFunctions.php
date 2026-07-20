<?php declare(strict_types = 1);

namespace PHPStan;

use Composer\Autoload\ClassLoader;
use function count;
use function get_class;
use function is_array;
use function is_object;

/**
 * Autoloaders that were registered *after* Composer's class loader in the
 * spl_autoload queue. At runtime Composer resolves the class first, so these
 * are consulted only as a fallback, after the static source locators.
 *
 * @return array<int, callable(string): void>
 */
function autoloadFunctions(): array // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return $GLOBALS['__phpstanAutoloadFunctions'] ?? [];
}

/**
 * Autoloaders that were registered *before* Composer's class loader in the
 * spl_autoload queue (e.g. spl_autoload_register($fn, true, true)). At runtime
 * these run before Composer resolves the class, so they must be consulted
 * before the static Composer source locators to mirror that order.
 *
 * @return array<int, callable(string): void>
 */
function autoloadFunctionsPrependedToComposer(): array // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return $GLOBALS['__phpstanAutoloadFunctionsPrependedToComposer'] ?? [];
}

/**
 * Splits the autoload functions registered while loading Composer's autoloader
 * and the bootstrap files into those registered before and after Composer's own
 * class loader in the spl_autoload queue. This lets PHPStan consult them in the
 * same order relative to Composer as PHP does at runtime, instead of always
 * invoking them before (or after) the static Composer source locators.
 *
 * @param list<mixed>|false $autoloadFunctionsBefore
 * @param list<mixed>|false $autoloadFunctionsAfter
 * @return array{prepended: list<mixed>, appended: list<mixed>}
 */
function collectNewAutoloadFunctions($autoloadFunctionsBefore, $autoloadFunctionsAfter): array // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	$prepended = [];
	$appended = [];

	if ($autoloadFunctionsBefore === false || $autoloadFunctionsAfter === false) {
		return ['prepended' => $prepended, 'appended' => $appended];
	}

	$composerIndex = null;
	foreach ($autoloadFunctionsAfter as $index => $after) {
		if (
			is_array($after)
			&& count($after) > 0
			&& is_object($after[0])
			&& get_class($after[0]) === ClassLoader::class
		) {
			$composerIndex = $index;
			break;
		}
	}

	foreach ($autoloadFunctionsAfter as $index => $after) {
		if (is_array($after) && count($after) > 0) {
			if (
				is_object($after[0])
				&& get_class($after[0]) === ClassLoader::class
			) {
				continue;
			}
			if ($after[0] === 'PHPStan\\PharAutoloader') {
				continue;
			}
		}

		foreach ($autoloadFunctionsBefore as $before) {
			if ($after === $before) {
				continue 2;
			}
		}

		if ($composerIndex !== null && $index < $composerIndex) {
			$prepended[] = $after;
		} else {
			$appended[] = $after;
		}
	}

	return ['prepended' => $prepended, 'appended' => $appended];
}
