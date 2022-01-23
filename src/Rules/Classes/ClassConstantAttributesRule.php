<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
class ClassConstantAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CLASS_CONSTANT,
			'class constant',
		);
	}

}
