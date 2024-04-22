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
use PHPStan\Node\FileNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function error_reporting;
use function get_class;
use function is_dir;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use const E_DEPRECATED;

class FileAnalyser
{

	/** @var list<Error> */
	private array $collectedErrors = [];

	public function __construct(
		private ScopeFactory $scopeFactory,
		private NodeScopeResolver $nodeScopeResolver,
		private Parser $parser,
		private DependencyResolver $dependencyResolver,
		private RuleErrorTransformer $ruleErrorTransformer,
		private LocalIgnoresProcessor $localIgnoresProcessor,
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

		/** @var list<Error> $locallyIgnoredErrors */
		$locallyIgnoredErrors = [];

		/** @var list<CollectedData> $fileCollectedData */
		$fileCollectedData = [];

		$fileDependencies = [];
		$exportedNodes = [];
		$linesToIgnore = [];
		$unmatchedLineIgnores = [];
		if (is_file($file)) {
			try {
				$this->collectErrors($analysedFiles);
				$parserNodes = $this->parser->parseFile($file);
				$linesToIgnore = $unmatchedLineIgnores = [$file => $this->getLinesToIgnoreFromTokens($parserNodes)];
				$temporaryFileErrors = [];
				$nodeCallback = function (Node $node, Scope $scope) use (&$fileErrors, &$fileCollectedData, &$fileDependencies, &$exportedNodes, $file, $ruleRegistry, $collectorRegistry, $outerNodeCallback, $analysedFiles, &$linesToIgnore, &$unmatchedLineIgnores, &$temporaryFileErrors): void {
					if ($node instanceof Node\Stmt\Trait_) {
						foreach (array_keys($linesToIgnore[$file] ?? []) as $lineToIgnore) {
							if ($lineToIgnore < $node->getStartLine() || $lineToIgnore > $node->getEndLine()) {
								continue;
							}

							unset($unmatchedLineIgnores[$file][$lineToIgnore]);
						}
					}
					if ($node instanceof InTraitNode) {
						$traitNode = $node->getOriginalNode();
						$linesToIgnore[$scope->getFileDescription()] = $this->getLinesToIgnoreFromTokens([$traitNode]);
					}
					if ($outerNodeCallback !== null) {
						$outerNodeCallback($node, $scope);
					}
					$uniquedAnalysedCodeExceptionMessages = [];
					$nodeType = get_class($node);
					foreach ($ruleRegistry->getRules($nodeType) as $rule) {
						try {
							$ruleErrors = $rule->processNode($node, $scope);
						} catch (AnalysedCodeException $e) {
							if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
								continue;
							}

							$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
							$fileErrors[] = (new Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal');
							continue;
						} catch (IdentifierNotFound $e) {
							$fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection');
							continue;
						} catch (UnableToCompileNode | CircularReference $e) {
							$fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection');
							continue;
						}

						foreach ($ruleErrors as $ruleError) {
							$temporaryFileErrors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getStartLine());
						}
					}

					foreach ($collectorRegistry->getCollectors($nodeType) as $collector) {
						try {
							$collectedData = $collector->processNode($node, $scope);
						} catch (AnalysedCodeException $e) {
							if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
								continue;
							}

							$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
							$fileErrors[] = (new Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal');
							continue;
						} catch (IdentifierNotFound $e) {
							$fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection');
							continue;
						} catch (UnableToCompileNode | CircularReference $e) {
							$fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection');
							continue;
						}

						if ($collectedData === null) {
							continue;
						}

						$fileCollectedData[] = new CollectedData(
							$collectedData,
							$scope->getFile(),
							get_class($collector),
						);
					}

					try {
						$dependencies = $this->dependencyResolver->resolveDependencies($node, $scope);
						foreach ($dependencies->getFileDependencies($scope->getFile(), $analysedFiles) as $dependentFile) {
							$fileDependencies[] = $dependentFile;
						}
						if ($dependencies->getExportedNode() !== null) {
							$exportedNodes[] = $dependencies->getExportedNode();
						}
					} catch (AnalysedCodeException) {
						// pass
					} catch (IdentifierNotFound) {
						// pass
					} catch (UnableToCompileNode) {
						// pass
					}
				};

				$scope = $this->scopeFactory->create(ScopeContext::create($file));
				$nodeCallback(new FileNode($parserNodes), $scope);
				$this->nodeScopeResolver->processNodes(
					$parserNodes,
					$scope,
					$nodeCallback,
				);

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
				$fileErrors[] = (new Error($e->getMessage(), $file, null, $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal');
			} catch (IdentifierNotFound $e) {
				$fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, null, $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection');
			} catch (UnableToCompileNode | CircularReference $e) {
				$fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, null, $e))->withIdentifier('phpstan.reflection');
			}
		} elseif (is_dir($file)) {
			$fileErrors[] = (new Error(sprintf('File %s is a directory.', $file), $file, null, false))->withIdentifier('phpstan.path');
		} else {
			$fileErrors[] = (new Error(sprintf('File %s does not exist.', $file), $file, null, false))->withIdentifier('phpstan.path');
		}

		$this->restoreCollectErrorsHandler();

		$fileErrors = array_merge($fileErrors, $this->collectedErrors);

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

		return new FileAnalyserResult($fileErrors, $locallyIgnoredErrors, $fileCollectedData, array_values(array_unique($fileDependencies)), $exportedNodes, $linesToIgnore, $unmatchedLineIgnores);
	}

	/**
	 * @param Node[] $nodes
	 * @return array<int, non-empty-list<string>|null>
	 */
	private function getLinesToIgnoreFromTokens(array $nodes): array
	{
		if (!isset($nodes[0])) {
			return [];
		}

		/** @var array<int, non-empty-list<string>|null> */
		return $nodes[0]->getAttribute('linesToIgnore', []);
	}

	/**
	 * @param array<string, true> $analysedFiles
	 */
	private function collectErrors(array $analysedFiles): void
	{
		$this->collectedErrors = [];
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($analysedFiles): bool {
			if ((error_reporting() & $errno) === 0) {
				// silence @ operator
				return true;
			}

			if ($errno === E_DEPRECATED) {
				return true;
			}

			if (!isset($analysedFiles[$errfile])) {
				return true;
			}

			$this->collectedErrors[] = (new Error($errstr, $errfile, $errline, true))->withIdentifier('phpstan.php');

			return true;
		});
	}

	private function restoreCollectErrorsHandler(): void
	{
		restore_error_handler();
	}

}
