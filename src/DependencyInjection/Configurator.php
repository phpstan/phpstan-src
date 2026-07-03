<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use DirectoryIterator;
use Nette\DI\Config\Loader;
use Nette\DI\Container as OriginalNetteContainer;
use Nette\DI\ContainerLoader;
use Nette\DI\Definitions\Statement;
use Nette\Neon\Entity;
use Override;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use Throwable;
use function array_fill_keys;
use function array_intersect_key;
use function array_keys;
use function count;
use function error_reporting;
use function explode;
use function file_get_contents;
use function hash_file;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function ksort;
use function preg_match;
use function preg_match_all;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
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
		// make sure variables which can get defined by the shell
		// after user-interactions with the UI will not invalidate the container cache
		unset($staticParameters['env']['SHLVL']);
		unset($staticParameters['env']['OLDPWD']);
		unset($staticParameters['env']['LINES']);
		unset($staticParameters['env']['COLUMNS']);
		unset($staticParameters['env']['SHELL_VERBOSITY']);
		// make sure invocations via blackfire use the same container
		unset($staticParameters['env']['BLACKFIRE_AGENT_SOCKET']);

		// Keep only the env vars the container actually depends on in the cache key, so unrelated env
		// changes (CI/shell) don't force a full recompile - phpstan/phpstan#14072. The full env stays
		// in the container parameters, so %env.*% resolution is unaffected; this only narrows the key.
		if (isset($staticParameters['env'])) {
			$relevantEnvVariableNames = $this->relevantEnvVariableNamesForCacheKey();
			if ($relevantEnvVariableNames !== null) {
				$staticParameters['env'] = array_intersect_key(
					$staticParameters['env'],
					array_fill_keys($relevantEnvVariableNames, true),
				);
			}
		}

		$containerKey = [
			$staticParameters,
			array_keys($this->dynamicParameters),
			$this->configs,
			PHP_VERSION_ID - PHP_RELEASE_VERSION,
			is_file($attributesPhp) ? hash_file('sha256', $attributesPhp) : 'attributes-missing',
			NeonAdapter::CACHE_KEY,
			$this->getAllConfigFilesHashes(),
		];

		$className = $loader->load(
			[$this, 'generateContainer'],
			$containerKey,
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
	 * Env vars that can change the generated container, so they must stay in the cache key: every
	 * %env.NAME% referenced across the loaded configs, plus BUILD_TIME_ENV_VARIABLES. Returns null
	 * when a config references the whole %env% array (or its references can't be safely enumerated),
	 * in which case all of it must be kept.
	 *
	 * References are enumerated from the config as parsed by PHPStan's own NeonAdapter - the same
	 * parser the container compiler uses - rather than from raw config text: comments are ignored and
	 * service entities become Statements, so a %env.NAME% in a parameter value, a service argument or
	 * a factory is found the same way it is at compile time. The %env.NAME% grammar mirrors Nette's
	 * parameter-name grammar (%([\w.-]*)%), so dashed names like %env.MY-VAR% are handled too.
	 *
	 * @return list<string>|null
	 */
	private function relevantEnvVariableNamesForCacheKey(): ?array
	{
		$names = [];
		$adapter = new NeonAdapter([]);

		foreach ($this->allConfigFiles as $file) {
			$contents = @file_get_contents($file);
			if ($contents === false || !str_contains($contents, '%env')) {
				continue;
			}

			try {
				$data = $adapter->load($file);
			} catch (Throwable) {
				// A config we can't parse as NEON here (e.g. a .php config) might reference any env
				// var, so we can't prove which ones the container needs: keep the whole environment.
				return null;
			}

			$referencesWholeEnv = false;
			$referencedNames = $this->collectReferencedEnvVariableNames($data, $referencesWholeEnv);
			if ($referencesWholeEnv) {
				return null;
			}

			foreach ($referencedNames as $name) {
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * Recursively collect the env-variable names referenced by %env.NAME% in a parsed config node.
	 * Recurses into arrays and into the service definitions the NeonAdapter produces (Statements, and
	 * any remaining Neon entities) so references in service arguments and factories are not missed.
	 * Sets $referencesWholeEnv on a bare %env% (the whole environment), which forces keeping all of it.
	 *
	 * @param mixed $node
	 * @return list<string>
	 */
	private function collectReferencedEnvVariableNames($node, bool &$referencesWholeEnv): array
	{
		if (is_string($node)) {
			if (preg_match('~%env%~', $node) === 1) {
				$referencesWholeEnv = true;
			}

			if (preg_match_all('~%env\.([\w.-]+)%~', $node, $matches) > 0) {
				return $matches[1];
			}

			return [];
		}

		if ($node instanceof Statement) {
			$node = [$node->getEntity(), $node->arguments];
		} elseif ($node instanceof Entity) {
			$node = [$node->value, $node->attributes];
		}

		if (!is_array($node)) {
			return [];
		}

		$names = [];
		foreach ($node as $value) {
			foreach ($this->collectReferencedEnvVariableNames($value, $referencesWholeEnv) as $name) {
				$names[] = $name;
			}
		}

		return $names;
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
