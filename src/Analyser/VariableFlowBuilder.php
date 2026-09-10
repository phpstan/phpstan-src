<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;
use function is_string;
use function spl_object_id;

/** Compose variable flow for assignment targets and arguments. */
final class VariableFlowBuilder
{

	/** @param InternalThrowPoint[] $throwPoints */
	public static function throws(Expr $expr, array $throwPoints): ?VariableFlow
	{
		$throws = [];
		foreach ($throwPoints as $throw) {
			if ($throw->getNode() !== $expr && ($throw->getNode()->getStartFilePos() !== $expr->getStartFilePos() || $throw->getNode()->getEndFilePos() !== $expr->getEndFilePos())) {
				continue;
			}

			$throws[] = VariableFlow::throwing($throw->getType(), true, $throw->canContainAnyThrowable());
		}
		return VariableFlow::sequence(...$throws);
	}

	public static function arguments(Expr\CallLike $call, ArgsResult $argsResult, ExpressionResultStorage $storage): ?VariableFlow
	{
		$flows = [];
		foreach ($call->getArgs() as $arg) {
			$result = $argsResult->findArgResult($arg->value) ?? $storage->findExpressionResult($arg->value);
			$flows[] = $result !== null ? $result->getVariableFlow() : null;
			if (!$arg->byRef && !$argsResult->isPassedByReference($arg->value)) {
				continue;
			}

			$flows[] = self::escapeRoot($arg->value);
		}
		return VariableFlow::sequence(...$flows);
	}

	public static function child(?Node $node, ExpressionResultStorage $storage): ?VariableFlow
	{
		if ($node instanceof Expr) {
			$result = $storage->findExpressionResult($node);
			return $result !== null ? $result->getVariableFlow() : null;
		}
		return null;
	}

	public static function targetRead(Expr $target, ExpressionResultStorage $storage, bool $read): ?VariableFlow
	{
		if ($target instanceof Expr\Variable) {
			return is_string($target->name) ? ($read ? VariableFlow::read($target->name) : null) : self::child($target->name, $storage);
		}
		if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
			return null;
		}
		if ($target instanceof Expr\ArrayDimFetch) {
			return VariableFlow::sequence(self::targetRead($target->var, $storage, true), self::child($target->dim, $storage));
		}
		if ($target instanceof Expr\PropertyFetch || $target instanceof Expr\NullsafePropertyFetch) {
			return VariableFlow::sequence(self::child($target->var, $storage), self::child($target->name, $storage));
		}
		if ($target instanceof Expr\StaticPropertyFetch) {
			return VariableFlow::sequence(self::child($target->class, $storage), self::child($target->name, $storage));
		}
		return self::child($target, $storage);
	}

	/** @param VariableWrite::KIND_* $kind */
	public static function targetWrite(Expr $target, int $kind, MutatingScope $scope, ExpressionResultStorage $storage, ?Type $redundant = null): ?VariableFlow
	{
		if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
			$writes = [];
			foreach ($target->items as $item) {
				if ($item === null) {
					continue;
				}
				$writes[] = VariableFlow::sequence(self::child($item->key, $storage), self::targetRead($item->value, $storage, false), self::targetWrite($item->value, VariableWrite::KIND_LIST_ITEM, $scope, $storage), $item->byRef ? self::escapeRoot($item->value) : null);
			}
			return VariableFlow::sequence(...$writes);
		}
		if ($target instanceof Expr\ArrayDimFetch) {
			do {
				$target = $target->var;
			} while ($target instanceof Expr\ArrayDimFetch);
			if (!$target instanceof Expr\Variable || !is_string($target->name) || $scope->hasVariableType($target->name)->no()) {
				return null;
			}
			$type = $scope->getVariableType($target->name);
			if (!$type->isArray()->yes() && !$type->isString()->yes()) {
				return null;
			}
			$kind = VariableWrite::KIND_ARRAY_DIM_WRITE;
		}
		if (!$target instanceof Expr\Variable || !is_string($target->name)) {
			return null;
		}
		return VariableFlow::write(new VariableWrite($target->name, $target, spl_object_id($target), $kind), $redundant);
	}

	public static function escapeRoot(Expr $expr): ?VariableFlow
	{
		while ($expr instanceof Expr\ArrayDimFetch) {
			$expr = $expr->var;
		}
		return $expr instanceof Expr\Variable && is_string($expr->name) ? VariableFlow::escape($expr->name) : null;
	}

}
