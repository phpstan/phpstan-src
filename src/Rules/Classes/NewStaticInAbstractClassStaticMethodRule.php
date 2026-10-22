<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class NewStaticInAbstractClassStaticMethodRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		if (!$scope->isInClass()) {
			return [];
		}

		if (strtolower($node->class->toString()) !== 'static') {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if (!$classReflection->isAbstract()) {
			return [];
		}

		$inMethod = $scope->getFunction();
		if (!$inMethod instanceof PhpMethodFromParserNodeReflection) {
			return [];
		}

		if (!$inMethod->isStatic()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Unsafe usage of new static() in abstract class %s in static method %s().',
				$classReflection->getDisplayName(),
				$inMethod->getName(),
			))
				->identifier('new.staticInAbstractClassStaticMethod')
				->tip(sprintf('Direct call to %s::%s() would crash because an abstract class cannot be instantiated.', $classReflection->getName(), $inMethod->getName()))
				->build(),
		];
	}

}
