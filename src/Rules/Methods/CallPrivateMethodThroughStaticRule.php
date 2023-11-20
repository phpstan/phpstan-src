<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<StaticCall>
 */
class CallPrivateMethodThroughStaticRule implements Rule
{

	public function getNodeType(): string
	{
		return StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		if (!$node->class instanceof Name) {
			return [];
		}

		$methodName = $node->name->name;
		$className = $node->class;
		if ($className->toLowerString() !== 'static') {
			return [];
		}

		$classType = $scope->resolveTypeByName($className);
		if (!$classType->hasMethod($methodName)->yes()) {
			return [];
		}

		$method = $classType->getMethod($methodName, $scope);
		if (!$method->isPrivate()) {
			return [];
		}

		if ($scope->isInClass() && $scope->getClassReflection()->isFinal()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Unsafe call to private method %s::%s() through static::.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('staticClassAccess.privateMethod')->build(),
		];
	}

}
