<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[RegisteredRule(level: 0)]
final class AccessStaticTraitMemberRule implements MultipleNodeTypesRule
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Expr\StaticCall::class, StaticMethodCallableNode::class, Expr\StaticPropertyFetch::class];
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesStaticTraitMemberAccess()) {
			return [];
		}

		if ($node instanceof Expr\StaticCall) {
			if ($node->isFirstClassCallable()) {
				return [];
			}
			$class = $node->class;
			$name = $node->name;
		} elseif ($node instanceof StaticMethodCallableNode) {
			$class = $node->getClass();
			$name = $node->getName();
		} elseif ($node instanceof Expr\StaticPropertyFetch) {
			$class = $node->class;
			$name = $node->name;
		} else {
			return [];
		}

		if (!$name instanceof Node\Identifier) {
			return [];
		}

		$trait = $this->findTrait($class, $scope);
		if ($trait === null) {
			return [];
		}

		if ($node instanceof Expr\StaticPropertyFetch) {
			if (!$trait->hasStaticProperty($name->toString())) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Accessing static trait property %s::$%s is deprecated in PHP 8.1, it should only be accessed on a class using the trait.',
					$trait->getDisplayName(),
					$name->toString(),
				))
					->identifier('staticProperty.onTrait')
					->build(),
			];
		}

		if (!$trait->hasNativeMethod($name->toString())) {
			return [];
		}

		$method = $trait->getNativeMethod($name->toString());
		if (!$method->isStatic()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Calling static trait method %s::%s() is deprecated in PHP 8.1, it should only be called on a class using the trait.',
				$trait->getDisplayName(),
				$method->getName(),
			))
				->identifier('staticMethod.onTrait')
				->build(),
		];
	}

	private function findTrait(Name|Expr $class, Scope $scope): ?ClassReflection
	{
		$classType = $class instanceof Name
			? $scope->resolveTypeByName($class)
			: $scope->getType($class)->getObjectTypeOrClassStringObjectType();

		$classReflections = $classType->getObjectClassReflections();
		if (count($classReflections) !== 1 || !$classReflections[0]->isTrait()) {
			return null;
		}

		return $classReflections[0];
	}

}
