<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\Token;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Fixable\NewClassNamesVisitor;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\Fixable\ReplacingNodeVisitor;
use PHPStan\Node\VirtualNode;
use PHPStan\Parser\Parser as AnalysisParser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrors\TransformedRuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\ShouldNotHappenException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function array_key_exists;
use function array_shift;
use function array_slice;
use function count;
use function get_class;
use function hash;
use function is_file;
use function min;
use function preg_split;
use function str_contains;
use function str_repeat;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

#[AutowiredService]
final class RuleErrorTransformer
{

	private const PARSED_FILES_LIMIT = 4;

	private const PRINTERS_LIMIT = 4;

	private Differ $differ;

	private UnifiedDiffOutputBuilder $diffOutputBuilder;

	/** @var array<string, array{code: string, hash: string, tokens: Token[], indent: string|null}> */
	private array $parsedFiles = [];

	/** @var array<string, PhpPrinter> */
	private array $printers = [];

	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionPhpParser')]
		private Parser $parser,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private AnalysisParser $analysisParser,
		#[AutowiredParameter(ref: '%fixer.importNames%')]
		private bool $importNames,
		#[AutowiredParameter(ref: '%fixer.preferSelf%')]
		private bool $preferSelf,
	)
	{
		$this->diffOutputBuilder = new UnifiedDiffOutputBuilder('', addLineNumbers: true);
		$this->differ = new Differ($this->diffOutputBuilder);
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

		// the file $fileNodes come from - the error can point to another one with RuleErrorBuilder::file()
		$fileNodesFile = $traitFilePath ?? $filePath;

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

		$fixedErrorDiffs = [];
		if ($ruleError instanceof FixableNodeRuleError) {
			$fixesByFile = [];
			foreach ($ruleError->getNodeFixes() as $nodeFix) {
				if ($nodeFix->node instanceof VirtualNode) {
					throw new ShouldNotHappenException('Cannot fix virtual node');
				}

				$fixesByFile[$nodeFix->file ?? $traitFilePath ?? $filePath][] = $nodeFix;
			}

			foreach ($fixesByFile as $fixingFile => $fixes) {
				$fixedErrorDiff = $this->fix(
					$fixingFile,
					$fixingFile === $fileNodesFile ? $fileNodes : [],
					$fixes,
				);
				if ($fixedErrorDiff === null) {
					// the changes are applied together or not at all
					$fixedErrorDiffs = [];
					break;
				}

				$fixedErrorDiffs[] = $fixedErrorDiff;
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
			$fixedErrorDiffs,
		);
	}

	/**
	 * Without $fileNodes (errors of CollectedDataNode rules, errors transformed inside collectors,
	 * and fixes in another file), the file is parsed with the cached analysis parser. ReplacingNodeVisitor
	 * finds the fixed nodes by their position, so the rule has to take them from a parse of the same file contents.
	 * Returns null when some of the nodes cannot be fixed.
	 *
	 * @param Node\Stmt[] $fileNodes
	 * @param non-empty-list<NodeFix> $fixes
	 */
	private function fix(string $fixingFile, array $fileNodes, array $fixes): ?FixedErrorDiff
	{
		if ($fileNodes === []) {
			if (!is_file($fixingFile)) {
				return null;
			}

			try {
				$fileNodes = $this->analysisParser->parseFile($fixingFile);
			} catch (ParserErrorsException) {
				return null;
			}

			if ($fileNodes === []) {
				return null;
			}
		}

		$parsedFile = $this->getParsedFile($fixingFile);

		$indentDetector = null;
		$visitors = [];
		if ($parsedFile['indent'] === null) {
			$indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($parsedFile['tokens'], PhpPrinter::TAB_WIDTH));
			$visitors[] = $indentDetector;
		}
		$visitors[] = new CloningVisitor();
		$replacingVisitor = new ReplacingNodeVisitor($fixes);
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

		if (!$replacingVisitor->isComplete()) {
			return null;
		}

		if (NewClassNamesVisitor::hasWork($replacingVisitor->getFixedNodes())) {
			$nameResolver = new NameResolver(new Collecting(), ['replaceNodes' => false]);
			/** @var Stmt[] $newStmts */
			$newStmts = (new NodeTraverser(
				$nameResolver,
				new NewClassNamesVisitor($nameResolver->getNameContext(), $this->importNames, $this->preferSelf),
			))->traverse($newStmts);
		}

		$newCode = $this->getPrinter($indent)->printFormatPreserving($newStmts, $fileNodes, $parsedFile['tokens']);
		if ($parsedFile['code'] === $newCode) {
			return null;
		}

		return new FixedErrorDiff($fixingFile, $parsedFile['hash'], $this->diff($parsedFile['code'], $newCode));
	}

	/**
	 * A fix that is not made through nodes, like removing a comment.
	 */
	public function createFixedErrorDiff(string $file, string $originalCode, string $newCode): FixedErrorDiff
	{
		return new FixedErrorDiff($file, hash('sha256', $originalCode), $this->diff($originalCode, $newCode));
	}

	/**
	 * Same output as Differ::diff(), but the common leading and trailing lines are found here in linear time.
	 * Differ strips them too, but in quadratic time, which made each fix in a large file cost up to a second.
	 * The middle part is usually just the fixed node.
	 */
	private function diff(string $from, string $to): string
	{
		$fromLines = self::splitStringByLines($from);
		$toLines = self::splitStringByLines($to);
		$fromCount = count($fromLines);
		$toCount = count($toLines);
		$minCount = min($fromCount, $toCount);

		$startCount = 0;
		while ($startCount < $minCount && $fromLines[$startCount] === $toLines[$startCount]) {
			$startCount++;
		}

		$endCount = 0;
		while (
			$endCount < $minCount - $startCount
			&& $fromLines[$fromCount - 1 - $endCount] === $toLines[$toCount - 1 - $endCount]
		) {
			$endCount++;
		}

		$diff = [];
		for ($i = 0; $i < $startCount; $i++) {
			$diff[] = [$fromLines[$i], Differ::OLD];
		}
		foreach ($this->differ->diffToArray(
			array_slice($fromLines, $startCount, $fromCount - $startCount - $endCount),
			array_slice($toLines, $startCount, $toCount - $startCount - $endCount),
		) as $entry) {
			if ($entry[1] === Differ::DIFF_LINE_END_WARNING) {
				// the warning would be printed as a context line and shift the line numbers in Patcher
				continue;
			}
			$diff[] = $entry;
		}
		for ($i = $fromCount - $endCount; $i < $fromCount; $i++) {
			$diff[] = [$fromLines[$i], Differ::OLD];
		}

		return $this->diffOutputBuilder->getDiff($diff);
	}

	/**
	 * @return list<string>
	 */
	private static function splitStringByLines(string $input): array
	{
		$lines = preg_split('/(.*\R)/', $input, flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		if ($lines === false) {
			throw new ShouldNotHappenException();
		}

		return $lines;
	}

	/**
	 * @return array{code: string, hash: string, tokens: Token[], indent: string|null}
	 */
	private function getParsedFile(string $file): array
	{
		// the file can change between analyses in one process, like PHPStan Pro or tests
		$code = FileReader::read($file);
		if (array_key_exists($file, $this->parsedFiles) && $this->parsedFiles[$file]['code'] === $code) {
			return $this->parsedFiles[$file];
		}

		unset($this->parsedFiles[$file]);
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
