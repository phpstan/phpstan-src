<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class WritingToReadOnlyPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private bool $checkThisOnly;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		PropertyDescriptor $propertyDescriptor,
		PropertyReflectionFinder $propertyReflectionFinder,
		bool $checkThisOnly
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->propertyDescriptor = $propertyDescriptor;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->checkThisOnly = $checkThisOnly;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\Assign
			&& !$node instanceof Node\Expr\AssignOp
		) {
			return [];
		}

		if (
			!($node->var instanceof Node\Expr\PropertyFetch)
			&& !($node->var instanceof Node\Expr\StaticPropertyFetch)
		) {
			return [];
		}

		if (
			$node->var instanceof Node\Expr\PropertyFetch
			&& $this->checkThisOnly
			&& !$this->ruleLevelHelper->isThis($node->var->var)
		) {
			return [];
		}

		/** @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch */
		$propertyFetch = $node->var;

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);
		if (count($propertyReflections) === 0) {
			return [];
		}

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			if (!$scope->canAccessProperty($propertyReflection)) {
				continue;
			}

			if ($propertyReflection->isWritable()) {
				continue;
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $propertyFetch);

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'%s is not writable.',
					$propertyDescription
				)
			)->build();
		}

		return $errors;
	}

}
