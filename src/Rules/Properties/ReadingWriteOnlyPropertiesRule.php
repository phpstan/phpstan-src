<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class ReadingWriteOnlyPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private RuleLevelHelper $ruleLevelHelper;

	private bool $checkThisOnly;

	public function __construct(
		PropertyDescriptor $propertyDescriptor,
		PropertyReflectionFinder $propertyReflectionFinder,
		RuleLevelHelper $ruleLevelHelper,
		bool $checkThisOnly
	)
	{
		$this->propertyDescriptor = $propertyDescriptor;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->checkThisOnly = $checkThisOnly;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
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

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($node, $scope);
		if (count($propertyReflections) === 0) {
			return [];
		}

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			if (!$scope->canAccessProperty($propertyReflection)) {
				continue;
			}

			if ($propertyReflection->isReadable()) {
				continue;
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $node);

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'%s is not readable.',
					$propertyDescription
				)
			)->build();
		}

		return $errors;
	}

}
