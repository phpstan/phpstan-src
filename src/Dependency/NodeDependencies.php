<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use function array_values;
use function str_starts_with;

final class NodeDependencies
{

	/**
	 * @param array<int, ClassReflection|FunctionReflection|ConstantReflection> $reflections
	 * @param list<string> $filePaths files depended on directly, by path rather than through a symbol
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $reflections,
		private ?RootExportedNode $exportedNode,
		private array $filePaths = [],
	)
	{
	}

	/**
	 * Files this node depends on by path: an included file holds no symbol to reflect, but deleting it
	 * changes what the analysis says about the file including it.
	 *
	 * @return list<string>
	 */
	public function getFilePaths(): array
	{
		return $this->filePaths;
	}

	/**
	 * @return array<int, ClassReflection|FunctionReflection|ConstantReflection>
	 */
	public function getReflections(): array
	{
		return $this->reflections;
	}

	/**
	 * @param array<string, true> $analysedFiles
	 * @return string[]
	 */
	public function getFileDependencies(string $currentFile, array $analysedFiles): array
	{
		$dependencies = [];

		foreach ($this->reflections as $dependencyReflection) {
			$dependencyFile = $dependencyReflection->getFileName();
			if ($dependencyFile === null) {
				continue;
			}
			if ($currentFile === $dependencyFile) {
				continue;
			}

			$dependencyFile = $this->fileHelper->normalizePath($dependencyFile);

			if ($currentFile === $dependencyFile) {
				continue;
			}

			if (!isset($analysedFiles[$dependencyFile])) {
				continue;
			}

			$dependencies[$dependencyFile] = $dependencyFile;
		}

		return array_values($dependencies);
	}

	/**
	 * The dependency files getFileDependencies() drops because they are not analysed, split into the
	 * two ways the result cache tracks them:
	 *
	 * - "packages": files of an installed Composer package, resolved to the package name, so that a
	 *   composer.lock change re-analyses only the files depending on a package whose version changed.
	 * - "files": the remaining project files - listed in scanFiles/scanDirectories, excluded from the
	 *   analysis but living in an analysed directory, or simply reached through the autoloader -
	 *   recorded as regular file dependencies, so that editing one of them re-analyses only the files
	 *   depending on it instead of invalidating the whole result cache. A package installed from a
	 *   path repository is in both: it is the project's own code, edited without Composer noticing.
	 *
	 * Files inside a PHAR belong to the running PHPStan itself and cannot change without its version
	 * changing, so they are left out of both.
	 *
	 * @param array<string, true> $analysedFiles
	 * @return array{packages: list<string>, files: list<string>}
	 */
	public function getNonAnalysedDependencies(string $currentFile, array $analysedFiles, PackageDependencyResolver $packageDependencyResolver): array
	{
		$packages = [];
		$files = [];

		foreach ($this->reflections as $dependencyReflection) {
			$dependencyFile = $dependencyReflection->getFileName();
			if ($dependencyFile === null) {
				continue;
			}

			if (str_starts_with($dependencyFile, 'phar://')) {
				continue;
			}

			$dependencyFile = $this->fileHelper->normalizePath($dependencyFile);

			if ($currentFile === $dependencyFile) {
				continue;
			}

			if (isset($analysedFiles[$dependencyFile])) {
				// already returned by getFileDependencies()
				continue;
			}

			$package = $packageDependencyResolver->resolvePackage($dependencyFile);
			if ($package !== null) {
				$packages[$package] = $package;

				// A package installed from a path repository is the project's own code: Composer
				// symlinked or copied it out of a directory next to the project, and it is edited in
				// place without its recorded version or reference moving. Tracking the file as well as
				// the package is what notices those edits - the package entry alone only reacts to a
				// composer.lock change.
				if (!$packageDependencyResolver->isPathPackage($package)) {
					continue;
				}
			}

			$files[$dependencyFile] = $dependencyFile;
		}

		return [
			'packages' => array_values($packages),
			'files' => array_values($files),
		];
	}

	public function getExportedNode(): ?RootExportedNode
	{
		return $this->exportedNode;
	}

}
