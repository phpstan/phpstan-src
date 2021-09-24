<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\LiteralArrayNode>
 */
class UnpackIterableInArrayRule implements Rule
{

	private RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return LiteralArrayNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->getItemNodes() as $itemNode) {
			$item = $itemNode->getArrayItem();
			if ($item === null) {
				continue;
			}
			if (!$item->unpack) {
				continue;
			}

			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$item->value,
				'',
				static function (Type $type): bool {
					return $type->isIterable()->yes();
				},
				false
			);
			$type = $typeResult->getType();
			if ($type instanceof ErrorType) {
				continue;
			}

			if ($type->isIterable()->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Only iterables can be unpacked, %s given.',
				$type->describe(VerbosityLevel::typeOnly())
			))->line($item->getLine())->build();
		}

		return $errors;
	}

}
