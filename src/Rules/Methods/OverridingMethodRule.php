<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
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
		$prototype = $method->getPrototype();
		if ($prototype->getDeclaringClass()->getName() === $method->getDeclaringClass()->getName()) {
			if (strtolower($method->getName()) === '__construct') {
				$parent = $method->getDeclaringClass()->getParentClass();
				if ($parent !== null && $parent->hasConstructor()) {
					$parentConstructor = $parent->getConstructor();
					if ($parentConstructor->isFinal()->yes()) {
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
				}
			}

			return [];
		}

		if (!$prototype instanceof MethodPrototypeReflection) {
			return [];
		}

		$messages = [];
		if ($prototype->isFinal()) {
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

		if (
			$this->phpVersion->hasTentativeReturnTypes()
			&& $prototype->getTentativeReturnType() !== null
			&& !$this->hasReturnTypeWillChangeAttribute($node->getOriginalNode())
		) {

			if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($prototype->getTentativeReturnType(), $methodVariant->getNativeReturnType(), true)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with tentative return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototype->getTentativeReturnType()->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
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

		if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($prototypeReturnType, $methodReturnType, $this->phpVersion->supportsReturnCovariance())) {
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
	 * @param RuleError[] $errors
	 * @return (string|RuleError)[]
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

}
