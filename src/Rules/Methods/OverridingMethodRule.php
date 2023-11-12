<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
class OverridingMethodRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodSignatureRule $methodSignatureRule,
		private bool $checkPhpDocMethodSignatures,
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
		private bool $genericPrototypeMessage,
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
		$prototype = $this->findPrototype($node->getClassReflection(), $method->getName());
		if ($prototype === null) {
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
								$parent->getDisplayName($this->genericPrototypeMessage),
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
								$parent->getDisplayName($this->genericPrototypeMessage),
								$parentConstructor->getName(),
							))->identifier('method.parentMethodFinalByPhpDoc')
								->build(),
						], $node, $scope);
					}
				}
			}
			return [];
		}

		$messages = [];
		if ($prototype->isFinalByKeyword()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides final method %s::%s().',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
				$prototype->getName(),
			))
				->nonIgnorable()
				->identifier('method.parentMethodFinal')
				->build();
		} elseif ($prototype->isFinal()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides @final method %s::%s().',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
				$prototype->getName(),
			))->identifier('method.parentMethodFinalByPhpDoc')
				->build();
		}

		if ($prototype->isStatic()) {
			if (!$method->isStatic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Non-static method %s::%s() overrides static method %s::%s().',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))
					->nonIgnorable()
					->identifier('method.nonStatic')
					->build();
			}
		} elseif ($method->isStatic()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Static method %s::%s() overrides non-static method %s::%s().',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
				$prototype->getName(),
			))
				->nonIgnorable()
				->identifier('method.static')
				->build();
		}

		if ($prototype->isPublic()) {
			if (!$method->isPublic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s method %s::%s() overriding public method %s::%s() should also be public.',
					$method->isPrivate() ? 'Private' : 'Protected',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))
					->nonIgnorable()
					->identifier('method.visibility')
					->build();
			}
		} elseif ($method->isPrivate()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Private method %s::%s() overriding protected method %s::%s() should be protected or public.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
				$prototype->getName(),
			))
				->nonIgnorable()
				->identifier('method.visibility')
				->build();
		}

		$prototypeVariants = $prototype->getVariants();
		if (count($prototypeVariants) !== 1) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeVariant = $prototypeVariants[0];

		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodReturnType = $methodVariant->getNativeReturnType();

		$realPrototype = $method->getPrototype();

		if (
			$realPrototype instanceof MethodPrototypeReflection
			&& $this->phpVersion->hasTentativeReturnTypes()
			&& $realPrototype->getTentativeReturnType() !== null
			&& !$this->hasReturnTypeWillChangeAttribute($node->getOriginalNode())
			&& count($prototype->getDeclaringClass()->getNativeReflection()->getMethod($prototype->getName())->getAttributes('ReturnTypeWillChange')) === 0
		) {
			if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($realPrototype->getTentativeReturnType(), $methodVariant->getNativeReturnType(), true)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with tentative return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$realPrototype->getTentativeReturnType()->describe(VerbosityLevel::typeOnly()),
					$realPrototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$realPrototype->getName(),
				))
					->tip('Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.')
					->nonIgnorable()
					->identifier('method.tentativeReturnType')
					->build();
			}
		}

		$messages = array_merge($messages, $this->methodParameterComparisonHelper->compare($prototype, $method));

		if (!$prototypeVariant instanceof FunctionVariantWithPhpDocs) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeReturnType = $prototypeVariant->getNativeReturnType();
		$reportReturnType = true;
		if ($this->phpVersion->hasTentativeReturnTypes()) {
			$reportReturnType = !$realPrototype instanceof MethodPrototypeReflection || $realPrototype->getTentativeReturnType() === null || $prototype->isInternal()->no();
		} else {
			if ($realPrototype instanceof MethodPrototypeReflection && $realPrototype->isInternal()) {
				if ($prototype->isInternal()->yes() && $prototype->getDeclaringClass()->getName() !== $realPrototype->getDeclaringClass()->getName()) {
					$realPrototypeVariant = $realPrototype->getVariants()[0];
					if (
						$prototypeReturnType instanceof MixedType
						&& !$prototypeReturnType->isExplicitMixed()
						&& (!$realPrototypeVariant->getReturnType() instanceof MixedType || $realPrototypeVariant->getReturnType()->isExplicitMixed())
					) {
						$reportReturnType = false;
					}
				}

				if ($reportReturnType && $prototype->isInternal()->yes()) {
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
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
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
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
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
		Scope $scope,
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

	private function findPrototype(ClassReflection $classReflection, string $methodName): ?ExtendedMethodReflection
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasNativeMethod($methodName)) {
				return $immediateInterface->getNativeMethod($methodName);
			}
		}

		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return null;
		}

		if (!$parentClass->hasNativeMethod($methodName)) {
			return null;
		}

		$method = $parentClass->getNativeMethod($methodName);
		if ($method->isPrivate()) {
			return null;
		}

		$declaringClass = $method->getDeclaringClass();
		if ($declaringClass->hasConstructor()) {
			if ($method->getName() === $declaringClass->getConstructor()->getName()) {
				$prototype = $method->getPrototype();
				if ($prototype instanceof PhpMethodReflection || $prototype instanceof MethodPrototypeReflection || $prototype instanceof NativeMethodReflection) {
					if (!$prototype->isAbstract()) {
						return null;
					}
				}
			}
		}

		return $method;
	}

}
