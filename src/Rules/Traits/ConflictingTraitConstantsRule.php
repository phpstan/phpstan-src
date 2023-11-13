<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
class ConflictingTraitConstantsRule implements Rule
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		$traitConstants = [];
		foreach ($classReflection->getTraits(true) as $trait) {
			foreach ($trait->getNativeReflection()->getReflectionConstants() as $constant) {
				$traitConstants[] = $constant;
			}
		}

		$errors = [];
		foreach ($node->consts as $const) {
			foreach ($traitConstants as $traitConstant) {
				if ($traitConstant->getName() !== $const->name->toString()) {
					continue;
				}

				foreach ($this->processSingleConstant($classReflection, $traitConstant, $node, $const->value) as $error) {
					$errors[] = $error;
				}
			}
		}

		return $errors;
	}

	/**
	 * @return list<RuleError>
	 */
	private function processSingleConstant(ClassReflection $classReflection, ReflectionClassConstant $traitConstant, Node\Stmt\ClassConst $classConst, Node\Expr $valueExpr): array
	{
		$errors = [];
		if ($traitConstant->isPublic()) {
			if ($classConst->isProtected()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Protected constant %s::%s overriding public constant %s::%s should also be public.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			} elseif ($classConst->isPrivate()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Private constant %s::%s overriding public constant %s::%s should also be public.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			}
		} elseif ($traitConstant->isProtected()) {
			if ($classConst->isPublic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Public constant %s::%s overriding protected constant %s::%s should also be protected.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			} elseif ($classConst->isPrivate()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Private constant %s::%s overriding protected constant %s::%s should also be protected.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			}
		} elseif ($traitConstant->isPrivate()) {
			if ($classConst->isPublic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Public constant %s::%s overriding private constant %s::%s should also be private.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			} elseif ($classConst->isProtected()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Protected constant %s::%s overriding private constant %s::%s should also be private.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			}
		}

		if ($traitConstant->isFinal()) {
			if (!$classConst->isFinal()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Non-final constant %s::%s overriding final constant %s::%s should also be final.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			}
		} elseif ($classConst->isFinal()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Final constant %s::%s overriding non-final constant %s::%s should also be non-final.',
				$classReflection->getDisplayName(),
				$traitConstant->getName(),
				$traitConstant->getDeclaringClass()->getName(),
				$traitConstant->getName(),
			))->nonIgnorable()->build();
		}

		$traitNativeType = $traitConstant->getType();
		$constantNativeType = $classConst->type;
		$traitDeclaringClass = $traitConstant->getDeclaringClass();
		if ($traitNativeType === null) {
			if ($constantNativeType !== null) {
				$constantNativeTypeType = ParserNodeTypeToPHPStanType::resolve($constantNativeType, $classReflection);
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Constant %s::%s (%s) overriding constant %s::%s should not have a native type.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$constantNativeTypeType->describe(VerbosityLevel::typeOnly()),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
				))->nonIgnorable()->build();
			}
		} elseif ($constantNativeType === null) {
			$traitNativeTypeType = TypehintHelper::decideTypeFromReflection($traitNativeType, null, $traitDeclaringClass->getName());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s overriding constant %s::%s (%s) should also have native type %s.',
				$classReflection->getDisplayName(),
				$traitConstant->getName(),
				$traitConstant->getDeclaringClass()->getName(),
				$traitConstant->getName(),
				$traitNativeTypeType->describe(VerbosityLevel::typeOnly()),
				$traitNativeTypeType->describe(VerbosityLevel::typeOnly()),
			))->nonIgnorable()->build();
		} else {
			$traitNativeTypeType = TypehintHelper::decideTypeFromReflection($traitNativeType, null, $traitDeclaringClass->getName());
			$constantNativeTypeType = ParserNodeTypeToPHPStanType::resolve($constantNativeType, $classReflection);
			if (!$traitNativeTypeType->equals($constantNativeTypeType)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Constant %s::%s (%s) overriding constant %s::%s (%s) should have the same native type %s.',
					$classReflection->getDisplayName(),
					$traitConstant->getName(),
					$constantNativeTypeType->describe(VerbosityLevel::typeOnly()),
					$traitConstant->getDeclaringClass()->getName(),
					$traitConstant->getName(),
					$traitNativeTypeType->describe(VerbosityLevel::typeOnly()),
					$traitNativeTypeType->describe(VerbosityLevel::typeOnly()),
				))->nonIgnorable()->build();
			}
		}

		$classConstantValueType = $this->initializerExprTypeResolver->getType($valueExpr, InitializerExprContext::fromClassReflection($classReflection));
		$traitConstantValueType = $this->initializerExprTypeResolver->getType(
			$traitConstant->getValueExpression(),
			InitializerExprContext::fromClass(
				$traitDeclaringClass->getName(),
				$traitDeclaringClass->getFileName() !== false ? $traitDeclaringClass->getFileName() : null,
			),
		);
		if (!$classConstantValueType->equals($traitConstantValueType)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s with value %s overriding constant %s::%s with different value %s should have the same value.',
				$classReflection->getDisplayName(),
				$traitConstant->getName(),
				$classConstantValueType->describe(VerbosityLevel::value()),
				$traitConstant->getDeclaringClass()->getName(),
				$traitConstant->getName(),
				$traitConstantValueType->describe(VerbosityLevel::value()),
			))->nonIgnorable()->build();
		}

		return $errors;
	}

}
