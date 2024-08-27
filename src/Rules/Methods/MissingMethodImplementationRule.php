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
final class MissingMethodImplementationRule implements Rule
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

			$classLikeDescription = 'Non-abstract class';
			if ($classReflection->isEnum()) {
				$classLikeDescription = 'Enum';
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s %s contains abstract method %s() from %s %s.',
				$classLikeDescription,
				$classReflection->getDisplayName(),
				$method->getName(),
				$declaringClass->isInterface() ? 'interface' : 'class',
				$declaringClass->getName(),
			))->nonIgnorable()->identifier('method.abstract')->build();
		}

		return $messages;
	}

}
