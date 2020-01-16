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

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
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
				$message = 'Cannot call random_int() when $min parameter (%s) can be greater than $max parameter (%s).';

				if ($maxType->isSuperTypeOf($minType)->no()) {
					$message = 'Cannot call random_int() when $min parameter (%s) is greater than $max parameter (%s).';
				}

				return [
					RuleErrorBuilder::message(sprintf(
						$message,
						$minType->describe(VerbosityLevel::value()),
						$maxType->describe(VerbosityLevel::value())
					))->build(),
				];
			}
		}

		return [];
	}

}
