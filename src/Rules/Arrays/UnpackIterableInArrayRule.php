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
use function sprintf;

/**
 * @implements Rule<LiteralArrayNode>
 */
final class UnpackIterableInArrayRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
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
				static fn (Type $type): bool => $type->isIterable()->yes(),
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
				$type->describe(VerbosityLevel::typeOnly()),
			))->identifier('arrayUnpacking.nonIterable')->line($item->getStartLine())->build();
		}

		return $errors;
	}

}
