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
use PHPStan\Dependency\RootExportedNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Parser\Parser;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_keys;
use function get_class;
use function spl_object_id;
use function sprintf;

/**
 * @phpstan-import-type CollectorData from CollectedData
 * @phpstan-import-type Identifier from FileAnalyserResult
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
final class FileAnalyserCallback
{

	/** @var list<Error> */
	private array $fileErrors = [];

	/** @var CollectorData */
	private array $fileCollectedData = [];

	/** @var array<string> */
	private array $fileDependencies = [];

	/** @var array<string> */
	private array $usedTraitFileDependencies = [];

	/** @var list<RootExportedNode> */
	private array $exportedNodes = [];

	/** @var list<Error> */
	private array $temporaryFileErrors = [];

	/** @var array<string, list<PendingFix>> */
	private array $pendingFixesByFile = [];

	/** @var LinesToIgnore */
	private array $linesToIgnore;

	/** @var LinesToIgnore */
	private array $unmatchedLineIgnores;

	/**
	 * @param array<string, true> $analysedFiles
	 * @param callable(Node $node, Scope $scope): void|null $outerNodeCallback
	 * @param Node\Stmt[] $parserNodes
	 * @param IgnoreErrorExtension[] $ignoreErrorExtensions
	 * @param list<string> $processedFiles
	 */
	public function __construct(
		private string $file,
		private array $analysedFiles,
		private RuleRegistry $ruleRegistry,
		private CollectorRegistry $collectorRegistry,
		private $outerNodeCallback,
		private array $parserNodes,
		private array $ignoreErrorExtensions,
		private Parser $parser,
		private DependencyResolver $dependencyResolver,
		private RuleErrorTransformer $ruleErrorTransformer,
		private array $processedFiles,
	)
	{
		$this->linesToIgnore = $this->unmatchedLineIgnores = [$file => $this->getLinesToIgnoreFromTokens($parserNodes)];
	}

	public function __invoke(Node $node, Scope $scope): void
	{
		/** @var Scope&NodeCallbackInvoker $scope */
		if ($node instanceof Node\Stmt\Trait_) {
			foreach (array_keys($this->linesToIgnore[$this->file] ?? []) as $lineToIgnore) {
				if ($lineToIgnore < $node->getStartLine() || $lineToIgnore > $node->getEndLine()) {
					continue;
				}

				unset($this->unmatchedLineIgnores[$this->file][$lineToIgnore]);
			}
		}
		if ($node instanceof InTraitNode) {
			$traitNode = $node->getOriginalNode();
			$fileDescription = $scope->getFileDescription();
			$this->linesToIgnore[$fileDescription] ??= [];
			$this->linesToIgnore[$fileDescription] += $this->getLinesToIgnoreFromTokens([$traitNode]);

			$traitFileName = $node->getTraitReflection()->getFileName();
			if ($traitFileName !== null) {
				$this->processedFiles[] = $traitFileName;
			}
		}

		if ($this->outerNodeCallback !== null) {
			($this->outerNodeCallback)($node, $scope);
		}
		$uniquedAnalysedCodeExceptionMessages = [];
		$nodeType = get_class($node);
		foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
			try {
				$ruleErrors = $rule->processNode($node, $scope);
			} catch (AnalysedCodeException $e) {
				if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
					continue;
				}

				$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
				$this->fileErrors[] = (new Error($e->getMessage(), $this->file, $node->getStartLine(), $e, tip: $e->getTip()))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (IdentifierNotFound $e) {
				$this->fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $this->file, $node->getStartLine(), $e, tip: 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (UnableToCompileNode | CircularReference $e) {
				$this->fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $this->file, $node->getStartLine(), $e))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			}

			foreach ($ruleErrors as $ruleError) {
				[$error, $pendingFix] = $this->ruleErrorTransformer->transformPreserveFixable($ruleError, $scope, $node);

				if ($error->canBeIgnored()) {
					foreach ($this->ignoreErrorExtensions as $ignoreErrorExtension) {
						if ($ignoreErrorExtension->shouldIgnore($error, $node, $scope)) {
							continue 2;
						}
					}
				}

				$this->temporaryFileErrors[] = $error;
				if ($pendingFix === null) {
					continue;
				}

				$this->pendingFixesByFile[$pendingFix->fixingFilePath][] = $pendingFix;
			}
		}

		foreach ($this->collectorRegistry->getCollectors($nodeType) as $collector) {
			try {
				$collectedData = $collector->processNode($node, $scope);
			} catch (AnalysedCodeException $e) {
				if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
					continue;
				}

				$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
				$this->fileErrors[] = (new Error($e->getMessage(), $this->file, $node->getStartLine(), $e, tip: $e->getTip()))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (IdentifierNotFound $e) {
				$this->fileErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $this->file, $node->getStartLine(), $e, tip: 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (UnableToCompileNode | CircularReference $e) {
				$this->fileErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $this->file, $node->getStartLine(), $e))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			}

			if ($collectedData === null) {
				continue;
			}

			$this->fileCollectedData[$scope->getFile()][get_class($collector)][] = $collectedData;
		}

		try {
			$dependencies = $this->dependencyResolver->resolveDependencies($node, $scope);
			foreach ($dependencies->getFileDependencies($scope->getFile(), $this->analysedFiles) as $dependentFile) {
				$this->fileDependencies[] = $dependentFile;
			}
			if ($dependencies->getExportedNode() !== null) {
				$this->exportedNodes[] = $dependencies->getExportedNode();
			}
		} catch (AnalysedCodeException) {
			// pass
		} catch (IdentifierNotFound) {
			// pass
		} catch (UnableToCompileNode) {
			// pass
		}

		if (!$node instanceof InClassNode) {
			return;
		}

		$usedTraitDependencies = $this->dependencyResolver->resolveUsedTraitDependencies($node);
		foreach ($usedTraitDependencies->getFileDependencies($scope->getFile(), $this->analysedFiles) as $dependentFile) {
			$this->usedTraitFileDependencies[] = $dependentFile;
		}
	}

	/**
	 * @param Node[] $nodes
	 * @return array<int, non-empty-list<Identifier>|null>
	 */
	private function getLinesToIgnoreFromTokens(array $nodes): array
	{
		if (!isset($nodes[0])) {
			return [];
		}

		/** @var array<int, non-empty-list<Identifier>|null> */
		return $nodes[0]->getAttribute('linesToIgnore', []);
	}

	/**
	 * @return list<Error>
	 */
	public function getFileErrors(): array
	{
		return $this->fileErrors;
	}

	/**
	 * @return CollectorData
	 */
	public function getFileCollectedData(): array
	{
		return $this->fileCollectedData;
	}

	/**
	 * @return array<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

	/**
	 * @return array<string>
	 */
	public function getUsedTraitFileDependencies(): array
	{
		return $this->usedTraitFileDependencies;
	}

	/**
	 * @return list<RootExportedNode>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getLinesToIgnore(): array
	{
		return $this->linesToIgnore;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getUnmatchedLineIgnores(): array
	{
		return $this->unmatchedLineIgnores;
	}

	/**
	 * @return list<Error>
	 */
	public function getTemporaryFileErrors(): array
	{
		if ($this->pendingFixesByFile === []) {
			return $this->temporaryFileErrors;
		}

		$parserNodesByFile = [$this->file => $this->parserNodes];
		foreach (array_keys($this->pendingFixesByFile) as $fixingFilePath) {
			if (isset($parserNodesByFile[$fixingFilePath])) {
				continue;
			}
			$parserNodesByFile[$fixingFilePath] = $this->parser->parseFile($fixingFilePath);
		}

		$diffsByErrorId = $this->ruleErrorTransformer->finalizePendingFixes(
			$this->pendingFixesByFile,
			$parserNodesByFile,
		);

		if ($diffsByErrorId === []) {
			return $this->temporaryFileErrors;
		}

		$finalErrors = [];
		foreach ($this->temporaryFileErrors as $error) {
			$diff = $diffsByErrorId[spl_object_id($error)] ?? null;
			$finalErrors[] = $diff !== null ? $error->withFixedErrorDiff($diff) : $error;
		}

		return $finalErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getProcessedFiles(): array
	{
		return $this->processedFiles;
	}

}
