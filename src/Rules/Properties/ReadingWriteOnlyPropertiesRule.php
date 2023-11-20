<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
class ReadingWriteOnlyPropertiesRule implements Rule
{

	public function __construct(
		private PropertyDescriptor $propertyDescriptor,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $checkThisOnly,
	)
	{
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
		if (!$scope->canAccessProperty($propertyReflection)) {
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
