<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\Fixable\ReplacingNodeVisitor;
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
use function str_contains;
use function str_repeat;

#[AutowiredService]
final class RuleErrorTransformer
{

	private Differ $differ;

	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionPhpParser')]
		private Parser $parser,
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

		$fixedErrorDiff = null;
		if ($ruleError instanceof FixableNodeRuleError) {
			if ($ruleError->getOriginalNode() instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot fix virtual node');
			}
			$fixingFile = $filePath;
			if ($traitFilePath !== null) {
				$fixingFile = $traitFilePath;
			}

			$oldCode = FileReader::read($fixingFile);

			$this->parser->parse($oldCode);
			$hash = hash('sha256', $oldCode);
			$oldTokens = $this->parser->getTokens();

			$indentTraverser = new NodeTraverser();
			$indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($oldTokens, PhpPrinter::TAB_WIDTH));
			$indentTraverser->addVisitor($indentDetector);
			$indentTraverser->traverse($fileNodes);

			$cloningTraverser = new NodeTraverser();
			$cloningTraverser->addVisitor(new CloningVisitor());

			/** @var Stmt[] $newStmts */
			$newStmts = $cloningTraverser->traverse($fileNodes);

			$traverser = new NodeTraverser();
			$visitor = new ReplacingNodeVisitor($ruleError->getOriginalNode(), $ruleError->getNewNodeCallable());
			$traverser->addVisitor($visitor);

			/** @var Stmt[] $newStmts */
			$newStmts = $traverser->traverse($newStmts);

			if ($visitor->isFound()) {
				if (str_contains($indentDetector->indentCharacter, "\t")) {
					$indent = "\t";
				} else {
					$indent = str_repeat($indentDetector->indentCharacter, $indentDetector->indentSize);
				}
				$printer = new PhpPrinter(['indent' => $indent]);
				$newCode = $printer->printFormatPreserving($newStmts, $fileNodes, $oldTokens);

				if ($oldCode !== $newCode) {
					$fixedErrorDiff = new FixedErrorDiff($hash, $this->differ->diff($oldCode, $newCode));
				}
			}
		}

		return new Error(
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
			$fixedErrorDiff,
		);
	}

}
