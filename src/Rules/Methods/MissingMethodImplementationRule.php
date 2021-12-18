<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class MissingMethodImplementationRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if ($classReflection->isInterface()) {
			return [];
		}
		if ($classReflection->isAbstract()) {
			return [];
		}

		$messages = [];

		try {
			$nativeMethods = $classReflection->getNativeReflection()->getMethods();
		} catch (IdentifierNotFound) {
			return [];
		}
		foreach ($nativeMethods as $method) {
			if (!$method->isAbstract()) {
				continue;
			}

			$declaringClass = $method->getDeclaringClass();

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Non-abstract class %s contains abstract method %s() from %s %s.',
				$classReflection->getDisplayName(),
				$method->getName(),
				$declaringClass->isInterface() ? 'interface' : 'class',
				$declaringClass->getName(),
			))->nonIgnorable()->build();
		}

		return $messages;
	}

}
