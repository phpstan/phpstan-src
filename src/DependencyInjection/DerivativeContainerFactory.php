<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_merge;

class DerivativeContainerFactory
{

	/**
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		private string $currentWorkingDirectory,
		private string $tempDirectory,
		private array $additionalConfigFiles,
		private array $analysedPaths,
		private array $composerAutoloaderProjectPaths,
		private array $analysedPathsFromConfig,
		private string $usedLevel,
		private ?string $generateBaselineFile,
		private ?string $cliAutoloadFile,
		private ?string $singleReflectionFile,
		private ?string $singleReflectionInsteadOfFile,
	)
	{
	}

	/**
	 * @param string[] $additionalConfigFiles
	 */
	public function create(array $additionalConfigFiles): Container
	{
		$containerFactory = new ContainerFactory(
			$this->currentWorkingDirectory,
		);

		return $containerFactory->create(
			$this->tempDirectory,
			array_merge($this->additionalConfigFiles, $additionalConfigFiles),
			$this->analysedPaths,
			$this->composerAutoloaderProjectPaths,
			$this->analysedPathsFromConfig,
			$this->usedLevel,
			$this->generateBaselineFile,
			$this->cliAutoloadFile,
			$this->singleReflectionFile,
			$this->singleReflectionInsteadOfFile,
		);
	}

}
