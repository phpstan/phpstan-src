<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\ArrayItem>
 */
#[RegisteredRule(level: 3)]
final class InvalidKeyInArrayItemRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
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

		$dimensionType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->key,
			'',
			static fn (Type $dimType): bool => AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimType)->yes(),
		)->getType();
		if ($dimensionType instanceof ErrorType) {
			return [];
		}

		$isSuperType = AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
		if ($isSuperType->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s array key type %s.',
				$isSuperType->no() ? 'Invalid' : 'Possibly invalid',
				$dimensionType->describe(VerbosityLevel::typeOnly()),
			))->identifier('array.invalidKey')->build(),
		];
	}

}
