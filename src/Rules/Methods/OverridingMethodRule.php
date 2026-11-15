<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function is_bool;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(level: 0)]
#[ValidatesStubFiles]
final class OverridingMethodRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodSignatureRule $methodSignatureRule,
		#[AutowiredParameter]
		private bool $checkPhpDocMethodSignatures,
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
		private MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper,
		private MethodPrototypeFinder $methodPrototypeFinder,
		private OverridingMethodCheck $overridingMethodCheck,
		#[AutowiredParameter]
		private bool $checkMissingOverrideMethodAttribute,
		#[AutowiredParameter(ref: '%featureToggles.checkWholePhpVersionRange%')]
		private bool $checkWholePhpVersionRange,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter&DependencyTracker $scope): array
	{
		$method = $node->getMethodReflection();
		$supportsAbstractTraitMethods = $scope->getPhpVersion()->supportsAbstractTraitMethods();
		$prototypeData = $this->methodPrototypeFinder->findPrototype(
			$node->getClassReflection(),
			$method->getName(),
			$this->checkWholePhpVersionRange ? !$supportsAbstractTraitMethods->no() : $supportsAbstractTraitMethods->yes(),
		);
		if ($prototypeData === null) {
			if (strtolower($method->getName()) === '__construct') {
				$parent = $method->getDeclaringClass()->getParentClass();
				if ($parent !== null && $parent->hasConstructor()) {
					$parentConstructor = $parent->getConstructor();
					if ($parentConstructor->isFinalByKeyword()->yes()) {
						return $this->addErrors([
							RuleErrorBuilder::message(sprintf(
								'Method %s::%s() overrides final method %s::%s().',
								$method->getDeclaringClass()->getDisplayName(),
								$method->getName(),
								$parent->getDisplayName(true),
								$parentConstructor->getName(),
							))
								->nonIgnorable()
								->identifier('method.parentMethodFinal')
								->build(),
						], $node, $scope);
					}
					if ($parentConstructor->isFinal()->yes()) {
						return $this->addErrors([
							RuleErrorBuilder::message(sprintf(
								'Method %s::%s() overrides @final method %s::%s().',
								$method->getDeclaringClass()->getDisplayName(),
								$method->getName(),
								$parent->getDisplayName(true),
								$parentConstructor->getName(),
							))->identifier('method.parentMethodFinalByPhpDoc')
								->build(),
						], $node, $scope);
					}
					$parentAbstract = $parentConstructor->isAbstract();
					if (
						$method->isAbstract()->yes()
						&& !(is_bool($parentAbstract) ? $parentAbstract : $parentAbstract->yes())
					) {
						return $this->addErrors([
							RuleErrorBuilder::message(sprintf(
								'Cannot make non-abstract method %s::%s() abstract in class %s.',
								$parentConstructor->getDeclaringClass()->getDisplayName(true),
								$parentConstructor->getName(),
								$method->getDeclaringClass()->getDisplayName(),
							))
								->nonIgnorable()
								->identifier('method.abstractOverridingNonAbstract')
								->build(),
						], $node, $scope);
					}
				}
			}

			return $this->overridingMethodCheck->checkOverrideAttributeWithoutPrototype($method, $node->getOriginalNode());
		}

		[
			$prototype,
			$prototypeDeclaringClass,
			$checkVisibility,
			$inheritancePrototype,
			$inheritancePrototypeDeclaringClass,
		] = $prototypeData;

		$messages = $this->overridingMethodCheck->checkInheritance(
			$method,
			$node->getOriginalNode(),
			$prototype,
			$prototypeDeclaringClass,
			$inheritancePrototype,
			$inheritancePrototypeDeclaringClass,
			$this->checkMissingOverrideMethodAttribute && !$scope->isInTrait(),
		);

		if ($checkVisibility) {
			$messages = array_merge($messages, $this->methodVisibilityComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method, $node->getOriginalNode()));
		}

		$prototypeVariants = $prototype->getVariants();
		if (count($prototypeVariants) !== 1) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeVariant = $prototypeVariants[0];

		$methodReturnType = $method->getNativeReturnType();

		$realPrototype = $method->getPrototype();
		$tentativeReturnTypes = $scope->getPhpVersion()->hasTentativeReturnTypes();
		$hasTentativeReturnTypes = $this->checkWholePhpVersionRange ? !$tentativeReturnTypes->no() : $tentativeReturnTypes->yes();

		if (
			$realPrototype instanceof MethodPrototypeReflection
			&& $hasTentativeReturnTypes
			&& $realPrototype->getTentativeReturnType() !== null
			&& !$this->hasReturnTypeWillChangeAttribute($node->getOriginalNode())
			&& count($prototypeDeclaringClass->getNativeReflection()->getMethod($prototype->getName())->getAttributes('ReturnTypeWillChange')) === 0
		) {
			if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($realPrototype->getTentativeReturnType(), $method->getNativeReturnType(), true)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with tentative return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$realPrototype->getTentativeReturnType()->describe(VerbosityLevel::typeOnly()),
					$realPrototype->getDeclaringClass()->getDisplayName(true),
					$realPrototype->getName(),
				))
					->tip('Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.')
					->nonIgnorable()
					->identifier('method.tentativeReturnType')
					->build();
			}
		}

		$messages = array_merge($messages, $this->methodParameterComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method, $scope, false));

		if (!$prototypeVariant instanceof ExtendedFunctionVariant) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeReturnType = $prototypeVariant->getNativeReturnType();
		$reportReturnType = true;
		if ($hasTentativeReturnTypes) {
			$reportReturnType = !$realPrototype instanceof MethodPrototypeReflection
				|| $realPrototype->getTentativeReturnType() === null
				|| (is_bool($prototype->isBuiltin()) ? !$prototype->isBuiltin() : $prototype->isBuiltin()->no());
		} elseif (
			$realPrototype instanceof MethodPrototypeReflection
			&& $realPrototype->getTentativeReturnType() !== null
			&& $prototypeDeclaringClass->isBuiltin()
		) {
			// return types that are only tentative are not checked before PHP 8.1
			$reportReturnType = false;
		} else {
			if ($realPrototype instanceof MethodPrototypeReflection && $realPrototype->isInternal()) {
				if (
					(is_bool($prototype->isBuiltin()) ? $prototype->isBuiltin() : $prototype->isBuiltin()->yes())
					&& $prototypeDeclaringClass->getName() !== $realPrototype->getDeclaringClass()->getName()
				) {
					$realPrototypeVariant = $realPrototype->getVariants()[0];
					if (
						$prototypeReturnType instanceof MixedType
						&& !$prototypeReturnType->isExplicitMixed()
						&& (!$realPrototypeVariant->getReturnType() instanceof MixedType || $realPrototypeVariant->getReturnType()->isExplicitMixed())
					) {
						$reportReturnType = false;
					}
				}

				if (
					$reportReturnType
					&& (is_bool($prototype->isBuiltin()) ? $prototype->isBuiltin() : $prototype->isBuiltin()->yes())
				) {
					$reportReturnType = !$this->hasReturnTypeWillChangeAttribute($node->getOriginalNode());
				}
			}
		}

		if (
			$reportReturnType
			&& !$this->methodParameterComparisonHelper->isReturnTypeCompatible($prototypeReturnType, $methodReturnType, $this->phpVersion->supportsReturnCovariance())
		) {
			if ($this->phpVersion->supportsReturnCovariance()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototypeReturnType->describe(VerbosityLevel::typeOnly()),
					$prototypeDeclaringClass->getDisplayName(true),
					$prototype->getName(),
				))
					->nonIgnorable()
					->identifier('method.childReturnType')
					->build();
			} else {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not compatible with return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototypeReturnType->describe(VerbosityLevel::typeOnly()),
					$prototypeDeclaringClass->getDisplayName(true),
					$prototype->getName(),
				))
					->nonIgnorable()
					->identifier('method.childReturnType')
					->build();
			}
		}

		return $this->addErrors($messages, $node, $scope);
	}

	/**
	 * @param list<IdentifierRuleError> $errors
	 * @return list<IdentifierRuleError>
	 */
	private function addErrors(
		array $errors,
		InClassMethodNode $classMethod,
		Scope&NodeCallbackInvoker&CollectedDataEmitter&DependencyTracker $scope,
	): array
	{
		if (count($errors) > 0) {
			return $errors;
		}

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		return $this->methodSignatureRule->processNode($classMethod, $scope);
	}

	private function hasReturnTypeWillChangeAttribute(Node\Stmt\ClassMethod $method): bool
	{
		foreach ($method->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'returntypewillchange') {
					return true;
				}
			}
		}

		return false;
	}

}
