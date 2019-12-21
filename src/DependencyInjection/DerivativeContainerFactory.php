<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

class DerivativeContainerFactory
{

	/** @var string */
	private $currentWorkingDirectory;

	/** @var string */
	private $tempDirectory;

	/** @var string[] */
	private $additionalConfigFiles;

	/** @var string[] */
	private $analysedPaths;

	/**
	 * @param string $currentWorkingDirectory
	 * @param string $tempDirectory
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 */
	public function __construct(
		string $currentWorkingDirectory,
		string $tempDirectory,
		array $additionalConfigFiles,
		array $analysedPaths
	)
	{
		$this->currentWorkingDirectory = $currentWorkingDirectory;
		$this->tempDirectory = $tempDirectory;
		$this->additionalConfigFiles = $additionalConfigFiles;
		$this->analysedPaths = $analysedPaths;
	}

	/**
	 * @param string[] $additionalConfigFiles
	 * @return \PHPStan\DependencyInjection\Container
	 */
	public function create(array $additionalConfigFiles): Container
	{
		$containerFactory = new ContainerFactory(
			$this->currentWorkingDirectory
		);

		return $containerFactory->create(
			$this->tempDirectory,
			array_merge($this->additionalConfigFiles, $additionalConfigFiles),
			$this->analysedPaths
		);
	}

}
