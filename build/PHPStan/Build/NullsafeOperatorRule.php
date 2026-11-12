<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function is_string;

/**
 * Reports null checks that the nullsafe operator replaces:
 * `$a !== null ? $a->b->c() : null` and `if ($a !== null) { $a->b(); }`.
 *
 * Only expressions that can be evaluated twice without side effects are considered,
 * so that the replacement does not change how many times anything is called.
 *
 * @implements Rule<Node>
 */
final class NullsafeOperatorRule implements Rule
{

	public function __construct(
		private ExprPrinter $printer,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// on PHP 7.4, the downgraded code has these null checks in place of the nullsafe operator
		if (!$this->phpVersion->supportsNullsafeOperator()) {
			return [];
		}

		$replacement = $this->createReplacement($node);
		if ($replacement === null) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message('This null check can be replaced with the nullsafe operator (?->).')
			->identifier('phpstan.nullsafeOperator');

		// the printer puts the arguments of the new nullsafe call on one line
		if (!$this->hasMultilineArgs($replacement)) {
			$errorBuilder->fixNode($node, fn (Node $node): Node => $this->createReplacement($node) ?? $node);
		}

		return [$errorBuilder->build()];
	}

	private function createReplacement(Node $node): Expr|Expression|null
	{
		if ($node instanceof Ternary) {
			if ($node->if === null) {
				return null;
			}

			if ($this->isNull($node->else)) {
				$subject = $this->getComparedWithNull($node->cond, NotIdentical::class);
				$chain = $node->if;
			} elseif ($this->isNull($node->if)) {
				$subject = $this->getComparedWithNull($node->cond, Identical::class);
				$chain = $node->else;
			} else {
				return null;
			}

			if ($subject === null) {
				return null;
			}

			return $this->createNullsafeChain($chain, $this->printer->printExpr($subject));
		}

		if (
			!$node instanceof If_
			|| count($node->elseifs) > 0
			|| $node->else !== null
			|| count($node->stmts) !== 1
			|| !$node->stmts[0] instanceof Expression
			|| count($node->stmts[0]->getComments()) > 0
		) {
			return null;
		}

		$expr = $node->stmts[0]->expr;
		if (!$expr instanceof MethodCall && !$expr instanceof NullsafeMethodCall) {
			return null;
		}

		$subject = $this->getComparedWithNull($node->cond, NotIdentical::class);
		if ($subject === null) {
			return null;
		}

		$chain = $this->createNullsafeChain($expr, $this->printer->printExpr($subject));
		if ($chain === null) {
			return null;
		}

		return new Expression($chain, ['comments' => $node->getComments()]);
	}

	/**
	 * @param class-string<Identical|NotIdentical> $comparisonClass
	 */
	private function getComparedWithNull(Expr $cond, string $comparisonClass): ?Expr
	{
		if (!$cond instanceof $comparisonClass) {
			return null;
		}

		if ($this->isNull($cond->right)) {
			$subject = $cond->left;
		} elseif ($this->isNull($cond->left)) {
			$subject = $cond->right;
		} else {
			return null;
		}

		return $this->isFreeOfSideEffects($subject) ? $subject : null;
	}

	private function isFreeOfSideEffects(Expr $expr): bool
	{
		if ($expr instanceof Variable) {
			return is_string($expr->name);
		}

		if ($expr instanceof PropertyFetch) {
			return $expr->name instanceof Identifier && $this->isFreeOfSideEffects($expr->var);
		}

		return $expr instanceof StaticPropertyFetch && $expr->class instanceof Name && $expr->name instanceof VarLikeIdentifier;
	}

	/**
	 * Replaces the first -> on $subjectString in the chain with ?->, which also short-circuits the rest of the chain.
	 */
	private function createNullsafeChain(Expr $chain, string $subjectString): ?Expr
	{
		if ($chain instanceof MethodCall && $chain->isFirstClassCallable()) {
			return null;
		}

		if (
			($chain instanceof PropertyFetch || $chain instanceof MethodCall)
			&& $this->printer->printExpr($chain->var) === $subjectString
		) {
			if ($chain instanceof PropertyFetch) {
				return new NullsafePropertyFetch($chain->var, $chain->name);
			}

			return new NullsafeMethodCall($chain->var, $chain->name, $chain->args);
		}

		if (
			!$chain instanceof PropertyFetch
			&& !$chain instanceof MethodCall
			&& !$chain instanceof NullsafePropertyFetch
			&& !$chain instanceof NullsafeMethodCall
			&& !$chain instanceof ArrayDimFetch
		) {
			return null;
		}

		$var = $this->createNullsafeChain($chain->var, $subjectString);
		if ($var === null) {
			return null;
		}

		$chain = clone $chain;
		$chain->var = $var;

		return $chain;
	}

	private function hasMultilineArgs(Node $node): bool
	{
		while ($node instanceof Expression || $node instanceof ArrayDimFetch || $node instanceof PropertyFetch || $node instanceof MethodCall || $node instanceof NullsafePropertyFetch || $node instanceof NullsafeMethodCall) {
			if ($node instanceof Expression) {
				$node = $node->expr;
				continue;
			}

			if ($node instanceof NullsafeMethodCall) {
				foreach ($node->getArgs() as $arg) {
					if ($arg->getStartLine() !== $node->name->getStartLine()) {
						return true;
					}
				}
			}

			$node = $node->var;
		}

		return false;
	}

	private function isNull(?Expr $expr): bool
	{
		return $expr instanceof ConstFetch && $expr->name->toLowerString() === 'null';
	}

}
