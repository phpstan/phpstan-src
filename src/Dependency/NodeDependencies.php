<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use function array_values;

final class NodeDependencies
{

	/**
	 * @param array<int, ClassReflection|FunctionReflection|ConstantReflection> $reflections
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $reflections,
		private ?RootExportedNode $exportedNode,
	)
	{
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
	 * Project packages this file's reflections depend on. Mirrors getFileDependencies() but keeps the
	 * vendor files it drops, resolved to the analysed project's Composer package, so a composer.lock
	 * change can re-analyse only the files that depend on a package whose version changed.
	 *
	 * @param array<string, true> $analysedFiles
	 * @return list<string>
	 */
	public function getPackageDependencies(string $currentFile, array $analysedFiles, PackageDependencyResolver $packageDependencyResolver): array
	{
		$packages = [];

		foreach ($this->reflections as $dependencyReflection) {
			$dependencyFile = $dependencyReflection->getFileName();
			if ($dependencyFile === null) {
				continue;
			}

			$dependencyFile = $this->fileHelper->normalizePath($dependencyFile);

			if ($currentFile === $dependencyFile) {
				continue;
			}

			if (isset($analysedFiles[$dependencyFile])) {
				// Analysed file: already tracked as a file-to-file dependency.
				continue;
			}

			$package = $packageDependencyResolver->resolvePackage($dependencyFile);
			if ($package === null) {
				continue;
			}

			$packages[$package] = $package;
		}

		return array_values($packages);
	}

	public function getExportedNode(): ?RootExportedNode
	{
		return $this->exportedNode;
	}

}
