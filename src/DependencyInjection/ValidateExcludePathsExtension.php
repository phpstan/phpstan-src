<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PHPStan\DependencyInjection\Neon\OptionalPath;
use PHPStan\File\FileExcluder;
use function array_key_exists;
use function array_map;
use function count;
use function is_dir;
use function is_file;
use function sprintf;

final class ValidateExcludePathsExtension extends CompilerExtension
{

	/**
	 * @throws InvalidExcludePathsException
	 */
	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$excludePaths = $builder->parameters['excludePaths'];
		if ($excludePaths === null) {
			return;
		}

		$newExcludePaths = [];
		if (array_key_exists('analyseAndScan', $excludePaths)) {
			$newExcludePaths['analyseAndScan'] = $excludePaths['analyseAndScan'];
		}
		if (array_key_exists('analyse', $excludePaths)) {
			$newExcludePaths['analyse'] = $excludePaths['analyse'];
		}

		$errors = [];
		$suggestOptional = [];
		if ($builder->parameters['__validate']) {
			foreach ($newExcludePaths as $key => $paths) {
				foreach ($paths as $path) {
					if ($path instanceof OptionalPath) {
						continue;
					}
					if (FileExcluder::isAbsolutePath($path)) {
						if (is_dir($path)) {
							continue;
						}
						if (is_file($path)) {
							continue;
						}
					}
					if (FileExcluder::isFnmatchPattern($path)) {
						continue;
					}

					$suggestOptional[$key][] = $path;
					$errors[] = sprintf('Path "%s" is neither a directory, nor a file path, nor a fnmatch pattern.', $path);
				}
			}
		}

		if (count($errors) !== 0) {
			throw new InvalidExcludePathsException($errors, $suggestOptional);
		}

		foreach ($newExcludePaths as $key => $p) {
			$newExcludePaths[$key] = array_map(
				static fn ($path) => $path instanceof OptionalPath ? $path->path : $path,
				$p,
			);
		}

		$builder->parameters['excludePaths'] = $newExcludePaths;
	}

}
