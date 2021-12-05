<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

class DerivativeContainerFactory
{

	private string $currentWorkingDirectory;

	private string $tempDirectory;

	/** @var string[] */
	private array $additionalConfigFiles;

	/** @var string[] */
	private array $analysedPaths;

	/** @var string[] */
	private array $composerAutoloaderProjectPaths;

	/** @var string[] */
	private array $analysedPathsFromConfig;

	private string $usedLevel;

	private ?string $generateBaselineFile;

	private ?string $cliAutoloadFile;

	private ?string $singleReflectionFile;

	private ?string $singleReflectionInsteadOfFile;

	/**
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		string $currentWorkingDirectory,
		string $tempDirectory,
		array $additionalConfigFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $analysedPathsFromConfig,
		string $usedLevel,
		?string $generateBaselineFile,
		?string $cliAutoloadFile,
		?string $singleReflectionFile,
		?string $singleReflectionInsteadOfFile
	)
	{
		$this->currentWorkingDirectory = $currentWorkingDirectory;
		$this->tempDirectory = $tempDirectory;
		$this->additionalConfigFiles = $additionalConfigFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->analysedPathsFromConfig = $analysedPathsFromConfig;
		$this->usedLevel = $usedLevel;
		$this->generateBaselineFile = $generateBaselineFile;
		$this->cliAutoloadFile = $cliAutoloadFile;
		$this->singleReflectionFile = $singleReflectionFile;
		$this->singleReflectionInsteadOfFile = $singleReflectionInsteadOfFile;
	}

	/**
	 * @param string[] $additionalConfigFiles
	 */
	public function create(array $additionalConfigFiles): Container
	{
		$containerFactory = new ContainerFactory(
			$this->currentWorkingDirectory
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
			$this->singleReflectionInsteadOfFile
		);
	}

}
