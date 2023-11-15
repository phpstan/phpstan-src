<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Scalar\MagicConst;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/** @implements Rule<MagicConst> */
class MagicConstantContextRule implements Rule
{

	public function getNodeType(): string
	{
		return MagicConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof MagicConst\Class_ || $node instanceof MagicConst\Method) {
			if ($scope->isInClass()) {
				return [];
			}
		} elseif ($node instanceof MagicConst\Function_) {
			// __FUNCTION__ can be used in a method, but __METHOD__ cannot be used in a function
			// https://3v4l.org/3CAHm
			if ($scope->getFunctionName() !== null) {
				return [];
			}
		} elseif ($node instanceof MagicConst\Namespace_) {
			if ($scope->getNamespace() === null) {
				return [
					RuleErrorBuilder::message(
						sprintf('Magic constant %s is always empty when used in global namespace.', $node->getName()),
					)->build(),
				];
			}

			return [];
		} else {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Magic constant %s is always empty when used outside a class.', $node->getName()),
			)->build(),
		];
	}

}
