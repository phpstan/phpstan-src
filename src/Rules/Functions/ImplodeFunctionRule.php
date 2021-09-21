<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class ImplodeFunctionRule implements \PHPStan\Rules\Rule
{
	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}

		$name = strtolower((string) $node->name);
		if ($name !== 'implode') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 2) {
			return [];
		}

		$arrayType = $scope->getType($args[1]->value);
		if ($arrayType->getIterableValueType()->isArray()->yes()) {
			return [
				RuleErrorBuilder::message(
					'Call to implode with invalid nested array argument.',
				)->build()
			];
		}
		if ($arrayType->getIterableValueType() instanceof UnionType) {
			foreach ($arrayType->getIterableValueType()->getTypes() as $subType) {
				if ($subType->isArray()->yes()) {
					return [
						RuleErrorBuilder::message(
							'Call to implode with invalid nested array argument in union type.',
						)->build()
					];
				}
			}
		}

		return [];
	}

}
