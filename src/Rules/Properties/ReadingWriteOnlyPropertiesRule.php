<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Node\Expr>
 */
#[RegisteredRule(level: 0)]
final class ReadingWriteOnlyPropertiesRule implements MultipleNodeTypesRule
{

	public function __construct(
		private PropertyDescriptor $propertyDescriptor,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private RuleLevelHelper $ruleLevelHelper,
		#[AutowiredParameter]
		private bool $checkThisOnly,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Node\Expr\PropertyFetch::class, Node\Expr\StaticPropertyFetch::class];
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!($node instanceof Node\Expr\PropertyFetch)
			&& !($node instanceof Node\Expr\StaticPropertyFetch)
		) {
			return [];
		}

		if (
			$node instanceof Node\Expr\PropertyFetch
			&& $this->checkThisOnly
			&& !$this->ruleLevelHelper->isThis($node->var)
		) {
			return [];
		}

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node, $scope);
		if ($propertyReflection === null) {
			return [];
		}
		if (!$scope->canReadProperty($propertyReflection)) {
			return [];
		}

		if (!$propertyReflection->isReadable()) {
			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $scope, $node);

			return [
				RuleErrorBuilder::message(sprintf(
					'%s is not readable.',
					$propertyDescription,
				))
					->identifier('property.writeOnly')
					->build(),
			];
		}

		return [];
	}

}
