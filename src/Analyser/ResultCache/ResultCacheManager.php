<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Nette\Neon\Neon;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Command\Output;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\DependencyInjection\ProjectConfigHelper;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileFinder;
use PHPStan\File\FileHelper;
use PHPStan\File\FileWriter;
use PHPStan\Internal\ComposerHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function get_loaded_extensions;
use function implode;
use function is_array;
use function is_file;
use function ksort;
use function microtime;
use function round;
use function sha1_file;
use function sort;
use function sprintf;
use function str_starts_with;
use function time;
use function unlink;
use function var_export;
use const PHP_VERSION_ID;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
final class ResultCacheManager
{

	private const CACHE_VERSION = 'v12-linesToIgnore';

	/** @var array<string, string> */
	private array $fileHashes = [];

	/** @var array<string, true> */
	private array $alreadyProcessed = [];

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $bootstrapFiles
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 */
	public function __construct(
		private ExportedNodeFetcher $exportedNodeFetcher,
		private FileFinder $scanFileFinder,
		private ReflectionProvider $reflectionProvider,
		private StubFilesProvider $stubFilesProvider,
		private FileHelper $fileHelper,
		private string $cacheFilePath,
		private array $analysedPaths,
		private array $composerAutoloaderProjectPaths,
		private string $usedLevel,
		private ?string $cliAutoloadFile,
		private array $bootstrapFiles,
		private array $scanFiles,
		private array $scanDirectories,
		private bool $checkDependenciesOfProjectExtensionFiles,
	)
	{
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param mixed[]|null $projectConfigArray
	 */
	public function restore(array $allAnalysedFiles, bool $debug, bool $onlyFiles, ?array $projectConfigArray, Output $output): ResultCache
	{
		$startTime = microtime(true);
		if ($debug) {
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache not used because of debug mode.');
			}
			return new ResultCache($allAnalysedFiles, true, time(), $this->getMeta($allAnalysedFiles, $projectConfigArray), [], [], [], [], [], [], [], []);
		}
		if ($onlyFiles) {
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache not used because only files were passed as analysed paths.');
			}
			return new ResultCache($allAnalysedFiles, true, time(), $this->getMeta($allAnalysedFiles, $projectConfigArray), [], [], [], [], [], [], [], []);
		}

		$cacheFilePath = $this->cacheFilePath;
		if (!is_file($cacheFilePath)) {
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache not used because the cache file does not exist.');
			}
			return new ResultCache($allAnalysedFiles, true, time(), $this->getMeta($allAnalysedFiles, $projectConfigArray), [], [], [], [], [], [], [], []);
		}

		try {
			$data = require $cacheFilePath;
		} catch (Throwable $e) {
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted(sprintf('Result cache not used because an error occurred while loading the cache file: %s', $e->getMessage()));
			}

			@unlink($cacheFilePath);

			return new ResultCache($allAnalysedFiles, true, time(), $this->getMeta($allAnalysedFiles, $projectConfigArray), [], [], [], [], [], [], [], []);
		}

		if (!is_array($data)) {
			@unlink($cacheFilePath);
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache not used because the cache file is corrupted.');
			}

			return new ResultCache($allAnalysedFiles, true, time(), $this->getMeta($allAnalysedFiles, $projectConfigArray), [], [], [], [], [], [], [], []);
		}

		$meta = $this->getMeta($allAnalysedFiles, $projectConfigArray);
		if ($this->isMetaDifferent($data['meta'], $meta)) {
			if ($output->isVeryVerbose()) {
				$diffs = $this->getMetaKeyDifferences($data['meta'], $meta);
				$output->writeLineFormatted('Result cache not used because the metadata do not match: ' . implode(', ', $diffs));
			}
			return new ResultCache($allAnalysedFiles, true, time(), $meta, [], [], [], [], [], [], [], []);
		}

		if (time() - $data['lastFullAnalysisTime'] >= 60 * 60 * 24 * 7) {
			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache not used because it\'s more than 7 days since last full analysis.');
			}
			// run full analysis if the result cache is older than 7 days
			return new ResultCache($allAnalysedFiles, true, time(), $meta, [], [], [], [], [], [], [], []);
		}

		/**
		 * @var string $fileHash
		 * @var bool $isAnalysed
		 */
		foreach ($data['projectExtensionFiles'] as $extensionFile => [$fileHash, $isAnalysed]) {
			if (!$isAnalysed) {
				continue;
			}
			if (!is_file($extensionFile)) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted(sprintf('Result cache not used because extension file %s was not found.', $extensionFile));
				}
				return new ResultCache($allAnalysedFiles, true, time(), $meta, [], [], [], [], [], [], [], []);
			}

			if ($this->getFileHash($extensionFile) === $fileHash) {
				continue;
			}

			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted(sprintf('Result cache not used because extension file %s hash does not match.', $extensionFile));
			}

			return new ResultCache($allAnalysedFiles, true, time(), $meta, [], [], [], [], [], [], [], []);
		}

		$invertedDependencies = $data['dependencies'];
		$deletedFiles = array_fill_keys(array_keys($invertedDependencies), true);
		$filesToAnalyse = [];
		$invertedDependenciesToReturn = [];
		$errors = $data['errorsCallback']();
		$locallyIgnoredErrors = $data['locallyIgnoredErrorsCallback']();
		$linesToIgnore = $data['linesToIgnore'];
		$unmatchedLineIgnores = $data['unmatchedLineIgnores'];
		$collectedData = $data['collectedDataCallback']();
		$exportedNodes = $data['exportedNodesCallback']();
		$filteredErrors = [];
		$filteredLocallyIgnoredErrors = [];
		$filteredLinesToIgnore = [];
		$filteredUnmatchedLineIgnores = [];
		$filteredCollectedData = [];
		$filteredExportedNodes = [];
		$newFileAppeared = false;

		foreach ($this->getStubFiles() as $stubFile) {
			if (!array_key_exists($stubFile, $errors)) {
				continue;
			}

			$filteredErrors[$stubFile] = $errors[$stubFile];
		}

		foreach ($allAnalysedFiles as $analysedFile) {
			if (array_key_exists($analysedFile, $errors)) {
				$filteredErrors[$analysedFile] = $errors[$analysedFile];
			}
			if (array_key_exists($analysedFile, $locallyIgnoredErrors)) {
				$filteredLocallyIgnoredErrors[$analysedFile] = $locallyIgnoredErrors[$analysedFile];
			}
			if (array_key_exists($analysedFile, $linesToIgnore)) {
				$filteredLinesToIgnore[$analysedFile] = $linesToIgnore[$analysedFile];
			}
			if (array_key_exists($analysedFile, $unmatchedLineIgnores)) {
				$filteredUnmatchedLineIgnores[$analysedFile] = $unmatchedLineIgnores[$analysedFile];
			}
			if (array_key_exists($analysedFile, $collectedData)) {
				$filteredCollectedData[$analysedFile] = $collectedData[$analysedFile];
			}
			if (array_key_exists($analysedFile, $exportedNodes)) {
				$filteredExportedNodes[$analysedFile] = $exportedNodes[$analysedFile];
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
			if (!array_key_exists($analysedFile, $filteredExportedNodes)) {
				continue;
			}

			$cachedFileExportedNodes = $filteredExportedNodes[$analysedFile];
			$exportedNodesChanged = $this->exportedNodesChanged($analysedFile, $cachedFileExportedNodes);
			if ($exportedNodesChanged === null) {
				continue;
			}

			if ($exportedNodesChanged) {
				$newFileAppeared = true;
			}

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

		$filesToAnalyse = array_unique($filesToAnalyse);
		$filesToAnalyseCount = count($filesToAnalyse);

		if ($output->isVeryVerbose()) {
			$elapsed = microtime(true) - $startTime;
			$elapsedString = $elapsed > 5
				? sprintf(' in %f seconds', round($elapsed, 1))
				: '';

			$output->writeLineFormatted(sprintf(
				'Result cache restored%s. %d %s will be reanalysed.',
				$elapsedString,
				$filesToAnalyseCount,
				$filesToAnalyseCount === 1 ? 'file' : 'files',
			));
		}

		return new ResultCache($filesToAnalyse, false, $data['lastFullAnalysisTime'], $meta, $filteredErrors, $filteredLocallyIgnoredErrors, $filteredLinesToIgnore, $filteredUnmatchedLineIgnores, $filteredCollectedData, $invertedDependenciesToReturn, $filteredExportedNodes, $data['projectExtensionFiles']);
	}

	/**
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 */
	private function isMetaDifferent(array $cachedMeta, array $currentMeta): bool
	{
		$projectConfig = $currentMeta['projectConfig'];
		if ($projectConfig !== null) {
			ksort($currentMeta['projectConfig']);

			$currentMeta['projectConfig'] = Neon::encode($currentMeta['projectConfig']);
		}

		return $cachedMeta !== $currentMeta;
	}

	/**
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 *
	 * @return string[]
	 */
	private function getMetaKeyDifferences(array $cachedMeta, array $currentMeta): array
	{
		$diffs = [];
		foreach ($cachedMeta as $key => $value) {
			if (!array_key_exists($key, $currentMeta)) {
				$diffs[] = $key;
				continue;
			}

			if ($value === $currentMeta[$key]) {
				continue;
			}

			$diffs[] = $key;
		}

		if ($diffs === []) {
			// when none of the keys is different,
			// the order of the keys is the problem
			$diffs[] = 'keyOrder';
		}

		return $diffs;
	}

	/**
	 * @param array<int, RootExportedNode> $cachedFileExportedNodes
	 * @return bool|null null means nothing changed, true means new root symbol appeared, false means nested node changed
	 */
	private function exportedNodesChanged(string $analysedFile, array $cachedFileExportedNodes): ?bool
	{
		$fileExportedNodes = $this->exportedNodeFetcher->fetchNodes($analysedFile);

		$cachedSymbols = [];
		foreach ($cachedFileExportedNodes as $cachedFileExportedNode) {
			$cachedSymbols[$cachedFileExportedNode->getType()][] = $cachedFileExportedNode->getName();
		}

		$fileSymbols = [];
		foreach ($fileExportedNodes as $fileExportedNode) {
			$fileSymbols[$fileExportedNode->getType()][] = $fileExportedNode->getName();
		}

		if ($cachedSymbols !== $fileSymbols) {
			return true;
		}

		if (count($fileExportedNodes) !== count($cachedFileExportedNodes)) {
			return true;
		}

		foreach ($fileExportedNodes as $i => $fileExportedNode) {
			$cachedExportedNode = $cachedFileExportedNodes[$i];
			if (!$cachedExportedNode->equals($fileExportedNode)) {
				return false;
			}
		}

		return null;
	}

	public function process(AnalyserResult $analyserResult, ResultCache $resultCache, Output $output, bool $onlyFiles, bool $save): ResultCacheProcessResult
	{
		$internalErrors = $analyserResult->getInternalErrors();
		$freshErrorsByFile = [];
		foreach ($analyserResult->getErrors() as $error) {
			$freshErrorsByFile[$error->getFilePath()][] = $error;
		}

		$freshLocallyIgnoredErrorsByFile = [];
		foreach ($analyserResult->getLocallyIgnoredErrors() as $error) {
			$freshLocallyIgnoredErrorsByFile[$error->getFilePath()][] = $error;
		}

		$freshCollectedDataByFile = [];
		foreach ($analyserResult->getCollectedData() as $collectedData) {
			$freshCollectedDataByFile[$collectedData->getFilePath()][] = $collectedData;
		}

		$meta = $resultCache->getMeta();
		$projectConfigArray = $meta['projectConfig'];
		if ($projectConfigArray !== null) {
			$meta['projectConfig'] = Neon::encode($projectConfigArray);
		}
		$doSave = function (array $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, ?array $dependencies, array $exportedNodes, array $projectExtensionFiles) use ($internalErrors, $resultCache, $output, $onlyFiles, $meta): bool {
			if ($onlyFiles) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because only files were passed as analysed paths.');
				}
				return false;
			}
			if ($dependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in dependencies.');
				}
				return false;
			}

			if (count($internalErrors) > 0) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of internal errors.');
				}
				return false;
			}

			foreach ($errorsByFile as $errors) {
				foreach ($errors as $error) {
					if (!$error->hasNonIgnorableException()) {
						continue;
					}

					if ($output->isVeryVerbose()) {
						$output->writeLineFormatted(sprintf('Result cache was not saved because of non-ignorable exception: %s', $error->getMessage()));
					}

					return false;
				}
			}

			$this->save($resultCache->getLastFullAnalysisTime(), $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, $dependencies, $exportedNodes, $projectExtensionFiles, $meta);

			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache is saved.');
			}

			return true;
		};

		if ($resultCache->isFullAnalysis()) {
			$saved = false;
			if ($save !== false) {
				$projectExtensionFiles = [];
				if ($analyserResult->getDependencies() !== null) {
					$projectExtensionFiles = $this->getProjectExtensionFiles($projectConfigArray, $analyserResult->getDependencies());
				}
				$saved = $doSave($freshErrorsByFile, $freshLocallyIgnoredErrorsByFile, $analyserResult->getLinesToIgnore(), $analyserResult->getUnmatchedLineIgnores(), $freshCollectedDataByFile, $analyserResult->getDependencies(), $analyserResult->getExportedNodes(), $projectExtensionFiles);
			} else {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because it was not requested.');
				}
			}

			return new ResultCacheProcessResult($analyserResult, $saved);
		}

		$errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
		$locallyIgnoredErrorsByFile = $this->mergeLocallyIgnoredErrors($resultCache, $freshLocallyIgnoredErrorsByFile);
		$collectedDataByFile = $this->mergeCollectedData($resultCache, $freshCollectedDataByFile);
		$dependencies = $this->mergeDependencies($resultCache, $analyserResult->getDependencies());
		$exportedNodes = $this->mergeExportedNodes($resultCache, $analyserResult->getExportedNodes());
		$linesToIgnore = $this->mergeLinesToIgnore($resultCache, $analyserResult->getLinesToIgnore());
		$unmatchedLineIgnores = $this->mergeUnmatchedLineIgnores($resultCache, $analyserResult->getUnmatchedLineIgnores());

		$saved = false;
		if ($save !== false) {
			$projectExtensionFiles = [];
			foreach ($resultCache->getProjectExtensionFiles() as $file => [$hash, $isAnalysed, $className]) {
				if ($isAnalysed) {
					continue;
				}

				// keep the same file hashes from the old run
				// so that the message "When you edit them and re-run PHPStan, the result cache will get stale."
				// keeps being shown on subsequent runs
				$projectExtensionFiles[$file] = [$hash, false, $className];
			}
			if ($dependencies !== null) {
				foreach ($this->getProjectExtensionFiles($projectConfigArray, $dependencies) as $file => [$hash, $isAnalysed, $className]) {
					if (!$isAnalysed) {
						continue;
					}

					$projectExtensionFiles[$file] = [$hash, true, $className];
				}
			}
			$saved = $doSave($errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, $dependencies, $exportedNodes, $projectExtensionFiles);
		}

		$flatErrors = [];
		foreach ($errorsByFile as $fileErrors) {
			foreach ($fileErrors as $fileError) {
				$flatErrors[] = $fileError;
			}
		}

		$flatLocallyIgnoredErrors = [];
		foreach ($locallyIgnoredErrorsByFile as $fileErrors) {
			foreach ($fileErrors as $fileError) {
				$flatLocallyIgnoredErrors[] = $fileError;
			}
		}

		$flatCollectedData = [];
		foreach ($collectedDataByFile as $fileCollectedData) {
			foreach ($fileCollectedData as $collectedData) {
				$flatCollectedData[] = $collectedData;
			}
		}

		return new ResultCacheProcessResult(new AnalyserResult(
			$flatErrors,
			$analyserResult->getFilteredPhpErrors(),
			$analyserResult->getAllPhpErrors(),
			$flatLocallyIgnoredErrors,
			$linesToIgnore,
			$unmatchedLineIgnores,
			$internalErrors,
			$flatCollectedData,
			$dependencies,
			$exportedNodes,
			$analyserResult->hasReachedInternalErrorsCountLimit(),
			$analyserResult->getPeakMemoryUsageBytes(),
		), $saved);
	}

	/**
	 * @param array<string, list<Error>> $freshErrorsByFile
	 * @return array<string, list<Error>>
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
	 * @param array<string, list<Error>> $freshLocallyIgnoredErrorsByFile
	 * @return array<string, list<Error>>
	 */
	private function mergeLocallyIgnoredErrors(ResultCache $resultCache, array $freshLocallyIgnoredErrorsByFile): array
	{
		$errorsByFile = $resultCache->getLocallyIgnoredErrors();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshLocallyIgnoredErrorsByFile)) {
				unset($errorsByFile[$file]);
				continue;
			}
			$errorsByFile[$file] = $freshLocallyIgnoredErrorsByFile[$file];
		}

		return $errorsByFile;
	}

	/**
	 * @param array<string, array<CollectedData>> $freshCollectedDataByFile
	 * @return array<string, array<CollectedData>>
	 */
	private function mergeCollectedData(ResultCache $resultCache, array $freshCollectedDataByFile): array
	{
		$collectedDataByFile = $resultCache->getCollectedData();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshCollectedDataByFile)) {
				unset($collectedDataByFile[$file]);
				continue;
			}
			$collectedDataByFile[$file] = $freshCollectedDataByFile[$file];
		}

		return $collectedDataByFile;
	}

	/**
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
				throw new ShouldNotHappenException();
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
	 * @param array<string, array<RootExportedNode>> $freshExportedNodes
	 * @return array<string, array<RootExportedNode>>
	 */
	private function mergeExportedNodes(ResultCache $resultCache, array $freshExportedNodes): array
	{
		$newExportedNodes = $resultCache->getExportedNodes();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshExportedNodes)) {
				unset($newExportedNodes[$file]);
				continue;
			}

			$newExportedNodes[$file] = $freshExportedNodes[$file];
		}

		return $newExportedNodes;
	}

	/**
	 * @param array<string, LinesToIgnore> $freshLinesToIgnore
	 * @return array<string, LinesToIgnore>
	 */
	private function mergeLinesToIgnore(ResultCache $resultCache, array $freshLinesToIgnore): array
	{
		$newLinesToIgnore = $resultCache->getLinesToIgnore();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshLinesToIgnore)) {
				unset($newLinesToIgnore[$file]);
				continue;
			}

			$newLinesToIgnore[$file] = $freshLinesToIgnore[$file];
		}

		return $newLinesToIgnore;
	}

	/**
	 * @param array<string, LinesToIgnore> $freshUnmatchedLineIgnores
	 * @return array<string, LinesToIgnore>
	 */
	private function mergeUnmatchedLineIgnores(ResultCache $resultCache, array $freshUnmatchedLineIgnores): array
	{
		$newUnmatchedLineIgnores = $resultCache->getUnmatchedLineIgnores();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (!array_key_exists($file, $freshUnmatchedLineIgnores)) {
				unset($newUnmatchedLineIgnores[$file]);
				continue;
			}

			$newUnmatchedLineIgnores[$file] = $freshUnmatchedLineIgnores[$file];
		}

		return $newUnmatchedLineIgnores;
	}

	/**
	 * @param array<string, list<Error>> $errors
	 * @param array<string, list<Error>> $locallyIgnoredErrors
	 * @param array<string, LinesToIgnore> $linesToIgnore
	 * @param array<string, LinesToIgnore> $unmatchedLineIgnores
	 * @param array<string, array<CollectedData>> $collectedData
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 * @param mixed[] $meta
	 */
	private function save(
		int $lastFullAnalysisTime,
		array $errors,
		array $locallyIgnoredErrors,
		array $linesToIgnore,
		array $unmatchedLineIgnores,
		array $collectedData,
		array $dependencies,
		array $exportedNodes,
		array $projectExtensionFiles,
		array $meta,
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
				throw new ShouldNotHappenException();
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
		ksort($locallyIgnoredErrors);
		ksort($linesToIgnore);
		ksort($unmatchedLineIgnores);
		ksort($collectedData);
		ksort($invertedDependencies);

		foreach ($invertedDependencies as $file => $fileData) {
			$dependentFiles = $fileData['dependentFiles'];
			sort($dependentFiles);
			$invertedDependencies[$file]['dependentFiles'] = $dependentFiles;
		}

		ksort($exportedNodes);

		$file = $this->cacheFilePath;

		FileWriter::write(
			$file,
			"<?php declare(strict_types = 1);

return [
	'lastFullAnalysisTime' => " . var_export($lastFullAnalysisTime, true) . ",
	'meta' => " . var_export($meta, true) . ",
	'projectExtensionFiles' => " . var_export($projectExtensionFiles, true) . ",
	'errorsCallback' => static function (): array { return " . var_export($errors, true) . "; },
	'locallyIgnoredErrorsCallback' => static function (): array { return " . var_export($locallyIgnoredErrors, true) . "; },
	'linesToIgnore' => " . var_export($linesToIgnore, true) . ",
	'unmatchedLineIgnores' => " . var_export($unmatchedLineIgnores, true) . ",
	'collectedDataCallback' => static function (): array { return " . var_export($collectedData, true) . "; },
	'dependencies' => " . var_export($invertedDependencies, true) . ",
	'exportedNodesCallback' => static function (): array { return " . var_export($exportedNodes, true) . '; },
];
',
		);
	}

	/**
	 * @param mixed[]|null $projectConfig
	 * @param array<string, mixed> $dependencies
	 * @return array<string, array{string, bool, string}>
	 */
	private function getProjectExtensionFiles(?array $projectConfig, array $dependencies): array
	{
		$this->alreadyProcessed = [];
		$projectExtensionFiles = [];
		if ($projectConfig !== null) {
			$vendorDirs = [];
			foreach ($this->composerAutoloaderProjectPaths as $autoloaderProjectPath) {
				$composer = ComposerHelper::getComposerConfig($autoloaderProjectPath);
				if ($composer === null) {
					continue;
				}
				$vendorDirectory = ComposerHelper::getVendorDirFromComposerConfig($autoloaderProjectPath, $composer);
				$vendorDirs[] = $this->fileHelper->normalizePath($vendorDirectory);
			}

			$classes = ProjectConfigHelper::getServiceClassNames($projectConfig);
			foreach ($classes as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					continue;
				}

				$classReflection = $this->reflectionProvider->getClass($class);
				$fileName = $classReflection->getFileName();
				if ($fileName === null) {
					continue;
				}

				if (str_starts_with($fileName, 'phar://')) {
					continue;
				}

				$allServiceFiles = $this->getAllDependencies($fileName, $dependencies);
				if (count($allServiceFiles) === 0) {
					$normalizedFileName = $this->fileHelper->normalizePath($fileName);
					foreach ($vendorDirs as $vendorDir) {
						if (str_starts_with($normalizedFileName, $vendorDir)) {
							continue 2;
						}
					}
					$projectExtensionFiles[$fileName] = [$this->getFileHash($fileName), false, $class];
					continue;
				}

				foreach ($allServiceFiles as $serviceFile) {
					if (array_key_exists($serviceFile, $projectExtensionFiles)) {
						continue;
					}

					$projectExtensionFiles[$serviceFile] = [$this->getFileHash($serviceFile), true, $class];
				}
			}
		}

		return $projectExtensionFiles;
	}

	/**
	 * @param array<string, array<int, string>> $dependencies
	 * @return array<int, string>
	 */
	private function getAllDependencies(string $fileName, array $dependencies): array
	{
		if (!array_key_exists($fileName, $dependencies)) {
			return [];
		}

		if (array_key_exists($fileName, $this->alreadyProcessed)) {
			return [];
		}

		$this->alreadyProcessed[$fileName] = true;

		$files = [$fileName];

		if ($this->checkDependenciesOfProjectExtensionFiles) {
			foreach ($dependencies[$fileName] as $fileDep) {
				foreach ($this->getAllDependencies($fileDep, $dependencies) as $fileDep2) {
					$files[] = $fileDep2;
				}
			}
		}

		return $files;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param mixed[]|null $projectConfigArray
	 * @return mixed[]
	 */
	private function getMeta(array $allAnalysedFiles, ?array $projectConfigArray): array
	{
		$extensions = array_values(array_filter(get_loaded_extensions(), static fn (string $extension): bool => $extension !== 'xdebug'));
		sort($extensions);

		if ($projectConfigArray !== null) {
			unset($projectConfigArray['parameters']['editorUrl']);
			unset($projectConfigArray['parameters']['editorUrlTitle']);
			unset($projectConfigArray['parameters']['errorFormat']);
			unset($projectConfigArray['parameters']['ignoreErrors']);
			unset($projectConfigArray['parameters']['reportUnmatchedIgnoredErrors']);
			unset($projectConfigArray['parameters']['tipsOfTheDay']);
			unset($projectConfigArray['parameters']['parallel']);
			unset($projectConfigArray['parameters']['internalErrorsCountLimit']);
			unset($projectConfigArray['parameters']['cache']);
			unset($projectConfigArray['parameters']['memoryLimitFile']);
			unset($projectConfigArray['parameters']['pro']);
			unset($projectConfigArray['parametersSchema']);

			ksort($projectConfigArray);
		}

		return [
			'cacheVersion' => self::CACHE_VERSION,
			'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
			'phpVersion' => PHP_VERSION_ID,
			'projectConfig' => $projectConfigArray,
			'analysedPaths' => $this->analysedPaths,
			'scannedFiles' => $this->getScannedFiles($allAnalysedFiles),
			'composerLocks' => $this->getComposerLocks(),
			'composerInstalled' => $this->getComposerInstalled(),
			'executedFilesHashes' => $this->getExecutedFileHashes(),
			'phpExtensions' => $extensions,
			'stubFiles' => $this->getStubFiles(),
			'level' => $this->usedLevel,
		];
	}

	private function getFileHash(string $path): string
	{
		if (array_key_exists($path, $this->fileHashes)) {
			return $this->fileHashes[$path];
		}

		$hash = sha1_file($path);
		if ($hash === false) {
			throw new CouldNotReadFileException($path);
		}
		$this->fileHashes[$path] = $hash;

		return $hash;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @return array<string, string>
	 */
	private function getScannedFiles(array $allAnalysedFiles): array
	{
		$scannedFiles = $this->scanFiles;
		foreach ($this->scanFileFinder->findFiles($this->scanDirectories)->getFiles() as $file) {
			$scannedFiles[] = $file;
		}

		$scannedFiles = array_unique($scannedFiles);

		$hashes = [];
		foreach (array_diff($scannedFiles, $allAnalysedFiles) as $file) {
			$hashes[$file] = $this->getFileHash($file);
		}

		ksort($hashes);

		return $hashes;
	}

	/**
	 * @return array<string, string>
	 */
	private function getExecutedFileHashes(): array
	{
		$hashes = [];
		if ($this->cliAutoloadFile !== null) {
			$hashes[$this->cliAutoloadFile] = $this->getFileHash($this->cliAutoloadFile);
		}

		foreach ($this->bootstrapFiles as $bootstrapFile) {
			$hashes[$bootstrapFile] = $this->getFileHash($bootstrapFile);
		}

		ksort($hashes);

		return $hashes;
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
	private function getComposerInstalled(): array
	{
		$data = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$composer = ComposerHelper::getComposerConfig($autoloadPath);

			if ($composer === null) {
				continue;
			}

			$filePath = ComposerHelper::getVendorDirFromComposerConfig($autoloadPath, $composer) . '/composer/installed.php';
			if (!is_file($filePath)) {
				continue;
			}

			$installed = require $filePath;
			$rootName = $installed['root']['name'];
			unset($installed['root']);
			unset($installed['versions'][$rootName]);

			$data[$filePath] = $installed;
		}

		return $data;
	}

	/**
	 * @return array<string, string>
	 */
	private function getStubFiles(): array
	{
		$stubFiles = [];
		foreach ($this->stubFilesProvider->getProjectStubFiles() as $stubFile) {
			$stubFiles[$stubFile] = $this->getFileHash($stubFile);
		}

		ksort($stubFiles);

		return $stubFiles;
	}

}
