<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class MissingMethodReturnTypehintRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Rules\MissingTypehintCheck */
	private $missingTypehintCheck;

	public function __construct(MissingTypehintCheck $missingTypehintCheck)
	{
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$methodReflection = $scope->getClassReflection()->getNativeMethod($node->name->name);

		$returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

		if ($returnType instanceof MixedType && !$returnType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Method %s::%s() has no return typehint specified.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName()
				))->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($returnType) as $iterableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() return type has no value type specified in iterable type %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$iterableType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($returnType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() return type with generic %s does not specify its types: %s',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		return $messages;
	}

}
