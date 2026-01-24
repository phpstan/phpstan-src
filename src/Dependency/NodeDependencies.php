<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use function array_values;

final class NodeDependencies
{

	/** @var array<string, string> */
	private static array $normalizedFileNames = [];

	/**
	 * @param array<int, ClassReflection|FunctionReflection> $reflections
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $reflections,
		private ?RootExportedNode $exportedNode,
	)
	{
	}

	/**
	 * @return array<int, ClassReflection|FunctionReflection>
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

			$dependencyFile = self::$normalizedFileNames[$dependencyFile] ??= $this->fileHelper->normalizePath($dependencyFile);

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

	public function getExportedNode(): ?RootExportedNode
	{
		return $this->exportedNode;
	}

}
