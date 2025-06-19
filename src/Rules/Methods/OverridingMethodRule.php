<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
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
		#[AutowiredParameter]
		private bool $checkMissingOverrideMethodAttribute,
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
		$prototypeData = $this->methodPrototypeFinder->findPrototype($node->getClassReflection(), $method->getName());
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
				}
			}

			if ($this->phpVersion->supportsOverrideAttribute() && $this->hasOverrideAttribute($node->getOriginalNode())) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Method %s::%s() has #[\Override] attribute but does not override any method.',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
					))
						->nonIgnorable()
						->identifier('method.override')
						->fixNode($node->getOriginalNode(), function (Node\Stmt\ClassMethod $method) {
							$method->attrGroups = $this->filterOverrideAttribute($method->attrGroups);
							return $method;
						})
						->build(),
				];
			}

			return [];
		}

		[$prototype, $prototypeDeclaringClass, $checkVisibility] = $prototypeData;

		$messages = [];
		if (
			$this->phpVersion->supportsOverrideAttribute()
			&& $this->checkMissingOverrideMethodAttribute
			&& !$scope->isInTrait()
			&& !$this->hasOverrideAttribute($node->getOriginalNode())
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides method %s::%s() but is missing the #[\Override] attribute.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototypeDeclaringClass->getDisplayName(true),
				$prototype->getName(),
			))
				->identifier('method.missingOverride')
				->fixNode($node->getOriginalNode(), static function (Node\Stmt\ClassMethod $method) {
					$method->attrGroups[] = new Node\AttributeGroup([
						new Attribute(new Node\Name\FullyQualified('Override')),
					]);

					return $method;
				})
				->build();
		}
		if ($prototype->isFinalByKeyword()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides final method %s::%s().',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototypeDeclaringClass->getDisplayName(true),
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
				$prototypeDeclaringClass->getDisplayName(true),
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
					$prototypeDeclaringClass->getDisplayName(true),
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
				$prototypeDeclaringClass->getDisplayName(true),
				$prototype->getName(),
			))
				->nonIgnorable()
				->identifier('method.static')
				->build();
		}

		if ($checkVisibility) {
			$messages = array_merge($messages, $this->methodVisibilityComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method));
		}

		$prototypeVariants = $prototype->getVariants();
		if (count($prototypeVariants) !== 1) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeVariant = $prototypeVariants[0];

		$methodReturnType = $method->getNativeReturnType();

		$realPrototype = $method->getPrototype();

		if (
			$realPrototype instanceof MethodPrototypeReflection
			&& $this->phpVersion->hasTentativeReturnTypes()
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

		$messages = array_merge($messages, $this->methodParameterComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method, false));

		if (!$prototypeVariant instanceof ExtendedFunctionVariant) {
			return $this->addErrors($messages, $node, $scope);
		}

		$prototypeReturnType = $prototypeVariant->getNativeReturnType();
		$reportReturnType = true;
		if ($this->phpVersion->hasTentativeReturnTypes()) {
			$reportReturnType = !$realPrototype instanceof MethodPrototypeReflection
				|| $realPrototype->getTentativeReturnType() === null
				|| (is_bool($prototype->isBuiltin()) ? !$prototype->isBuiltin() : $prototype->isBuiltin()->no());
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
	 * @param Node\AttributeGroup[] $attrGroups
	 * @return Node\AttributeGroup[]
	 */
	private function filterOverrideAttribute(array $attrGroups): array
	{
		foreach ($attrGroups as $i => $attrGroup) {
			foreach ($attrGroup->attrs as $j => $attr) {
				if ($attr->name->toLowerString() !== 'override') {
					continue;
				}

				unset($attrGroup->attrs[$j]);
				if (count($attrGroup->attrs) !== 0) {
					continue;
				}

				unset($attrGroups[$i]);
			}
		}

		return $attrGroups;
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

	private function hasOverrideAttribute(Node\Stmt\ClassMethod $method): bool
	{
		foreach ($method->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'override') {
					return true;
				}
			}
		}

		return false;
	}

}
