<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class YieldInGeneratorRule implements Rule
{

	private bool $reportMaybes;

	public function __construct(bool $reportMaybes)
	{
		$this->reportMaybes = $reportMaybes;
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\Yield_ && !$node instanceof Node\Expr\YieldFrom) {
			return [];
		}

		$anonymousFunctionReturnType = $scope->getAnonymousFunctionReturnType();
		$scopeFunction = $scope->getFunction();
		if ($anonymousFunctionReturnType !== null) {
			$returnType = $anonymousFunctionReturnType;
		} elseif ($scopeFunction !== null) {
			$returnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
		} else {
			return [RuleErrorBuilder::message('Yield can be used only inside a function.')->build()];
		}

		if ($returnType instanceof MixedType) {
			return [];
		}

		$isSuperType = $returnType->isIterable()->and(TrinaryLogic::createFromBoolean(
			!$returnType->isArray()->yes()
		));
		if ($isSuperType->yes()) {
			return [];
		}

		if ($isSuperType->maybe() && !$this->reportMaybes) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Yield can be used only with these return types: %s.',
				'Generator, Iterator, Traversable, iterable'
			))->build(),
		];
	}

}
