<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(level: 6)]
final class MissingMethodSelfOutTypeRule implements Rule
{

	public function __construct(
		private MissingTypehintCheck $missingTypehintCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $node->getMethodReflection();
		$selfOutType = $methodReflection->getSelfOutType();

		if ($selfOutType === null) {
			return [];
		}

		$classReflection = $methodReflection->getDeclaringClass();
		$phpDocTagMessage = 'PHPDoc tag @phpstan-self-out';

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($selfOutType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s with no value type specified in iterable type %s.',
				$classReflection->getDisplayName(),
				$methodReflection->getName(),
				$phpDocTagMessage,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($selfOutType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s with generic %s but does not specify its types: %s',
				$classReflection->getDisplayName(),
				$methodReflection->getName(),
				$phpDocTagMessage,
				$name,
				$genericTypeNames,
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($selfOutType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s with no signature specified for %s.',
				$classReflection->getDisplayName(),
				$methodReflection->getName(),
				$phpDocTagMessage,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $messages;
	}

}
