<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\If_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function dirname;
use function str_starts_with;

/**
 * @implements Rule<If_>
 */
final class OrChainIdenticalComparisonToInArrayRule implements Rule
{

	public function __construct(
		private ExprPrinter $printer,
		private FileHelper $fileHelper,
		private bool $skipTests = true,
	)
	{
	}

	public function getNodeType(): string
	{
		return If_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = $this->processConditionNode($node->cond, $scope);
		foreach ($node->elseifs as $elseifCondNode) {
			$errors = array_merge($errors, $this->processConditionNode($elseifCondNode->cond, $scope));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processConditionNode(Expr $condNode, Scope $scope): array
	{
		$comparisons = $this->unpackOrChain($condNode);
		if (count($comparisons) < 2) {
			return [];
		}

		$firstComparison = array_shift($comparisons);
		if (!$firstComparison instanceof Identical) {
			return [];
		}

		$subjectAndValue = $this->getSubjectAndValue($firstComparison);
		if ($subjectAndValue === null) {
			return [];
		}

		if ($this->skipTests && str_starts_with($this->fileHelper->normalizePath($scope->getFile()), $this->fileHelper->normalizePath(dirname(__DIR__, 3) . '/tests'))) {
			return [];
		}

		$subjectNode = $subjectAndValue['subject'];
		$subjectStr = $this->printer->printExpr($subjectNode);
		$values = [$subjectAndValue['value']];

		foreach ($comparisons as $comparison) {
			if (!$comparison instanceof Identical) {
				return [];
			}

			$currentSubjectAndValue = $this->getSubjectAndValue($comparison);
			if ($currentSubjectAndValue === null) {
				return [];
			}

			if ($this->printer->printExpr($currentSubjectAndValue['subject']) !== $subjectStr) {
				return [];
			}

			$values[] = $currentSubjectAndValue['value'];
		}

		$errorBuilder = RuleErrorBuilder::message('This chain of identical comparisons can be simplified using in_array().')
			->line($condNode->getStartLine())
			->fixNode($condNode, static fn (Expr $node) => self::createInArrayCall($subjectNode, $values))
			->identifier('or.chainIdenticalComparison');

		return [$errorBuilder->build()];
	}

	/**
	 * @return list<Expr>
	 */
	private function unpackOrChain(Expr $node): array
	{
		if ($node instanceof BooleanOr) {
			return [...$this->unpackOrChain($node->left), ...$this->unpackOrChain($node->right)];
		}

		return [$node];
	}

	/**
	 * @phpstan-assert-if-true Scalar|ClassConstFetch|ConstFetch $node
	 */
	private static function isSubjectNode(Expr $node): bool
	{
		return $node instanceof Scalar || $node instanceof ClassConstFetch || $node instanceof ConstFetch;
	}

	/**
	 * @return array{subject: Expr, value: Scalar|ClassConstFetch|ConstFetch}|null
	 */
	private function getSubjectAndValue(Identical $comparison): ?array
	{
		if (self::isSubjectNode($comparison->left) && !self::isSubjectNode($comparison->left)) {
			return ['subject' => $comparison->right, 'value' => $comparison->left];
		}

		if (!self::isSubjectNode($comparison->left) && self::isSubjectNode($comparison->right)) {
			return ['subject' => $comparison->left, 'value' => $comparison->right];
		}

		return null;
	}

	/**
	 * @param list<Scalar|ClassConstFetch|ConstFetch> $values
	 */
	private static function createInArrayCall(Expr $subjectNode, array $values): FuncCall
	{
		return new FuncCall(new Name('\in_array'), [
			new Arg($subjectNode),
			new Arg(new Array_(array_map(static fn ($value) => new ArrayItem($value), $values))),
			new Arg(new ConstFetch(new Name('true'))),
		]);
	}

}
