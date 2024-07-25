<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use function dirname;
use function pathinfo;
use function str_starts_with;
use function stripos;
use function strtolower;
use const PATHINFO_BASENAME;

final class ApiRuleHelper
{

	public function isPhpStanCode(Scope $scope, string $namespace, ?string $declaringFile): bool
	{
		$scopeNamespace = $scope->getNamespace();
		if ($scopeNamespace === null) {
			return $this->isPhpStanName($namespace);
		}

		if ($this->isPhpStanName($scopeNamespace)) {
			if (!$this->isPhpStanName($namespace)) {
				return false;
			}

			if ($declaringFile !== null) {
				$scopeFile = $scope->getFile();
				$dir = dirname($scopeFile);
				$helper = new ParentDirectoryRelativePathHelper($dir);
				$pathParts = $helper->getFilenameParts($declaringFile);
				$directories = $this->createAbsoluteDirectories($dir, $pathParts);
				foreach ($directories as $directory) {
					if (pathinfo($directory, PATHINFO_BASENAME) === 'vendor') {
						return true;
					}
				}
			}

			return false;
		}

		return $this->isPhpStanName($namespace);
	}

	/**
	 * @param string[] $parts
	 * @return string[]
	 */
	private function createAbsoluteDirectories(string $currentDirectory, array $parts): array
	{
		$directories = [];
		foreach ($parts as $part) {
			if ($part === '..') {
				$currentDirectory = dirname($currentDirectory);
				$directories[] = $currentDirectory;
				continue;
			}

			$currentDirectory .= '/' . $part;
			$directories[] = $currentDirectory;
		}

		return $directories;
	}

	public function isPhpStanName(string $namespace): bool
	{
		if (strtolower($namespace) === 'phpstan') {
			return true;
		}

		if (str_starts_with($namespace, 'PHPStan\\PhpDocParser\\')) {
			return false;
		}

		if (str_starts_with($namespace, 'PHPStan\\BetterReflection\\')) {
			return false;
		}

		return stripos($namespace, 'PHPStan\\') === 0;
	}

}
