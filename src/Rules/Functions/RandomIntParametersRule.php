<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class RandomIntParametersRule implements \PHPStan\Rules\Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var bool */
	private $reportMaybes;

	public function __construct(ReflectionProvider $reflectionProvider, bool $reportMaybes)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->reportMaybes = $reportMaybes;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}

		if ($this->reflectionProvider->resolveFunctionName($node->name, $scope) !== 'random_int') {
			return [];
		}

		$minType = $scope->getType($node->args[0]->value)->toInteger();
		$maxType = $scope->getType($node->args[1]->value)->toInteger();
		$integerType = new IntegerType();

		if ($minType->equals($integerType) || $maxType->equals($integerType)) {
			return [];
		}

		if ($minType instanceof ConstantIntegerType || $minType instanceof IntegerRangeType) {
			if ($minType instanceof ConstantIntegerType) {
				$maxPermittedType = IntegerRangeType::fromInterval($minType->getValue(), PHP_INT_MAX);
			} else {
				$maxPermittedType = IntegerRangeType::fromInterval($minType->getMax(), PHP_INT_MAX);
			}

			if (!$maxPermittedType->isSuperTypeOf($maxType)->yes()) {
				$message = 'Parameter #1 $min (%s) of function random_int expects lower number than parameter #2 $max (%s).';

				// True if sometimes the parameters conflict.
				$isMaybe = !$maxType->isSuperTypeOf($minType)->no();

				if (!$isMaybe || $this->reportMaybes) {
					return [
						RuleErrorBuilder::message(sprintf(
							$message,
							$minType->describe(VerbosityLevel::value()),
							$maxType->describe(VerbosityLevel::value())
						))->build(),
					];
				}
			}
		}

		return [];
	}

}
