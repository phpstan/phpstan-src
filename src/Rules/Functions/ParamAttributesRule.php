<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use function count;

/**
 * @implements Rule<Node\Param>
 */
class ParamAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Param::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$targetName = 'parameter';
		if ($node->flags !== 0) {
			$targetName = 'parameter or property';

			$propertyTargetErrors = $this->attributesCheck->check(
				$scope,
				$node->attrGroups,
				Attribute::TARGET_PROPERTY,
				$targetName,
			);

			if (count($propertyTargetErrors) === 0) {
				return $propertyTargetErrors;
			}
		}

		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_PARAMETER,
			$targetName,
		);
	}

}
