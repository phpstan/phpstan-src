<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class RuleLevelHelper
{

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $reflectionProvider;

	/** @var bool */
	private $checkNullables;

	/** @var bool */
	private $checkThisOnly;

	/** @var bool */
	private $checkUnionTypes;

	/** @var bool */
	private $checkExplicitMixed;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		bool $checkNullables = true,
		bool $checkThisOnly = false,
		bool $checkUnionTypes = true,
		bool $checkExplicitMixed = true
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->checkNullables = $checkNullables;
		$this->checkThisOnly = $checkThisOnly;
		$this->checkUnionTypes = $checkUnionTypes;
		$this->checkExplicitMixed = $checkExplicitMixed;
	}

	public function isThis(Expr $expression): bool
	{
		return $expression instanceof Expr\Variable && $expression->name === 'this';
	}

	public function accepts(Type $acceptingType, Type $acceptedType, bool $strictTypes): bool
	{
		if (
			!$this->checkNullables
			&& !$acceptingType instanceof NullType
			&& !$acceptedType instanceof NullType
			&& !$acceptedType instanceof BenevolentUnionType
		) {
			$acceptedType = TypeCombinator::removeNull($acceptedType);
		}

		if (
			$acceptedType->isArray()->yes()
			&& $acceptingType->isArray()->yes()
			&& !$acceptingType instanceof ConstantArrayType
		) {
			$acceptedConstantArrays = TypeUtils::getConstantArrays($acceptedType);
			if (count($acceptedConstantArrays) > 0) {
				foreach ($acceptedConstantArrays as $acceptedConstantArray) {
					foreach ($acceptedConstantArray->getKeyTypes() as $i => $keyType) {
						$valueType = $acceptedConstantArray->getValueTypes()[$i];
						if (
							!self::accepts(
								$acceptingType->getIterableKeyType(),
								$keyType,
								$strictTypes
							) || !self::accepts(
								$acceptingType->getIterableValueType(),
								$valueType,
								$strictTypes
							)
						) {
							return false;
						}
					}
				}

				return true;
			}

			if (
				!self::accepts(
					$acceptingType->getIterableKeyType(),
					$acceptedType->getIterableKeyType(),
					$strictTypes
				) || !self::accepts(
					$acceptingType->getIterableValueType(),
					$acceptedType->getIterableValueType(),
					$strictTypes
				)
			) {
				return false;
			}

			return true;
		}

		if ($acceptingType instanceof UnionType && !$acceptedType instanceof CompoundType) {
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
			&& !$acceptingType instanceof ConstantArrayType
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

		$accepts = $acceptingType->accepts($acceptedType, $strictTypes);

		return $this->checkUnionTypes ? $accepts->yes() : !$accepts->no();
	}

	/**
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
		if ($type instanceof MixedType && (!$this->checkExplicitMixed || !$type->isExplicitMixed())) {
			return new FoundTypeResult(new ErrorType(), [], []);
		}
		if ($type instanceof NeverType) {
			return new FoundTypeResult(new ErrorType(), [], []);
		}
		if ($type instanceof StaticType) {
			$type = $type->getStaticObjectType();
		}

		$errors = [];
		$directClassNames = TypeUtils::getDirectClassNames($type);
		foreach ($directClassNames as $referencedClass) {
			if ($this->reflectionProvider->hasClass($referencedClass)) {
				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				if (!$classReflection->isTrait()) {
					continue;
				}
			}

			$errors[] = RuleErrorBuilder::message(sprintf($unknownClassErrorPattern, $referencedClass))->line($var->getLine())->build();
		}

		if (count($errors) > 0) {
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
