<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use function array_fill_keys;
use function array_key_exists;

class ResultCacheManager
{

	private const CACHE_VERSION = 'v4-callback';

	private string $cacheFilePath;

	/** @var string[] */
	private array $allCustomConfigFiles;

	/** @var string[] */
	private array $analysedPaths;

	/** @var string[] */
	private array $composerAutoloaderProjectPaths;

	/** @var string[] */
	private array $stubFiles;

	private string $usedLevel;

	private ?string $cliAutoloadFile;

	/** @var array<string, string> */
	private array $fileHashes = [];

	/**
	 * @param string $cacheFilePath
	 * @param string[] $allCustomConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $stubFiles
	 * @param string $usedLevel
	 * @param string|null $cliAutoloadFile
	 */
	public function __construct(
		string $cacheFilePath,
		array $allCustomConfigFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $stubFiles,
		string $usedLevel,
		?string $cliAutoloadFile
	)
	{
		$this->cacheFilePath = $cacheFilePath;
		$this->allCustomConfigFiles = $allCustomConfigFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->stubFiles = $stubFiles;
		$this->usedLevel = $usedLevel;
		$this->cliAutoloadFile = $cliAutoloadFile;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param bool $debug
	 * @return ResultCache
	 */
	public function restore(array $allAnalysedFiles, bool $debug): ResultCache
	{
		if ($debug) {
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		if (!is_file($this->cacheFilePath)) {
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		try {
			$data = require $this->cacheFilePath;
		} catch (\Throwable $e) {
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		if (!is_array($data)) {
			@unlink($this->cacheFilePath);
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		if ($data['meta'] !== $this->getMeta()) {
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		if (time() - $data['lastFullAnalysisTime'] >= 60 * 60 * 24 * 7) {
			// run full analysis if the result cache is older than 7 days
			return new ResultCache($allAnalysedFiles, true, time(), [], []);
		}

		$invertedDependencies = $data['dependencies'];
		$deletedFiles = array_fill_keys(array_keys($invertedDependencies), true);
		$filesToAnalyse = [];
		$invertedDependenciesToReturn = [];
		$errors = $data['errorsCallback']();
		$filteredErrors = [];
		$newFileAppeared = false;
		foreach ($allAnalysedFiles as $analysedFile) {
			if (array_key_exists($analysedFile, $errors)) {
				$filteredErrors[$analysedFile] = $errors[$analysedFile];
			}
			if (!array_key_exists($analysedFile, $invertedDependencies)) {
				// new file
				$filesToAnalyse[] = $analysedFile;
				$newFileAppeared = true;
				continue;
			}

			unset($deletedFiles[$analysedFile]);

			$analysedFileData = $invertedDependencies[$analysedFile];
			$cachedFileHash = $analysedFileData['fileHash'];
			$dependentFiles = $analysedFileData['dependentFiles'];
			$invertedDependenciesToReturn[$analysedFile] = $dependentFiles;
			$currentFileHash = $this->getFileHash($analysedFile);

			if ($cachedFileHash === $currentFileHash) {
				continue;
			}

			$filesToAnalyse[] = $analysedFile;
			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		foreach (array_keys($deletedFiles) as $deletedFile) {
			if (!array_key_exists($deletedFile, $invertedDependencies)) {
				continue;
			}

			$deletedFileData = $invertedDependencies[$deletedFile];
			$dependentFiles = $deletedFileData['dependentFiles'];
			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		if ($newFileAppeared) {
			foreach (array_keys($filteredErrors) as $fileWithError) {
				$filesToAnalyse[] = $fileWithError;
			}
		}

		return new ResultCache(array_unique($filesToAnalyse), false, $data['lastFullAnalysisTime'], $filteredErrors, $invertedDependenciesToReturn);
	}

	public function process(AnalyserResult $analyserResult, ResultCache $resultCache, bool $save): AnalyserResult
	{
		$internalErrors = $analyserResult->getInternalErrors();
		$freshErrorsByFile = [];
		foreach ($analyserResult->getErrors() as $error) {
			$freshErrorsByFile[$error->getFilePath()][] = $error;
		}

		$doSave = function (array $errorsByFile, ?array $dependencies) use ($internalErrors, $resultCache): void {
			if ($dependencies === null) {
				return;
			}

			if (count($internalErrors) > 0) {
				return;
			}

			foreach ($errorsByFile as $errors) {
				foreach ($errors as $error) {
					if ($error->canBeIgnored()) {
						continue;
					}

					return;
				}
			}

			$this->save($resultCache->getLastFullAnalysisTime(), $errorsByFile, $dependencies);
		};

		if ($resultCache->isFullAnalysis()) {
			if ($save) {
				$doSave($freshErrorsByFile, $analyserResult->getDependencies());
			}

			return $analyserResult;
		}

		$errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
		$dependencies = $this->mergeDependencies($resultCache, $analyserResult->getDependencies());

		if ($save) {
			$doSave($errorsByFile, $dependencies);
		}

		$flatErrors = [];
		foreach ($errorsByFile as $fileErrors) {
			foreach ($fileErrors as $fileError) {
				$flatErrors[] = $fileError;
			}
		}

		return new AnalyserResult(
			$flatErrors,
			$internalErrors,
			$dependencies,
			$analyserResult->hasReachedInternalErrorsCountLimit()
		);
	}

	/**
	 * @param ResultCache $resultCache
	 * @param array<string, array<Error>> $freshErrorsByFile
	 * @return array<string, array<Error>>
	 */
	private function mergeErrors(ResultCache $resultCache, array $freshErrorsByFile): array
	{
		$errorsByFile = $resultCache->getErrors();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshErrorsByFile)) {
				unset($errorsByFile[$file]);
				continue;
			}
			$errorsByFile[$file] = $freshErrorsByFile[$file];
		}

		return $errorsByFile;
	}

	/**
	 * @param ResultCache $resultCache
	 * @param array<string, array<string>>|null $freshDependencies
	 * @return array<string, array<string>>|null
	 */
	private function mergeDependencies(ResultCache $resultCache, ?array $freshDependencies): ?array
	{
		if ($freshDependencies === null) {
			return null;
		}

		$cachedDependencies = [];
		$resultCacheDependencies = $resultCache->getDependencies();
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($resultCacheDependencies), true);
		foreach ($resultCacheDependencies as $file => $filesDependingOnFile) {
			foreach ($filesDependingOnFile as $fileDependingOnFile) {
				$cachedDependencies[$fileDependingOnFile][] = $file;
				unset($filesNoOneIsDependingOn[$fileDependingOnFile]);
			}
		}

		foreach (array_keys($filesNoOneIsDependingOn) as $file) {
			if (array_key_exists($file, $cachedDependencies)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$cachedDependencies[$file] = [];
		}

		$newDependencies = $cachedDependencies;
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshDependencies)) {
				unset($newDependencies[$file]);
				continue;
			}

			$newDependencies[$file] = $freshDependencies[$file];
		}

		return $newDependencies;
	}

	/**
	 * @param int $lastFullAnalysisTime
	 * @param array<string, array<Error>> $errors
	 * @param array<string, array<string>> $dependencies
	 */
	private function save(
		int $lastFullAnalysisTime,
		array $errors,
		array $dependencies
	): void
	{
		$invertedDependencies = [];
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($dependencies), true);
		foreach ($dependencies as $file => $fileDependencies) {
			foreach ($fileDependencies as $fileDep) {
				if (!array_key_exists($fileDep, $invertedDependencies)) {
					$invertedDependencies[$fileDep] = [
						'fileHash' => $this->getFileHash($fileDep),
						'dependentFiles' => [],
					];
					unset($filesNoOneIsDependingOn[$fileDep]);
				}
				$invertedDependencies[$fileDep]['dependentFiles'][] = $file;
			}
		}

		foreach (array_keys($filesNoOneIsDependingOn) as $file) {
			if (array_key_exists($file, $invertedDependencies)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			if (!is_file($file)) {
				continue;
			}

			$invertedDependencies[$file] = [
				'fileHash' => $this->getFileHash($file),
				'dependentFiles' => [],
			];
		}

		ksort($errors);
		ksort($invertedDependencies);

		foreach ($invertedDependencies as $file => $fileData) {
			$dependentFiles = $fileData['dependentFiles'];
			sort($dependentFiles);
			$invertedDependencies[$file]['dependentFiles'] = $dependentFiles;
		}

		$template = <<<'php'
<?php declare(strict_types = 1);

return [
	'lastFullAnalysisTime' => %s,
	'meta' => %s,
	'errorsCallback' => static function (): array { return %s; },
	'dependencies' => %s,
];
php;

		FileWriter::write(
			$this->cacheFilePath,
			sprintf(
				$template,
				var_export($lastFullAnalysisTime, true),
				var_export($this->getMeta(), true),
				var_export($errors, true),
				var_export($invertedDependencies, true)
			)
		);
	}

	/**
	 * @return mixed[]
	 */
	private function getMeta(): array
	{
		$extensions = array_values(array_filter(get_loaded_extensions(), static function (string $extension): bool {
			return $extension !== 'xdebug';
		}));
		sort($extensions);

		return [
			'cacheVersion' => self::CACHE_VERSION,
			'phpstanVersion' => $this->getPhpStanVersion(),
			'phpVersion' => PHP_VERSION_ID,
			'configFiles' => $this->getConfigFiles(),
			'analysedPaths' => $this->analysedPaths,
			'composerLocks' => $this->getComposerLocks(),
			'cliAutoloadFile' => $this->cliAutoloadFile,
			'phpExtensions' => $extensions,
			'stubFiles' => $this->getStubFiles(),
			'level' => $this->usedLevel,
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function getConfigFiles(): array
	{
		$configFiles = [];
		foreach ($this->allCustomConfigFiles as $configFile) {
			$configFiles[$configFile] = $this->getFileHash($configFile);
		}

		return $configFiles;
	}

	private function getFileHash(string $path): string
	{
		if (array_key_exists($path, $this->fileHashes)) {
			return $this->fileHashes[$path];
		}

		$contents = FileReader::read($path);
		$contents = str_replace("\r\n", "\n", $contents);

		$hash = sha1($contents);
		$this->fileHashes[$path] = $hash;

		return $hash;
	}

	private function getPhpStanVersion(): string
	{
		try {
			return \Jean85\PrettyVersions::getVersion('phpstan/phpstan')->getPrettyVersion();
		} catch (\OutOfBoundsException $e) {
			return 'Version unknown';
		}
	}

	/**
	 * @return array<string, string>
	 */
	private function getComposerLocks(): array
	{
		$locks = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$lockPath = $autoloadPath . '/composer.lock';
			if (!is_file($lockPath)) {
				continue;
			}

			$locks[$lockPath] = $this->getFileHash($lockPath);
		}

		return $locks;
	}

	/**
	 * @return array<string, string>
	 */
	private function getStubFiles(): array
	{
		$stubFiles = [];
		foreach ($this->stubFiles as $stubFile) {
			$stubFiles[$stubFile] = $this->getFileHash($stubFile);
		}

		return $stubFiles;
	}

	public function clear(): string
	{
		$dir = dirname($this->cacheFilePath);
		if (!is_file($this->cacheFilePath)) {
			return $dir;
		}

		@unlink($this->cacheFilePath);

		return $dir;
	}

}
