<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function count;
use function min;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
final class MethodSignatureRule implements Rule
{

	public function __construct(
		private ParentMethodHelper $parentMethodHelper,
		private bool $reportMaybes,
		private bool $reportStatic,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$methodName = $method->getName();
		if ($methodName === '__construct') {
			return [];
		}
		if (!$this->reportStatic && $method->isStatic()) {
			return [];
		}
		if ($method->isPrivate()) {
			return [];
		}
		$errors = [];
		$declaringClass = $method->getDeclaringClass();
		foreach ($this->parentMethodHelper->collectParentMethods($methodName, $method->getDeclaringClass()) as [$parentMethod, $parentMethodDeclaringClass]) {
			$parentVariants = $parentMethod->getVariants();
			if (count($parentVariants) !== 1) {
				continue;
			}
			$parentVariant = $parentVariants[0];
			[$returnTypeCompatibility, $returnType, $parentReturnType] = $this->checkReturnTypeCompatibility($declaringClass, $method, $parentVariant);
			if ($returnTypeCompatibility->no() || (!$returnTypeCompatibility->yes() && $this->reportMaybes)) {
				$builder = RuleErrorBuilder::message(sprintf(
					'Return type (%s) of method %s::%s() should be %s with return type (%s) of method %s::%s()',
					$returnType->describe(VerbosityLevel::value()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$returnTypeCompatibility->no() ? 'compatible' : 'covariant',
					$parentReturnType->describe(VerbosityLevel::value()),
					$parentMethodDeclaringClass->getDisplayName(),
					$parentMethod->getName(),
				))->identifier('method.childReturnType');
				if (
					$parentMethod->getDeclaringClass()->getName() === Rule::class
					&& strtolower($methodName) === 'processnode'
				) {
					$ruleErrorType = new ObjectType(RuleError::class);
					$identifierRuleErrorType = new ObjectType(IdentifierRuleError::class);
					$listOfIdentifierRuleErrors = new IntersectionType([
						new ArrayType(IntegerRangeType::fromInterval(0, null), $identifierRuleErrorType),
						new AccessoryArrayListType(),
					]);
					if ($listOfIdentifierRuleErrors->isSuperTypeOf($parentReturnType)->yes()) {
						$returnValueType = $returnType->getIterableValueType();
						if (!$returnValueType->isString()->no()) {
							$builder->tip('Rules can no longer return plain strings. See: https://phpstan.org/blog/using-rule-error-builder');
						} elseif (
							$ruleErrorType->isSuperTypeOf($returnValueType)->yes()
							&& !$identifierRuleErrorType->isSuperTypeOf($returnValueType)->yes()
						) {
							$builder->tip('Errors are missing identifiers. See: https://phpstan.org/blog/using-rule-error-builder');
						} elseif (!$returnType->isList()->yes()) {
							$builder->tip('Return type must be a list. See: https://phpstan.org/blog/using-rule-error-builder');
						}
					}
				}
				$errors[] = $builder->build();
			}

			$methodParameters = $method->getParameters();
			$parentVariantParameters = $parentVariant->getParameters();
			$parameterResults = $this->checkParameterTypeCompatibility($declaringClass, $methodParameters, $parentVariantParameters);
			foreach ($parameterResults as $parameterIndex => [$parameterResult, $parameterType, $parentParameterType]) {
				if ($parameterResult->yes()) {
					continue;
				}
				if (!$parameterResult->no() && !$this->reportMaybes) {
					continue;
				}
				$parameter = $methodParameters[$parameterIndex];
				$parentParameter = $parentVariantParameters[$parameterIndex];
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() should be %s with parameter $%s (%s) of method %s::%s()',
					$parameterIndex + 1,
					$parameter->getName(),
					$parameterType->describe(VerbosityLevel::value()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$parameterResult->no() ? 'compatible' : 'contravariant',
					$parentParameter->getName(),
					$parentParameterType->describe(VerbosityLevel::value()),
					$parentMethodDeclaringClass->getDisplayName(),
					$parentMethod->getName(),
				))->identifier('method.childParameterType')->build();
			}
		}

		return $errors;
	}

	/**
	 * @return array{TrinaryLogic, Type, Type}
	 */
	private function checkReturnTypeCompatibility(
		ClassReflection $declaringClass,
		ExtendedParametersAcceptor $currentVariant,
		ExtendedParametersAcceptor $parentVariant,
	): array
	{
		$returnType = TypehintHelper::decideType(
			$currentVariant->getNativeReturnType(),
			TemplateTypeHelper::resolveToBounds($currentVariant->getPhpDocReturnType()),
		);
		$originalParentReturnType = TypehintHelper::decideType(
			$parentVariant->getNativeReturnType(),
			TemplateTypeHelper::resolveToBounds($parentVariant->getPhpDocReturnType()),
		);
		$parentReturnType = $this->transformStaticType($declaringClass, $originalParentReturnType);
		// Allow adding `void` return type hints when the parent defines no return type
		if ($returnType->isVoid()->yes() && $parentReturnType instanceof MixedType) {
			return [TrinaryLogic::createYes(), $returnType, $parentReturnType];
		}

		// We can return anything
		if ($parentReturnType->isVoid()->yes()) {
			return [TrinaryLogic::createYes(), $returnType, $parentReturnType];
		}

		return [$parentReturnType->isSuperTypeOf($returnType)->result, TypehintHelper::decideType(
			$currentVariant->getNativeReturnType(),
			$currentVariant->getPhpDocReturnType(),
		), $originalParentReturnType];
	}

	/**
	 * @param ExtendedParameterReflection[] $parameters
	 * @param ExtendedParameterReflection[] $parentParameters
	 * @return array<int, array{TrinaryLogic, Type, Type}>
	 */
	private function checkParameterTypeCompatibility(
		ClassReflection $declaringClass,
		array $parameters,
		array $parentParameters,
	): array
	{
		$parameterResults = [];

		$numberOfParameters = min(count($parameters), count($parentParameters));
		for ($i = 0; $i < $numberOfParameters; $i++) {
			$parameter = $parameters[$i];
			$parentParameter = $parentParameters[$i];

			$parameterType = TypehintHelper::decideType(
				$parameter->getNativeType(),
				TemplateTypeHelper::resolveToBounds($parameter->getPhpDocType()),
			);
			$originalParameterType = TypehintHelper::decideType(
				$parentParameter->getNativeType(),
				TemplateTypeHelper::resolveToBounds($parentParameter->getPhpDocType()),
			);
			$parentParameterType = $this->transformStaticType($declaringClass, $originalParameterType);

			$parameterResults[] = [$parameterType->isSuperTypeOf($parentParameterType)->result, TypehintHelper::decideType(
				$parameter->getNativeType(),
				$parameter->getPhpDocType(),
			), $originalParameterType];
		}

		return $parameterResults;
	}

	private function transformStaticType(ClassReflection $declaringClass, Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($declaringClass): Type {
			if ($type instanceof GenericStaticType) {
				if ($declaringClass->isFinal()) {
					$changedType = $type->changeBaseClass($declaringClass)->getStaticObjectType();
				} else {
					$changedType = $type->changeBaseClass($declaringClass);
				}
				return $traverse($changedType);
			}

			if ($type instanceof StaticType) {
				if ($declaringClass->isFinal()) {
					$changedType = new ObjectType($declaringClass->getName());
				} else {
					$changedType = $type->changeBaseClass($declaringClass);
				}
				return $traverse($changedType);
			}

			return $traverse($type);
		});
	}

}
