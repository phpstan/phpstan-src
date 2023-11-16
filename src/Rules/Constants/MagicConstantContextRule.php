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
		if ($node instanceof MagicConst\Class_) {
			if ($scope->isInClass()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(
					sprintf('Magic constant %s is always empty when used outside a class.', $node->getName()),
				)->build(),
			];
		} elseif ($node instanceof MagicConst\Trait_) {
			if ($scope->isInTrait()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(
					sprintf('Magic constant %s is always empty when used outside a trait-using-class.', $node->getName()),
				)->build(),
			];
		} elseif ($node instanceof MagicConst\Method || $node instanceof MagicConst\Function_) {
			// https://3v4l.org/3CAHm
			if ($scope->isInClass() || $scope->getFunctionName() !== null) {
				return [];
			}

			return [
				RuleErrorBuilder::message(
					sprintf('Magic constant %s is always empty when used outside a function.', $node->getName()),
				)->build(),
			];
		} elseif ($node instanceof MagicConst\Namespace_) {
			if ($scope->getNamespace() === null) {
				return [
					RuleErrorBuilder::message(
						sprintf('Magic constant %s is always empty when used in global namespace.', $node->getName()),
					)->build(),
				];
			}
		}
		return [];
	}

}
