<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
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
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strpos;

class RuleLevelHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private bool $checkNullables,
		private bool $checkThisOnly,
		private bool $checkUnionTypes,
		private bool $checkExplicitMixed,
		private bool $checkImplicitMixed,
		private bool $checkListType,
	)
	{
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
			$traverse = static function (Type $type, callable $traverse): Type {
				if ($type instanceof TemplateMixedType) {
					return $type->toStrictMixedType();
				}
				if (
					$type instanceof MixedType
					&& $type->isExplicitMixed()
				) {
					return new StrictMixedType();
				}

				return $traverse($type);
			};
			$acceptingType = TypeTraverser::map($acceptingType, $traverse);
			$acceptedType = TypeTraverser::map($acceptedType, $traverse);
		}

		if (
			$this->checkImplicitMixed
		) {
			$traverse = static function (Type $type, callable $traverse): Type {
				if ($type instanceof TemplateMixedType) {
					return $type->toStrictMixedType();
				}
				if (
					$type instanceof MixedType
					&& !$type->isExplicitMixed()
				) {
					return new StrictMixedType();
				}

				return $traverse($type);
			};
			$acceptingType = TypeTraverser::map($acceptingType, $traverse);
			$acceptedType = TypeTraverser::map($acceptedType, $traverse);
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
		if (!$accepts->yes() && $acceptingType instanceof UnionType) {
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
			&& (
				$acceptedType->isConstantArray()->no()
				|| !$acceptedType->isIterableAtLeastOnce()->no()
			)
			&& $acceptingType->isConstantArray()->no()
		) {
			return (
				!$acceptingType->isIterableAtLeastOnce()->yes()
				|| $acceptedType->isIterableAtLeastOnce()->yes()
			) && (
				!$this->checkListType
				|| !$acceptingType->isList()->yes()
				|| $acceptedType->isList()->yes()
			) && self::accepts(
				$acceptingType->getIterableKeyType(),
				$acceptedType->getIterableKeyType(),
				$strictTypes,
			) && self::accepts(
				$acceptingType->getIterableValueType(),
				$acceptedType->getIterableValueType(),
				$strictTypes,
			);
		}

		return $this->checkUnionTypes ? $accepts->yes() : !$accepts->no();
	}

	/**
	 * @api
	 * @param callable(Type $type): bool $unionTypeCriteriaCallback
	 */
	public function findTypeToCheck(
		Scope $scope,
		Expr $var,
		string $unknownClassErrorPattern,
		callable $unionTypeCriteriaCallback,
	): FoundTypeResult
	{
		if ($this->checkThisOnly && !$this->isThis($var)) {
			return new FoundTypeResult(new ErrorType(), [], [], null);
		}
		$type = $scope->getType($var);
		if (!$this->checkNullables && !$type instanceof NullType) {
			$type = TypeCombinator::removeNull($type);
		}

		if (
			$this->checkExplicitMixed
			&& $type instanceof MixedType
			&& !$type instanceof TemplateMixedType
			&& $type->isExplicitMixed()
		) {
			return new FoundTypeResult(new StrictMixedType(), [], [], null);
		}

		if (
			$this->checkImplicitMixed
			&& $type instanceof MixedType
			&& !$type instanceof TemplateMixedType
			&& !$type->isExplicitMixed()
		) {
			return new FoundTypeResult(new StrictMixedType(), [], [], null);
		}

		if ($type instanceof MixedType || $type instanceof NeverType) {
			return new FoundTypeResult(new ErrorType(), [], [], null);
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
			return new FoundTypeResult(new ErrorType(), [], $errors, null);
		}

		if (!$this->checkUnionTypes) {
			if ($type instanceof ObjectWithoutClassType) {
				return new FoundTypeResult(new ErrorType(), [], [], null);
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
					return new FoundTypeResult(TypeCombinator::union(...$newTypes), $directClassNames, [], null);
				}
			}
		}

		$tip = null;
		if (strpos($type->describe(VerbosityLevel::typeOnly()), 'PhpParser\\Node\\Arg|PhpParser\\Node\\VariadicPlaceholder') !== false && !$unionTypeCriteriaCallback($type)) {
			$tip = 'Use <fg=cyan>->getArgs()</> instead of <fg=cyan>->args</>.';
		}

		return new FoundTypeResult($type, $directClassNames, [], $tip);
	}

}
