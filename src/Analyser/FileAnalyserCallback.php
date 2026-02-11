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
use PHPStan\Node\InClassNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Parser\Parser;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_keys;
use function get_class;
use function sprintf;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
final class FileAnalyserCallback
{

	/** @var list<Error> */
	private array $fileErrors = [];

	/** @var CollectorData */
	private array $fileCollectedData = [];

	private array $fileDependencies = [];

	private array $usedTraitFileDependencies = [];

	private array $exportedNodes = [];

	private array $temporaryFileErrors = [];

	private array $linesToIgnore;

	private array $unmatchedLineIgnores;

	/**
	 * @param array<string, true> $analysedFiles
	 * @param callable(Node $node, Scope $scope): void|null $outerNodeCallback
	 * @param Node\Stmt[] $parserNodes
	 * @param IgnoreErrorExtension[] $ignoreErrorExtensions
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
		$parserNodes = $this->parserNodes;

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

		if ($scope->isInTrait()) {
			$traitReflection = $scope->getTraitReflection();
			if ($traitReflection->getFileName() !== null) {
				$traitFilePath = $traitReflection->getFileName();
				$parserNodes = $this->parser->parseFile($traitFilePath);
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
				$error = $this->ruleErrorTransformer->transform($ruleError, $scope, $parserNodes, $node);

				if ($error->canBeIgnored()) {
					foreach ($this->ignoreErrorExtensions as $ignoreErrorExtension) {
						if ($ignoreErrorExtension->shouldIgnore($error, $node, $scope)) {
							continue 2;
						}
					}
				}

				$this->temporaryFileErrors[] = $error;
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
	 * @return list<Error>
	 */
	public function getFileErrors(): array
	{
		return $this->fileErrors;
	}

	public function getFileCollectedData(): array
	{
		return $this->fileCollectedData;
	}

	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

	public function getUsedTraitFileDependencies(): array
	{
		return $this->usedTraitFileDependencies;
	}

	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	public function getLinesToIgnore(): array
	{
		return $this->linesToIgnore;
	}

	public function getUnmatchedLineIgnores(): array
	{
		return $this->unmatchedLineIgnores;
	}

	public function getTemporaryFileErrors(): array
	{
		return $this->temporaryFileErrors;
	}

	public function getProcessedFiles(): array
	{
		return $this->processedFiles;
	}

}
