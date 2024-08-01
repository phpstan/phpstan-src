<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use PHPStan\File\FileExcluder;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function count;
use function is_dir;
use function is_file;
use function sprintf;

class ValidateExcludePathsExtension extends CompilerExtension
{

	/**
	 * @throws InvalidExcludePathsException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		if (!$builder->parameters['__validate']) {
			return;
		}

		$excludePaths = $builder->parameters['excludePaths'];
		if ($excludePaths === null) {
			return;
		}

		$noImplicitWildcard = $builder->parameters['featureToggles']['noImplicitWildcard'];
		if (!$noImplicitWildcard) {
			return;
		}

		$paths = [];
		if (array_key_exists('analyse', $excludePaths)) {
			$paths = $excludePaths['analyse'];
		}
		if (array_key_exists('analyseAndScan', $excludePaths)) {
			$paths = array_merge($paths, $excludePaths['analyseAndScan']);
		}

		$errors = [];
		foreach (array_unique($paths) as $path) {
			if (is_dir($path)) {
				continue;
			}
			if (is_file($path)) {
				continue;
			}
			if (FileExcluder::isFnmatchPattern($path)) {
				continue;
			}

			$errors[] = sprintf('Path %s is neither a directory, nor a file path, nor a fnmatch pattern.', $path);
		}

		if (count($errors) === 0) {
			return;
		}

		throw new InvalidExcludePathsException($errors);
	}

}
