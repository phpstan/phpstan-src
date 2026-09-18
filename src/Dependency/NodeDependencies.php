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
	 * The files and packages this node depends on, resolved in a single pass over its reflections:
	 *
	 * - "analysedFiles": dependency files that are analysed themselves.
	 * - "packages": files of an installed Composer package, resolved to the package name, so that a
	 *   composer.lock change re-analyses only the files depending on a package whose version changed.
	 * - "nonAnalysedFiles": the remaining project files - listed in scanFiles/scanDirectories, excluded
	 *   from the analysis but living in an analysed directory, or simply reached through the autoloader -
	 *   recorded as regular file dependencies, so that editing one of them re-analyses only the files
	 *   depending on it instead of invalidating the whole result cache. A package installed from a
	 *   path repository is in both: it is the project's own code, edited without Composer noticing.
	 *
	 * Files inside a PHAR belong to the running PHPStan itself and cannot change without its version
	 * changing, so they are left out of "packages" and "nonAnalysedFiles".
	 *
	 * Built-in symbols of an extension whose stubs differ between its major versions are recorded in
	 * "packages" too, under the extension's platform package name (ext-<name>), so that selecting a
	 * different version re-analyses only the files using the extension. Their file is the PhpStorm stub
	 * they were read from - inside the PHAR, or in PHPStan's own vendor directory - which does not change
	 * with the selected version.
	 *
	 * @param array<string, true> $analysedFiles
	 * @return array{analysedFiles: list<string>, nonAnalysedFiles: list<string>, packages: list<string>}
	 */
	public function getFileAndPackageDependencies(string $currentFile, array $analysedFiles, PackageDependencyResolver $packageDependencyResolver): array
	{
		if ($this->reflections === []) {
			return ['analysedFiles' => [], 'nonAnalysedFiles' => [], 'packages' => []];
		}

		$analysedDependencies = [];
		$nonAnalysedDependencies = [];
		$packages = [];

		foreach ($this->reflections as $dependencyReflection) {
			$extensionPackage = $packageDependencyResolver->resolveVersionedExtensionPackage($dependencyReflection);
			if ($extensionPackage !== null) {
				$packages[$extensionPackage] = $extensionPackage;
			}

			$dependencyFile = $dependencyReflection->getFileName();
			if ($dependencyFile === null) {
				continue;
			}
			if ($currentFile === $dependencyFile) {
				continue;
			}

			$normalizedDependencyFile = $this->fileHelper->normalizePath($dependencyFile);
			if ($currentFile === $normalizedDependencyFile) {
				continue;
			}

			if (isset($analysedFiles[$normalizedDependencyFile])) {
				$analysedDependencies[$normalizedDependencyFile] = $normalizedDependencyFile;
				continue;
			}

			if (str_starts_with($dependencyFile, 'phar://')) {
				continue;
			}

			$package = $packageDependencyResolver->resolvePackage($normalizedDependencyFile);
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

			$nonAnalysedDependencies[$normalizedDependencyFile] = $normalizedDependencyFile;
		}

		return [
			'analysedFiles' => array_values($analysedDependencies),
			'nonAnalysedFiles' => array_values($nonAnalysedDependencies),
			'packages' => array_values($packages),
		];
	}

	public function getExportedNode(): ?RootExportedNode
	{
		return $this->exportedNode;
	}

}
