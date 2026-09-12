<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PhpParser\Token;
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
use PHPStan\Rules\RuleErrors\TransformedRuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\ShouldNotHappenException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function array_key_exists;
use function array_shift;
use function count;
use function get_class;
use function hash;
use function str_contains;
use function str_repeat;

#[AutowiredService]
final class RuleErrorTransformer
{

	private const PARSED_FILES_LIMIT = 4;

	private const PRINTERS_LIMIT = 4;

	private Differ $differ;

	/** @var array<string, array{code: string, hash: string, tokens: Token[], indent: string|null}> */
	private array $parsedFiles = [];

	/** @var array<string, PhpPrinter> */
	private array $printers = [];

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
		if ($ruleError instanceof TransformedRuleError) {
			return $ruleError->getError();
		}

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

			$fixedErrorDiff = $this->fix(
				$traitFilePath ?? $filePath,
				$fileNodes,
				$ruleError->getOriginalNode(),
				$ruleError->getNewNodeCallable(),
			);
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

	/**
	 * @param Node\Stmt[] $fileNodes
	 * @param callable(Node): Node $newNodeCallable
	 */
	private function fix(string $fixingFile, array $fileNodes, Node $originalNode, callable $newNodeCallable): ?FixedErrorDiff
	{
		if ($fileNodes === []) {
			return null;
		}

		$parsedFile = $this->getParsedFile($fixingFile);

		$indentDetector = null;
		$visitors = [];
		if ($parsedFile['indent'] === null) {
			$indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($parsedFile['tokens'], PhpPrinter::TAB_WIDTH));
			$visitors[] = $indentDetector;
		}
		$visitors[] = new CloningVisitor();
		$replacingVisitor = new ReplacingNodeVisitor($originalNode, $newNodeCallable);
		$visitors[] = $replacingVisitor;

		/** @var Stmt[] $newStmts */
		$newStmts = (new NodeTraverser(...$visitors))->traverse($fileNodes);

		$indent = $parsedFile['indent'];
		if ($indentDetector !== null) {
			if (str_contains($indentDetector->indentCharacter, "\t")) {
				$indent = "\t";
			} else {
				$indent = str_repeat($indentDetector->indentCharacter, $indentDetector->indentSize);
			}

			if ($indentDetector->found) {
				$this->parsedFiles[$fixingFile]['indent'] = $indent;
			}
		}

		if (!$replacingVisitor->isFound()) {
			return null;
		}

		$newCode = $this->getPrinter($indent)->printFormatPreserving($newStmts, $fileNodes, $parsedFile['tokens']);
		if ($parsedFile['code'] === $newCode) {
			return null;
		}

		return new FixedErrorDiff($parsedFile['hash'], $this->differ->diff($parsedFile['code'], $newCode));
	}

	/**
	 * @return array{code: string, hash: string, tokens: Token[], indent: string|null}
	 */
	private function getParsedFile(string $file): array
	{
		if (array_key_exists($file, $this->parsedFiles)) {
			return $this->parsedFiles[$file];
		}

		$code = FileReader::read($file);
		$this->parser->parse($code);

		if (count($this->parsedFiles) >= self::PARSED_FILES_LIMIT) {
			array_shift($this->parsedFiles);
		}

		return $this->parsedFiles[$file] = [
			'code' => $code,
			'hash' => hash('sha256', $code),
			'tokens' => $this->parser->getTokens(),
			'indent' => null,
		];
	}

	/**
	 * Printers are reused because PhpPrinter memoizes several sizeable lookup tables
	 * the first time printFormatPreserving() is called on it.
	 */
	private function getPrinter(string $indent): PhpPrinter
	{
		if (array_key_exists($indent, $this->printers)) {
			return $this->printers[$indent];
		}

		if (count($this->printers) >= self::PRINTERS_LIMIT) {
			array_shift($this->printers);
		}

		return $this->printers[$indent] = new PhpPrinter(['indent' => $indent]);
	}

}
