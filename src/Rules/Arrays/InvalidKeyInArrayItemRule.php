<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\ArrayItem>
 */
class InvalidKeyInArrayItemRule implements Rule
{

	public function __construct(private bool $reportMaybes)
	{
	}

	public function getNodeType(): string
	{
		return Node\ArrayItem::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->key === null) {
			return [];
		}

		$dimensionType = $scope->getType($node->key);
		$isSuperType = AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
		if ($isSuperType->no()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly())),
				)->identifier('array.invalidKey')->build(),
			];
		} elseif ($this->reportMaybes && $isSuperType->maybe() && !$dimensionType instanceof MixedType) {
			return [
				RuleErrorBuilder::message(
					sprintf('Possibly invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly())),
				)->identifier('array.invalidKey')->build(),
			];
		}

		return [];
	}

}
