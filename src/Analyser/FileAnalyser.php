<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\FileNode;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_unique;
use function array_values;
use function count;
use function error_reporting;
use function hash;
use function is_dir;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
#[AutowiredService]
final class FileAnalyser
{

	/** @var array<string, Error> */
	private array $allPhpErrors = [];

	/** @var array<string, Error> */
	private array $filteredPhpErrors = [];

	/**
	 * @param ExtensionsCollection<IgnoreErrorExtension> $ignoreErrorExtensions
	 */
	public function __construct(
		private ScopeFactory $scopeFactory,
		#[AutowiredParameter(ref: '@' . NodeScopeResolver::class)]
		private NodeScopeResolver $nodeScopeResolver,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
		private DependencyResolver $dependencyResolver,
		private PackageDependencyResolver $packageDependencyResolver,
		#[AutowiredExtensions(of: IgnoreErrorExtension::class)]
		private ExtensionsCollection $ignoreErrorExtensions,
		private RuleErrorTransformer $ruleErrorTransformer,
		private LocalIgnoresProcessor $localIgnoresProcessor,
		#[AutowiredParameter]
		private bool $reportIgnoresWithoutComments,
	)
	{
	}

	/**
	 * @param array<string, true> $analysedFiles
	 * @param callable(Node $node, Scope $scope): void|null $outerNodeCallback
	 */
	public function analyseFile(
		string $file,
		array $analysedFiles,
		RuleRegistry $ruleRegistry,
		CollectorRegistry $collectorRegistry,
		?callable $outerNodeCallback,
	): FileAnalyserResult
	{
		/** @var list<Error> $fileErrors */
		$fileErrors = [];

		/** @var list<string> $processedFiles */
		$processedFiles = [];

		/** @var list<Error> $locallyIgnoredErrors */
		$locallyIgnoredErrors = [];

		/** @var CollectorData $fileCollectedData */
		$fileCollectedData = [];

		$fileDependencies = [];
		$usedTraitFileDependencies = [];
		$filePackageDependencies = [];
		$exportedNodes = [];
		$linesToIgnore = [];
		$unmatchedLineIgnores = [];
		if (is_file($file)) {
			try {
				$this->collectErrors($analysedFiles);
				$parserNodes = $this->parser->parseFile($file);
				$processedFiles[] = $file;

				$nodeCallback = new FileAnalyserCallback(
					$file,
					$analysedFiles,
					$ruleRegistry,
					$collectorRegistry,
					$outerNodeCallback,
					$parserNodes,
					$this->ignoreErrorExtensions->getAll(),
					$this->parser,
					$this->dependencyResolver,
					$this->packageDependencyResolver,
					$this->ruleErrorTransformer,
					$processedFiles,
				);
				$scope = $this->scopeFactory->create(ScopeContext::create($file), $nodeCallback);
				$nodeCallback(new FileNode($parserNodes), $scope);
				$this->nodeScopeResolver->processNodes(
					$parserNodes,
					$scope,
					$nodeCallback,
				);
				$fileErrors = $nodeCallback->getFileErrors();
				$fileCollectedData = $nodeCallback->getFileCollectedData();
				$fileDependencies = $nodeCallback->getFileDependencies();
				$usedTraitFileDependencies = $nodeCallback->getUsedTraitFileDependencies();
				$filePackageDependencies = $nodeCallback->getPackageDependencies();
				$exportedNodes = $nodeCallback->getExportedNodes();
				$linesToIgnore = $nodeCallback->getLinesToIgnore();
				$unmatchedLineIgnores = $nodeCallback->getUnmatchedLineIgnores();
				$temporaryFileErrors = $nodeCallback->getTemporaryFileErrors();
				$processedFiles = $nodeCallback->getProcessedFiles();

				if ($this->reportIgnoresWithoutComments) {
					foreach ($linesToIgnore as $ignoredFile => $lines) {
						foreach ($lines as $line => $identifiers) {
							if ($identifiers === null) {
								$fileErrors[] = (new Error(
									'Ignoring all errors on a line is not allowed.',
									$ignoredFile,
									$line,
									false,
									$ignoredFile,
									null,
									'Use @phpstan-ignore with an identifier and a comment (in parentheses).',
								))->withIdentifier('ignore.allLineErrors');
								unset($linesToIgnore[$ignoredFile][$line]);
								unset($unmatchedLineIgnores[$ignoredFile][$line]);
								continue;
							}

							foreach ($identifiers as $identifier) {
								if ($identifier['comment'] !== null) {
									continue;
								}

								$fileErrors[] = (new Error(
									sprintf('Ignore with identifier %s has no comment.', $identifier['name']),
									$ignoredFile,
									$line,
									false,
									$ignoredFile,
									null,
									'Explain why this ignore is necessary in parentheses after the identifier.',
								))->withIdentifier('ignore.noComment');
								unset($linesToIgnore[$ignoredFile][$line]);
								unset($unmatchedLineIgnores[$ignoredFile][$line]);
							}
						}
					}
				}

				$localIgnoresProcessorResult = $this->localIgnoresProcessor->process(
					$temporaryFileErrors,
					$linesToIgnore,
					$unmatchedLineIgnores,
				);
				foreach ($localIgnoresProcessorResult->getFileErrors() as $fileError) {
					$fileErrors[] = $fileError;
				}
				foreach ($localIgnoresProcessorResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
					$locallyIgnoredErrors[] = $locallyIgnoredError;
				}
				$linesToIgnore = $localIgnoresProcessorResult->getLinesToIgnore();
				$unmatchedLineIgnores = $localIgnoresProcessorResult->getUnmatchedLineIgnores();
			} catch (\PhpParser\Error $e) {
				$fileErrors[] = (new Error($e->getRawMessage(), $file, $e->getStartLine() !== -1 ? $e->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
			} catch (ParserErrorsException $e) {
				foreach ($e->getErrors() as $error) {
					$fileErrors[] = (new Error($error->getMessage(), $e->getParsedFile() ?? $file, $error->getLine() !== -1 ? $error->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
				}
			} catch (AnalysedCodeException $e) {
				$fileErrors[] = (new Error($e->getMessage(), $file, canBeIgnored: $e, tip: $e->getTip()))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
			} catch (IdentifierNotFound $e) {
				$fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, canBeIgnored: $e, tip: 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
			} catch (UnableToCompileNode | CircularReference $e) {
				$fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, canBeIgnored: $e))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
			} finally {
				$this->restoreCollectErrorsHandler();
			}
		} elseif (is_dir($file)) {
			$fileErrors[] = (new Error(sprintf('File %s is a directory.', $file), $file, canBeIgnored: false))->withIdentifier('phpstan.path');
		} else {
			$fileErrors[] = (new Error(sprintf('File %s does not exist.', $file), $file, canBeIgnored: false))->withIdentifier('phpstan.path');
		}

		foreach ($linesToIgnore as $fileKey => $lines) {
			if (count($lines) > 0) {
				continue;
			}

			unset($linesToIgnore[$fileKey]);
		}

		foreach ($unmatchedLineIgnores as $fileKey => $lines) {
			if (count($lines) > 0) {
				continue;
			}

			unset($unmatchedLineIgnores[$fileKey]);
		}

		return new FileAnalyserResult(
			$fileErrors,
			array_values($this->filteredPhpErrors),
			array_values($this->allPhpErrors),
			$locallyIgnoredErrors,
			$fileCollectedData,
			array_values(array_unique($fileDependencies)),
			array_values(array_unique($usedTraitFileDependencies)),
			array_values(array_unique($filePackageDependencies)),
			$exportedNodes,
			$linesToIgnore,
			$unmatchedLineIgnores,
			$processedFiles,
		);
	}

	/**
	 * @param array<string, true> $analysedFiles
	 */
	private function collectErrors(array $analysedFiles): void
	{
		$this->filteredPhpErrors = [];
		$this->allPhpErrors = [];
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($analysedFiles): bool {
			if ((error_reporting() & $errno) === 0) {
				// silence @ operator
				return true;
			}

			$errorMessage = sprintf('%s: %s', $this->getErrorLabel($errno), $errstr);
			$errorSignature = hash('sha256', sprintf('%s:%s::%s', $errfile, $errline, $errorMessage));

			$this->allPhpErrors[$errorSignature] = (new Error($errorMessage, $errfile, $errline, false))->withIdentifier('phpstan.php');

			if ($errno === E_DEPRECATED) {
				return true;
			}

			if (!isset($analysedFiles[$errfile])) {
				return true;
			}

			$this->filteredPhpErrors[$errorSignature] = (new Error($errorMessage, $errfile, $errline, $errno === E_USER_DEPRECATED))->withIdentifier('phpstan.php');

			return true;
		});
	}

	private function restoreCollectErrorsHandler(): void
	{
		restore_error_handler();
	}

	private function getErrorLabel(int $errno): string
	{
		switch ($errno) {
			case E_ERROR:
				return 'Fatal error';
			case E_WARNING:
				return 'Warning';
			case E_PARSE:
				return 'Parse error';
			case E_NOTICE:
				return 'Notice';
			case E_DEPRECATED:
				return 'Deprecated';
			case E_USER_ERROR:
				return 'User error (E_USER_ERROR)';
			case E_USER_WARNING:
				return 'User warning (E_USER_WARNING)';
			case E_USER_NOTICE:
				return 'User notice (E_USER_NOTICE)';
			case E_USER_DEPRECATED:
				return 'Deprecated (E_USER_DEPRECATED)';
			case E_STRICT:
				return 'Strict error (E_STRICT)';
		}

		return 'Unknown PHP error';
	}

}
