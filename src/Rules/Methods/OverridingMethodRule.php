<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassMethodNode>
 */
class OverridingMethodRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof PhpMethodFromParserNodeReflection) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$prototype = $method->getPrototype();
		if ($prototype->getDeclaringClass()->getName() === $method->getDeclaringClass()->getName()) {
			return [];
		}
		if (!$prototype instanceof MethodPrototypeReflection) {
			return [];
		}

		$messages = [];
		if ($prototype->isFinal()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides final method %s::%s().',
				$method->getDeclaringClass()->getName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getName(),
				$prototype->getName()
			))->nonIgnorable()->build();
		}

		if ($prototype->isPublic()) {
			if (!$method->isPublic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s method %s::%s() overriding public method %s::%s() should also be public.',
					$method->isPrivate() ? 'Private' : 'Protected',
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		} else {
			if ($method->isPrivate()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Private method %s::%s() overriding protected method %s::%s() should be protected or public.',
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		}

		return $messages;
	}

}
