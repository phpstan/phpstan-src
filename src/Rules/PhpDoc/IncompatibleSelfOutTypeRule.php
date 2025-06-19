<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(level: 2)]
final class IncompatibleSelfOutTypeRule implements Rule
{

	public function __construct(
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
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
		$selfOutType = $method->getSelfOutType();

		if ($selfOutType === null) {
			return [];
		}

		$classReflection = $method->getDeclaringClass();
		$classType = new ObjectType($classReflection->getName(), classReflection: $classReflection);

		$errors = [];
		if (!$classType->isSuperTypeOf($selfOutType)->yes()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Self-out type %s of method %s::%s is not subtype of %s.',
				$selfOutType->describe(VerbosityLevel::precise()),
				$classReflection->getDisplayName(),
				$method->getName(),
				$classType->describe(VerbosityLevel::precise()),
			))->identifier('selfOut.type')->build();
		}

		if ($method->isStatic()) {
			$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-self-out is not supported above static method %s::%s().', $classReflection->getName(), $method->getName()))
				->identifier('selfOut.static')
				->build();
		}

		if ($this->unresolvableTypeHelper->containsUnresolvableType($selfOutType)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @phpstan-self-out for method %s::%s() contains unresolvable type.',
				$classReflection->getDisplayName(),
				$method->getName(),
			))->identifier('selfOut.unresolvableType')->build();
		}

		$escapedTagName = SprintfHelper::escapeFormatString('@phpstan-self-out');

		return array_merge($errors, $this->genericObjectTypeCheck->check(
			$selfOutType,
			sprintf(
				'PHPDoc tag %s contains generic type %%s but %%s %%s is not generic.',
				$escapedTagName,
			),
			sprintf(
				'Generic type %%s in PHPDoc tag %s does not specify all template types of %%s %%s: %%s',
				$escapedTagName,
			),
			sprintf(
				'Generic type %%s in PHPDoc tag %s specifies %%d template types, but %%s %%s supports only %%d: %%s',
				$escapedTagName,
			),
			sprintf(
				'Type %%s in generic type %%s in PHPDoc tag %s is not subtype of template type %%s of %%s %%s.',
				$escapedTagName,
			),
			sprintf(
				'Call-site variance of %%s in generic type %%s in PHPDoc tag %s is in conflict with %%s template type %%s of %%s %%s.',
				$escapedTagName,
			),
			sprintf(
				'Call-site variance of %%s in generic type %%s in PHPDoc tag %s is redundant, template type %%s of %%s %%s has the same variance.',
				$escapedTagName,
			),
		));
	}

}
