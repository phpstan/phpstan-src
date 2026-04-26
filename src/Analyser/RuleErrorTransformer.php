<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Fixable\BatchReplacingNodeVisitor;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\Fixable\ReplacingNodeVisitor;
use PHPStan\Node\DeepNodeCloner;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\ShouldNotHappenException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function get_class;
use function hash;
use function spl_object_id;
use function str_contains;
use function str_repeat;

#[AutowiredService]
final class RuleErrorTransformer
{

	private Differ $differ;

	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionPhpParser')]
		private Parser $parser,
		private DeepNodeCloner $deepNodeCloner,
	)
	{
		$this->differ = new Differ(new UnifiedDiffOutputBuilder('', addLineNumbers: true));
	}

	/**
	 * @param Node\Stmt[] $fileNodes
	 */
	public function transform(
		RuleError $ruleError,
		Scope $scope,
		array $fileNodes,
		Node $node,
	): Error
	{
		[$error, $pendingFix] = $this->transformPreserveFixable($ruleError, $scope, $node);
		if ($pendingFix === null) {
			return $error;
		}

		$diff = $this->buildSinglePendingFixDiff($pendingFix, $fileNodes);
		if ($diff === null) {
			return $error;
		}

		return $error->withFixedErrorDiff($diff);
	}

	/**
	 * @return array{Error, PendingFix|null}
	 */
	public function transformPreserveFixable(
		RuleError $ruleError,
		Scope $scope,
		Node $node,
	): array
	{
		$line = $node->getStartLine();
		$canBeIgnored = true;
		$fileName = $scope->getFileDescription();
		$filePath = $scope->getFile();
		$traitFilePath = null;
		$tip = null;
		$identifier = null;
		$metadata = [];
		if ($scope->isInTrait()) {
			$traitReflection = $scope->getTraitReflection();
			if ($traitReflection->getFileName() !== null) {
				$traitFilePath = $traitReflection->getFileName();
			}
		}

		if (
			$ruleError instanceof LineRuleError
			&& $ruleError->getLine() !== -1
		) {
			$line = $ruleError->getLine();
		}
		if (
			$ruleError instanceof FileRuleError
			&& $ruleError->getFile() !== ''
		) {
			$fileName = $ruleError->getFileDescription();
			$filePath = $ruleError->getFile();
			$traitFilePath = null;
		}

		if ($ruleError instanceof TipRuleError) {
			$tip = $ruleError->getTip();
		}

		if ($ruleError instanceof IdentifierRuleError) {
			$identifier = $ruleError->getIdentifier();
		}

		if ($ruleError instanceof MetadataRuleError) {
			$metadata = $ruleError->getMetadata();
		}

		if ($ruleError instanceof NonIgnorableRuleError) {
			$canBeIgnored = false;
		}

		$error = new Error(
			$ruleError->getMessage(),
			$fileName,
			$line,
			$canBeIgnored,
			$filePath,
			$traitFilePath,
			$tip,
			$node->getStartLine(),
			get_class($node),
			$identifier,
			$metadata,
			null,
		);

		$pendingFix = null;
		if ($ruleError instanceof FixableNodeRuleError) {
			if ($ruleError->getOriginalNode() instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot fix virtual node');
			}
			$fixingFile = $traitFilePath ?? $filePath;

			$originalNode = $ruleError->getOriginalNode();
			$clone = $this->deepNodeCloner->cloneNode($originalNode);
			$newNode = ($ruleError->getNewNodeCallable())($clone);
			if ($newNode instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot print VirtualNode.');
			}

			$pendingFix = new PendingFix(
				$error,
				$originalNode,
				static fn (Node $clonedNode): Node => $newNode,
				$fixingFile,
			);
		}

		return [$error, $pendingFix];
	}

	/**
	 * @param array<string, list<PendingFix>> $pendingFixesByFile
	 * @param array<string, Node\Stmt[]> $parserNodesByFile
	 * @return array<int, FixedErrorDiff>
	 */
	public function finalizePendingFixes(array $pendingFixesByFile, array $parserNodesByFile): array
	{
		$diffsByErrorId = [];

		foreach ($pendingFixesByFile as $filePath => $pendingFixes) {
			if ($pendingFixes === []) {
				continue;
			}

			$fileNodes = $parserNodesByFile[$filePath] ?? null;
			if ($fileNodes === null) {
				continue;
			}

			$batchFixes = [];
			foreach ($pendingFixes as $pendingFix) {
				$batchFixes[] = [
					'node' => $pendingFix->originalNode,
					'callable' => $pendingFix->newNodeCallable,
				];
			}
			$replacing = new BatchReplacingNodeVisitor($batchFixes);

			$diff = $this->buildDiffFromVisitor($filePath, $fileNodes, $replacing);
			if ($diff === null) {
				continue;
			}

			$appliedSet = [];
			foreach ($replacing->getAppliedOriginalNodes() as $applied) {
				$appliedSet[spl_object_id($applied)] = true;
			}

			foreach ($pendingFixes as $pendingFix) {
				if (!isset($appliedSet[spl_object_id($pendingFix->originalNode)])) {
					continue;
				}
				$diffsByErrorId[spl_object_id($pendingFix->error)] = $diff;
			}
		}

		return $diffsByErrorId;
	}

	/**
	 * @param Node\Stmt[] $fileNodes
	 */
	private function buildSinglePendingFixDiff(PendingFix $pendingFix, array $fileNodes): ?FixedErrorDiff
	{
		$visitor = new ReplacingNodeVisitor($pendingFix->originalNode, $pendingFix->newNodeCallable);
		$diff = $this->buildDiffFromVisitor($pendingFix->fixingFilePath, $fileNodes, $visitor);
		if ($diff === null || !$visitor->isFound()) {
			return null;
		}

		return $diff;
	}

	/**
	 * @param Node\Stmt[] $fileNodes
	 */
	private function buildDiffFromVisitor(
		string $filePath,
		array $fileNodes,
		NodeVisitorAbstract $replacingVisitor,
	): ?FixedErrorDiff
	{
		$oldCode = FileReader::read($filePath);
		$this->parser->parse($oldCode);
		$hash = hash('sha256', $oldCode);
		$oldTokens = $this->parser->getTokens();

		$indentTraverser = new NodeTraverser();
		$indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($oldTokens, PhpPrinter::TAB_WIDTH));
		$indentTraverser->addVisitor($indentDetector);
		$indentTraverser->traverse($fileNodes);

		$cloningTraverser = new NodeTraverser();
		$cloningTraverser->addVisitor(new CloningVisitor());
		$newStmts = $cloningTraverser->traverse($fileNodes);

		$traverser = new NodeTraverser();
		$traverser->addVisitor($replacingVisitor);
		$newStmts = $traverser->traverse($newStmts);

		if (str_contains($indentDetector->indentCharacter, "\t")) {
			$indent = "\t";
		} else {
			$indent = str_repeat($indentDetector->indentCharacter, $indentDetector->indentSize);
		}
		$printer = new PhpPrinter(['indent' => $indent]);
		$newCode = $printer->printFormatPreserving($newStmts, $fileNodes, $oldTokens);

		if ($oldCode === $newCode) {
			return null;
		}

		return new FixedErrorDiff($hash, $this->differ->diff($oldCode, $newCode));
	}

}
