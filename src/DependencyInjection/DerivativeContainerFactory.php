<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_merge;

#[AutowiredService]
final class DerivativeContainerFactory
{

	/**
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		#[AutowiredParameter(ref: '%tempDir%')]
		private string $tempDirectory,
		#[AutowiredParameter]
		private array $additionalConfigFiles,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private string $usedLevel,
		#[AutowiredParameter]
		private ?string $generateBaselineFile,
		#[AutowiredParameter]
		private ?string $cliAutoloadFile,
		#[AutowiredParameter]
		private ?string $singleReflectionFile,
		#[AutowiredParameter]
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
		$containerFactory->setJournalContainer();

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
