<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_pop;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function spl_object_id;

/**
 * A foreach over a constant array that is not unrolled loses which iteration
 * wrote which offset - `$result[$value] = ...` leaves every value an optional
 * key of `$result`. When every iteration writes `$result[$value]` (or
 * `$result[$key]`) and the body changes `$result` only through offset writes,
 * which never remove a key, all the iterated values are keys of `$result`
 * after the loop.
 */
#[ReferencedByTurboExtension(key: 'foreachIterationOffsetWritesHelper')]
final class ForeachIterationOffsetWritesHelper
{

	private const PROPERTY_MODIFYING_IMPURE_POINTS = [
		'methodCall',
		'functionCall',
		'new',
		'propertyHookCall',
		'include',
		'require',
		'eval',
		'yield',
		'yieldFrom',
	];

	/**
	 * @param list<MutatingScope> $backEdgeScopes the scopes the body continues to the next iteration with
	 * @param array<ImpurePoint> $bodyImpurePoints
	 */
	public static function makeWrittenOffsetsRequired(
		NodeScopeResolver $nodeScopeResolver,
		Foreach_ $stmt,
		MutatingScope $finalScope,
		array $backEdgeScopes,
		Type $iterateeType,
		Type $nativeIterateeType,
		array $bodyImpurePoints,
	): MutatingScope
	{
		if ($backEdgeScopes === [] || $stmt->byRef) {
			return $finalScope;
		}

		if (
			self::getIteratedOffsets($iterateeType, true) === null
			&& self::getIteratedOffsets($iterateeType, false) === null
		) {
			return $finalScope;
		}

		$candidates = self::findCandidates($stmt);
		if ($candidates === []) {
			return $finalScope;
		}

		$canModifyProperties = null;
		foreach ($candidates as [$target, $dimName, $isKey]) {
			if ($target instanceof PropertyFetch) {
				// a called method or function can change the property behind the scope's back
				$canModifyProperties ??= self::canModifyProperties($bodyImpurePoints);
				if ($canModifyProperties) {
					continue;
				}
			}

			$offsets = self::getIteratedOffsets($iterateeType, $isKey);
			if ($offsets === null) {
				continue;
			}

			$originalExpr = $isKey ? new OriginalForeachKeyExpr($dimName) : new OriginalForeachValueExpr($dimName);
			$dimFetch = new ArrayDimFetch($target, new Variable($dimName));
			foreach ($backEdgeScopes as $backEdgeScope) {
				if (
					!$backEdgeScope->hasExpressionType($originalExpr)->yes()
					|| !$backEdgeScope->hasExpressionType($dimFetch)->yes()
				) {
					continue 2;
				}
			}

			$certainty = $finalScope->hasExpressionType($target);
			if ($certainty->no()) {
				continue;
			}

			$targetType = self::makeOffsetsRequired($nodeScopeResolver->readScopeStateOrSyntheticType($target, $finalScope), $offsets);
			if ($targetType === null) {
				continue;
			}

			$targetNativeType = $nodeScopeResolver->readScopeStateOrSyntheticType($target, $finalScope->doNotTreatPhpDocTypesAsCertain());
			$nativeOffsets = self::getIteratedOffsets($nativeIterateeType, $isKey);
			if ($nativeOffsets !== null) {
				$targetNativeType = self::makeOffsetsRequired($targetNativeType, $nativeOffsets) ?? $targetNativeType;
			}

			$finalScope = $finalScope->specifyExpressionType($target, $targetType, $targetNativeType, $certainty);
		}

		return $finalScope;
	}

	/**
	 * @param array<ImpurePoint> $impurePoints
	 */
	private static function canModifyProperties(array $impurePoints): bool
	{
		foreach ($impurePoints as $impurePoint) {
			if (in_array($impurePoint->getIdentifier(), self::PROPERTY_MODIFYING_IMPURE_POINTS, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<ConstantIntegerType|ConstantStringType> $offsets
	 */
	private static function makeOffsetsRequired(Type $type, array $offsets): ?Type
	{
		if (!$type->isConstantArray()->yes()) {
			return null;
		}

		foreach ($offsets as $offset) {
			$type = TypeCombinator::intersect($type, new HasOffsetType($offset));
		}

		if ($type instanceof NeverType) {
			return null;
		}

		return $type;
	}

	/**
	 * The offsets the iterations write - each iteration's key or value of a
	 * sealed constant array with no optional keys, so every one is iterated.
	 *
	 * @return list<ConstantIntegerType|ConstantStringType>|null
	 */
	private static function getIteratedOffsets(Type $iterateeType, bool $isKey): ?array
	{
		$constantArrays = $iterateeType->getConstantArrays();
		if (count($constantArrays) !== 1 || !$iterateeType->isConstantArray()->yes()) {
			return null;
		}

		$constantArray = $constantArrays[0];
		if (
			count($constantArray->getKeyTypes()) === 0
			|| count($constantArray->getOptionalKeys()) > 0
			|| !$constantArray->isUnsealed()->no()
		) {
			return null;
		}

		$offsets = [];
		foreach ($isKey ? $constantArray->getKeyTypes() : $constantArray->getValueTypes() as $type) {
			$values = $type->toArrayKey()->getConstantScalarValues();
			if (count($values) !== 1) {
				continue;
			}
			$value = $values[0];
			if (is_int($value)) {
				$offsets[] = new ConstantIntegerType($value);
			} elseif (is_string($value)) {
				$offsets[] = new ConstantStringType($value);
			}
		}

		if ($offsets === []) {
			return null;
		}

		return $offsets;
	}

	/**
	 * The variables and `$this` properties the body writes as `$target[$keyOrValue] = ...` and
	 * otherwise accesses only through their offsets, not unsetting any.
	 *
	 * @return list<array{Variable|PropertyFetch, string, bool}> target, key or value variable name, whether it is the key
	 */
	private static function findCandidates(Foreach_ $stmt): array
	{
		$valueName = $stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name) ? $stmt->valueVar->name : null;
		$keyName = $stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name) ? $stmt->keyVar->name : null;
		if ($valueName === null && $keyName === null) {
			return [];
		}

		/** @var array<string, array{Variable|PropertyFetch, array<string, bool>}> $writes */
		$writes = [];
		$dimRootIds = [];
		$nonOffsetKeys = [];
		$hasDynamicThisProperty = false;
		$nodes = $stmt->stmts;
		while ($nodes !== []) {
			$node = array_pop($nodes);
			if (!$node instanceof Node) {
				continue;
			}
			if (
				($node instanceof Variable && !is_string($node->name))
				|| $node instanceof Expr\Include_
				|| $node instanceof Expr\Eval_
				|| (
					$node instanceof Expr\FuncCall
					&& $node->name instanceof Node\Name
					&& in_array($node->name->toLowerString(), ['extract', 'parse_str'], true)
				)
			) {
				return [];
			}

			if ($node instanceof ArrayDimFetch) {
				$dimRootIds[spl_object_id($node->var)] = true;
			}

			if ($node instanceof Stmt\Unset_) {
				foreach ($node->vars as $var) {
					while ($var instanceof ArrayDimFetch) {
						$var = $var->var;
					}
					$key = self::getTargetKey($var);
					if ($key === null) {
						continue;
					}

					$nonOffsetKeys[$key] = true;
				}
			}

			if ($node instanceof Expr\Assign || $node instanceof Expr\AssignOp || $node instanceof Expr\AssignRef) {
				foreach (self::getWriteTargets($node->var) as $target) {
					while ($target->var instanceof ArrayDimFetch) {
						$target = $target->var;
					}
					$key = self::getTargetKey($target->var);
					if (
						$key === null
						|| !$target->dim instanceof Variable
						|| !is_string($target->dim->name)
					) {
						continue;
					}

					$dimName = $target->dim->name;
					if ($dimName !== $valueName && $dimName !== $keyName) {
						continue;
					}

					$writes[$key] ??= [self::createTargetExpr($target->var), []];
					$writes[$key][1][$dimName] = $dimName !== $valueName;
				}
			}

			if (
				$node instanceof PropertyFetch
				&& $node->var instanceof Variable
				&& $node->var->name === 'this'
				&& !$node->name instanceof Node\Identifier
			) {
				$hasDynamicThisProperty = true;
			}

			$key = $node instanceof Expr ? self::getTargetKey($node) : null;
			if ($key !== null && !isset($dimRootIds[spl_object_id($node)])) {
				$nonOffsetKeys[$key] = true;
			}

			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->{$subNodeName};
				if (is_array($subNode)) {
					foreach ($subNode as $item) {
						$nodes[] = $item;
					}
				} else {
					$nodes[] = $subNode;
				}
			}
		}

		$candidates = [];
		foreach ($writes as $key => [$target, $dims]) {
			if (isset($nonOffsetKeys[$key])) {
				continue;
			}
			if ($target instanceof PropertyFetch && $hasDynamicThisProperty) {
				continue;
			}
			if ($target instanceof Variable && ($target->name === $valueName || $target->name === $keyName)) {
				continue;
			}
			foreach ($dims as $dimName => $isKey) {
				$candidates[] = [$target, $dimName, $isKey];
			}
		}

		return $candidates;
	}

	/**
	 * A local variable or a property of `$this` - the write targets whose
	 * every access can be found in the loop body.
	 */
	private static function getTargetKey(Expr $expr): ?string
	{
		if ($expr instanceof Variable && is_string($expr->name) && $expr->name !== 'this') {
			return '$' . $expr->name;
		}
		if (
			$expr instanceof PropertyFetch
			&& $expr->var instanceof Variable
			&& $expr->var->name === 'this'
			&& $expr->name instanceof Node\Identifier
		) {
			return '$this->' . $expr->name->toString();
		}

		return null;
	}

	private static function createTargetExpr(Expr $expr): Variable|PropertyFetch
	{
		if ($expr instanceof PropertyFetch && $expr->name instanceof Node\Identifier) {
			return new PropertyFetch(new Variable('this'), new Node\Identifier($expr->name->toString()));
		}
		if ($expr instanceof Variable && is_string($expr->name)) {
			return new Variable($expr->name);
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @return list<ArrayDimFetch>
	 */
	private static function getWriteTargets(Expr $target): array
	{
		if ($target instanceof ArrayDimFetch) {
			return [$target];
		}
		if (!$target instanceof Expr\List_ && !$target instanceof Expr\Array_) {
			return [];
		}

		$targets = [];
		foreach ($target->items as $item) {
			if ($item === null) {
				continue;
			}
			foreach (self::getWriteTargets($item->value) as $itemTarget) {
				$targets[] = $itemTarget;
			}
		}

		return $targets;
	}

}
