<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\VerbosityLevel;
use function array_values;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class RandomIntParametersRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
		private bool $reportMaybes
	) {
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if ($this->reflectionProvider->resolveFunctionName($node->name, $scope) !== 'random_int') {
			return [];
		}

		$args = array_values($node->getArgs());
		if (count($args) < 2) {
			return [];
		}

		$minType = $scope->getType($args[0]->value)->toInteger();
		$maxType = $scope->getType($args[1]->value)->toInteger();

		if (
			!$minType instanceof ConstantIntegerType && !$minType instanceof IntegerRangeType
			|| !$maxType instanceof ConstantIntegerType && !$maxType instanceof IntegerRangeType
		) {
			return [];
		}

		$isSmaller = $maxType->isSmallerThan($minType, $this->phpVersion);

		if ($isSmaller->yes() || $isSmaller->maybe() && $this->reportMaybes) {
			$message = 'Parameter #1 $min (%s) of function random_int expects lower number than parameter #2 $max (%s).';
			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$minType->describe(VerbosityLevel::value()),
					$maxType->describe(VerbosityLevel::value()),
				))->identifier('argument.type')->build(),
			];
		}

		return [];
	}

}
