<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use DirectoryIterator;
use Nette\DI\Config\Loader;
use Nette\DI\Container as OriginalNetteContainer;
use Nette\DI\ContainerLoader;
use Override;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use function array_keys;
use function count;
use function error_reporting;
use function explode;
use function hash_file;
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function ksort;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_ends_with;
use function substr;
use function time;
use function trim;
use function unlink;
use const E_USER_DEPRECATED;
use const PHP_RELEASE_VERSION;
use const PHP_VERSION_ID;

final class Configurator extends \Nette\Bootstrap\Configurator
{

	/** @var string[] */
	private array $allConfigFiles = [];

	public function __construct(private LoaderFactory $loaderFactory, private bool $journalContainer)
	{
		parent::__construct();
	}

	#[Override]
	protected function createLoader(): Loader
	{
		return $this->loaderFactory->createLoader();
	}

	/**
	 * @param string[] $allConfigFiles
	 */
	public function setAllConfigFiles(array $allConfigFiles): void
	{
		$this->allConfigFiles = $allConfigFiles;
	}

	/**
	 * @return mixed[]
	 */
	#[Override]
	protected function getDefaultParameters(): array
	{
		return [];
	}

	public function getContainerCacheDirectory(): string
	{
		return $this->getCacheDirectory() . '/nette.configurator';
	}

	#[Override]
	public function loadContainer(): string
	{
		$loader = new ContainerLoader(
			$this->getContainerCacheDirectory(),
			$this->staticParameters['debugMode'],
		);

		$attributesPhp = __DIR__ . '/../../vendor/attributes.php';

		$staticParameters = $this->staticParameters;
		ksort($staticParameters['env']);
		unset($staticParameters['env']['_']);
		unset($staticParameters['env']['SHLVL']);

		$className = $loader->load(
			[$this, 'generateContainer'],
			[
				$staticParameters,
				array_keys($this->dynamicParameters),
				$this->configs,
				PHP_VERSION_ID - PHP_RELEASE_VERSION,
				is_file($attributesPhp) ? hash_file('sha256', $attributesPhp) : 'attributes-missing',
				NeonAdapter::CACHE_KEY, $this->getAllConfigFilesHashes(),
			],
		);

		if ($this->journalContainer) {
			$this->journal($className);
		}

		return $className;
	}

	private function journal(string $currentContainerClassName): void
	{
		$directory = $this->getContainerCacheDirectory();
		if (!is_dir($directory)) {
			return;
		}

		$journalFile = $directory . '/container.journal';
		if (!is_file($journalFile)) {
			try {
				FileWriter::write($journalFile, sprintf("%s:%d\n", $currentContainerClassName, time()));
			} catch (CouldNotWriteFileException) {
				// pass
			}

			return;
		}

		try {
			$journalContents = FileReader::read($journalFile);
		} catch (CouldNotReadFileException) {
			return;
		}

		$journalLines = explode("\n", trim($journalContents));
		$linesToWrite = [];
		$usedInTheLastWeek = [];
		$now = time();
		$currentAlreadyInTheJournal = false;
		foreach ($journalLines as $journalLine) {
			if ($journalLine === '') {
				continue;
			}
			$journalLineParts = explode(':', $journalLine);
			if (count($journalLineParts) !== 2) {
				return;
			}
			$className = $journalLineParts[0];
			$containerLastUsedTime = (int) $journalLineParts[1];

			$week = 3600 * 24 * 7;

			if ($containerLastUsedTime + $week < $now) {
				continue;
			}

			$usedInTheLastWeek[] = $className;

			if ($currentContainerClassName !== $className) {
				$linesToWrite[] = sprintf('%s:%d', $className, $containerLastUsedTime);
				continue;
			}

			$linesToWrite[] = sprintf('%s:%d', $currentContainerClassName, $now);
			$currentAlreadyInTheJournal = true;
		}

		if (!$currentAlreadyInTheJournal) {
			$linesToWrite[] = sprintf('%s:%d', $currentContainerClassName, $now);
			$usedInTheLastWeek[] = $currentContainerClassName;
		}

		try {
			FileWriter::write($journalFile, implode("\n", $linesToWrite) . "\n");
		} catch (CouldNotWriteFileException) {
			return;
		}

		foreach (new DirectoryIterator($directory) as $fileInfo) {
			if ($fileInfo->isDot()) {
				continue;
			}
			$fileName = $fileInfo->getFilename();
			if ($fileName === 'container.journal') {
				continue;
			}
			if (!str_ends_with($fileName, '.php')) {
				continue;
			}
			$fileClassName = substr($fileName, 0, -4);
			if (in_array($fileClassName, $usedInTheLastWeek, true)) {
				continue;
			}
			$basePathname = $fileInfo->getPathname();
			@unlink($basePathname);
			@unlink($basePathname . '.lock');
			@unlink($basePathname . '.meta');
		}
	}

	#[Override]
	public function createContainer(bool $initialize = true): OriginalNetteContainer
	{
		set_error_handler(static function (int $errno): bool {
			if ((error_reporting() & $errno) === 0) {
				// silence @ operator
				return true;
			}

			return $errno === E_USER_DEPRECATED;
		});

		try {
			$container = parent::createContainer($initialize);
		} finally {
			restore_error_handler();
		}

		return $container;
	}

	/**
	 * @return string[]
	 */
	private function getAllConfigFilesHashes(): array
	{
		$hashes = [];
		foreach ($this->allConfigFiles as $file) {
			$hash = hash_file('sha256', $file);

			if ($hash === false) {
				throw new CouldNotReadFileException($file);
			}

			$hashes[$file] = $hash;
		}

		return $hashes;
	}

}
