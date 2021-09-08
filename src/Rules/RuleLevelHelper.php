<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class RuleLevelHelper
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private bool $checkNullables;

	private bool $checkThisOnly;

	private bool $checkUnionTypes;

	private bool $checkExplicitMixed;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		bool $checkNullables,
		bool $checkThisOnly,
		bool $checkUnionTypes,
		bool $checkExplicitMixed = false
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->checkNullables = $checkNullables;
		$this->checkThisOnly = $checkThisOnly;
		$this->checkUnionTypes = $checkUnionTypes;
		$this->checkExplicitMixed = $checkExplicitMixed;
	}

	/** @api */
	public function isThis(Expr $expression): bool
	{
		return $expression instanceof Expr\Variable && $expression->name === 'this';
	}

	/** @api */
	public function accepts(Type $acceptingType, Type $acceptedType, bool $strictTypes): bool
	{
		if (
			$this->checkExplicitMixed
		) {
			$acceptedType = TypeTraverser::map($acceptedType, static function (Type $type, callable $traverse): Type {
				if (
					$type instanceof MixedType
					&& $type->isExplicitMixed()
				) {
					return new StrictMixedType();
				}

				return $traverse($type);
			});
		}

		if (
			!$this->checkNullables
			&& !$acceptingType instanceof NullType
			&& !$acceptedType instanceof NullType
			&& !$acceptedType instanceof BenevolentUnionType
		) {
			$acceptedType = TypeCombinator::removeNull($acceptedType);
		}

		$accepts = $acceptingType->accepts($acceptedType, $strictTypes);
		if (!$accepts->yes() && $acceptingType instanceof UnionType && !$acceptedType instanceof CompoundType) {
			foreach ($acceptingType->getTypes() as $innerType) {
				if (self::accepts($innerType, $acceptedType, $strictTypes)) {
					return true;
				}
			}

			return false;
		}

		if (
			$acceptedType->isArray()->yes()
			&& $acceptingType->isArray()->yes()
			&& !$acceptingType->isIterableAtLeastOnce()->yes()
			&& count(TypeUtils::getConstantArrays($acceptedType)) === 0
			&& count(TypeUtils::getConstantArrays($acceptingType)) === 0
		) {
			return self::accepts(
				$acceptingType->getIterableKeyType(),
				$acceptedType->getIterableKeyType(),
				$strictTypes
			) && self::accepts(
				$acceptingType->getIterableValueType(),
				$acceptedType->getIterableValueType(),
				$strictTypes
			);
		}

		return $this->checkUnionTypes ? $accepts->yes() : !$accepts->no();
	}

	/**
	 * @api
	 * @param Scope $scope
	 * @param Expr $var
	 * @param string $unknownClassErrorPattern
	 * @param callable(Type $type): bool $unionTypeCriteriaCallback
	 * @return FoundTypeResult
	 */
	public function findTypeToCheck(
		Scope $scope,
		Expr $var,
		string $unknownClassErrorPattern,
		callable $unionTypeCriteriaCallback
	): FoundTypeResult
	{
		if ($this->checkThisOnly && !$this->isThis($var)) {
			return new FoundTypeResult(new ErrorType(), [], []);
		}
		$type = $scope->getType($var);
		if (!$this->checkNullables && !$type instanceof NullType) {
			$type = \PHPStan\Type\TypeCombinator::removeNull($type);
		}

		if (TypeCombinator::containsNull($type)) {
			$type = $scope->getType(NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($var));
		}

		if (
			$this->checkExplicitMixed
			&& $type instanceof MixedType
			&& !$type instanceof TemplateMixedType
			&& $type->isExplicitMixed()
		) {
			return new FoundTypeResult(new StrictMixedType(), [], []);
		}

		if ($type instanceof MixedType || $type instanceof NeverType) {
			return new FoundTypeResult(new ErrorType(), [], []);
		}
		if ($type instanceof StaticType) {
			$type = $type->getStaticObjectType();
		}

		$errors = [];
		$directClassNames = TypeUtils::getDirectClassNames($type);
		$hasClassExistsClass = false;
		foreach ($directClassNames as $referencedClass) {
			if ($this->reflectionProvider->hasClass($referencedClass)) {
				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				if (!$classReflection->isTrait()) {
					continue;
				}
			}

			if ($scope->isInClassExists($referencedClass)) {
				$hasClassExistsClass = true;
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf($unknownClassErrorPattern, $referencedClass))->line($var->getLine())->discoveringSymbolsTip()->build();
		}

		if (count($errors) > 0 || $hasClassExistsClass) {
			return new FoundTypeResult(new ErrorType(), [], $errors);
		}

		if (!$this->checkUnionTypes) {
			if ($type instanceof ObjectWithoutClassType) {
				return new FoundTypeResult(new ErrorType(), [], []);
			}
			if ($type instanceof UnionType) {
				$newTypes = [];
				foreach ($type->getTypes() as $innerType) {
					if (!$unionTypeCriteriaCallback($innerType)) {
						continue;
					}

					$newTypes[] = $innerType;
				}

				if (count($newTypes) > 0) {
					return new FoundTypeResult(TypeCombinator::union(...$newTypes), $directClassNames, []);
				}
			}
		}

		return new FoundTypeResult($type, $directClassNames, []);
	}

}
