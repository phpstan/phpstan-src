<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\TypeCombinator;

/**
 * New-world replacement for TypeSpecifier::handleDefaultTruthyOrFalseyContext():
 * the default narrowing of an expression used in a boolean context.
 *
 * Unlike the old world there is no nullsafe short-circuiting here: expressions
 * process inside-out, so only NullsafePropertyFetchHandler and
 * NullsafeMethodCallHandler ever see a `?->` — they emit the plain-chain
 * variant alongside their own key once, and every parent simply composes
 * their results. No recursive chain-walking, no type ask.
 */
#[AutowiredService]
final class DefaultNarrowingHelper
{

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	public function specifyDefaultTypes(Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		if (!$context->truthy()) {
			$removedType = StaticTypeFactory::truthy();
		} elseif (!$context->falsey()) {
			$removedType = StaticTypeFactory::falsey();
		} else {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		return (new SpecifiedTypes(sureNotTypes: [
			$this->exprPrinter->printExpr($expr) => [$expr, $removedType],
		]))->setRootExpr($expr);
	}

	/**
	 * The narrowing callback for `?->` expressions, shared by
	 * NullsafePropertyFetchHandler and NullsafeMethodCallHandler — the only two
	 * places that know about short-circuiting (NEW_WORLD.md §3.10). Emits the
	 * plain-chain dual key (one structural getNullsafeShortcircuitedExpr call)
	 * and, when the chain provably executed, a subject-not-null entry.
	 *
	 * @param Expr\NullsafePropertyFetch|Expr\NullsafeMethodCall $expr
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	public function createNullsafeSpecifyCallback(Expr $expr, ExpressionResult $varResult, bool $resultNarrowingAllowed = true): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($varResult, $resultNarrowingAllowed): SpecifiedTypes {
			if (!$e instanceof Expr\NullsafePropertyFetch && !$e instanceof Expr\NullsafeMethodCall) {
				throw new ShouldNotHappenException();
			}

			if ($ctx->null()) {
				return (new SpecifiedTypes([], []))->setRootExpr($e);
			}

			if (!$ctx->truthy()) {
				$removedType = StaticTypeFactory::truthy();
				$chainExecuted = false;
			} elseif (!$ctx->falsey()) {
				$removedType = StaticTypeFactory::falsey();
				// a truthy result cannot have come from the short-circuit null
				$chainExecuted = true;
			} else {
				return (new SpecifiedTypes([], []))->setRootExpr($e);
			}

			// impure calls are not remembered, so narrowing their result is unsound —
			// mirrors the call gate in TypeSpecifier::create() (the subject entry below
			// stays: the chain executing says nothing about the result's purity)
			$sureNotTypes = [];
			if ($resultNarrowingAllowed) {
				$sureNotTypes[$this->exprPrinter->printExpr($e)] = [$e, $removedType];
			}

			$varType = $varResult->getTypeForScope($s);
			$varCanBeNull = TypeCombinator::containsNull($varType);

			if ($resultNarrowingAllowed && ($chainExecuted || !$varCanBeNull)) {
				// the plain-chain variant holds the same narrowing
				$plain = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($e);
				if ($plain !== $e) {
					$sureNotTypes[$this->exprPrinter->printExpr($plain)] = [$plain, $removedType];
				}
			}

			if ($chainExecuted && $varCanBeNull) {
				// the chain executed, so the subject is not null
				$sureNotTypes[$this->exprPrinter->printExpr($e->var)] = [$e->var, new NullType()];
			}

			return (new SpecifiedTypes([], $sureNotTypes))->setRootExpr($e);
		};
	}

}
