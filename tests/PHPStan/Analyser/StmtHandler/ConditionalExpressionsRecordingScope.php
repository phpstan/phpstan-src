<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Type\Type;
use function count;

/**
 * A MutatingScope subclass overriding addConditionalExpressions(), so the
 * native engine has to hand the holder tables over through a PHP call.
 * enterForeach() and mergeWith() answer $this to keep this subclass the
 * scope ForeachHandler narrows.
 */
final class ConditionalExpressionsRecordingScope extends MutatingScope
{

	/** @var list<array{string, int}> */
	public array $added = [];

	public function enterForeach(MutatingScope $originalScope, Expr $iteratee, Type $iterateeType, Type $nativeIterateeType, string $valueName, ?string $keyName, bool $valueByRef): MutatingScope
	{
		return $this;
	}

	public function mergeWith(?MutatingScope $otherScope, bool $preserveVacuousConditionals = false): MutatingScope
	{
		return $this;
	}

	/**
	 * @param ConditionalExpressionHolder[] $conditionalExpressionHolders
	 */
	public function addConditionalExpressions(string $exprString, array $conditionalExpressionHolders): MutatingScope
	{
		$this->added[] = [$exprString, count($conditionalExpressionHolders)];

		return $this;
	}

}
