<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;

class NodeDependencies
{

	private FileHelper $fileHelper;

	/** @var array<int, ClassReflection|FunctionReflection> */
	private array $reflections;

	private ?ExportedNode $exportedNode;

	/**
	 * @param FileHelper $fileHelper
	 * @param array<int, ClassReflection|FunctionReflection> $reflections
	 */
	public function __construct(
		FileHelper $fileHelper,
		array $reflections,
		?ExportedNode $exportedNode
	)
	{
		$this->fileHelper = $fileHelper;
		$this->reflections = $reflections;
		$this->exportedNode = $exportedNode;
	}

	/**
	 * @param string $currentFile
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

	public function getExportedNode(): ?ExportedNode
	{
		return $this->exportedNode;
	}

}
