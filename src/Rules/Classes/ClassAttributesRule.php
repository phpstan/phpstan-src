<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\ClassLike>
 */
class ClassAttributesRule implements Rule
{

	private AttributesCheck $attributesCheck;

	public function __construct(AttributesCheck $attributesCheck)
	{
		$this->attributesCheck = $attributesCheck;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CLASS,
			'class'
		);
	}

}
