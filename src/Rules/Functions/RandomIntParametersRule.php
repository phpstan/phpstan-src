<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 5)]
final class RandomIntParametersRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
		#[AutowiredParameter]
		private bool $reportMaybes,
	)
	{
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

		$args = $node->getArgs();
		if (count($args) < 2) {
			return [];
		}

		if ($this->reflectionProvider->resolveFunctionName($node->name, $scope) !== 'random_int') {
			return [];
		}

		$minArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'min');
		$maxArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'max');
		if ($minArg === null || $maxArg === null) {
			return [];
		}

		$minType = $scope->getType($minArg->value)->toInteger();
		$maxType = $scope->getType($maxArg->value)->toInteger();

		$minBounded = $this->isBoundedIntegerType($minType);
		$maxBounded = $this->isBoundedIntegerType($maxType);
		if ($minBounded === null || $maxBounded === null) {
			return [];
		}

		$isSmaller = $maxType->isSmallerThan($minType, $this->phpVersion);

		if ($isSmaller->maybe() && $this->reportMaybes) {
			if (!$minBounded || !$maxBounded) {
				return [];
			}
		}

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

	/**
	 * Returns null when the type is not composed solely of integer constants and integer ranges,
	 * otherwise whether all of its parts are bounded on both sides.
	 */
	private function isBoundedIntegerType(Type $type): ?bool
	{
		$bounded = true;
		foreach (TypeUtils::flattenTypes($type) as $innerType) {
			if ($innerType instanceof ConstantIntegerType) {
				continue;
			}
			if (!$innerType instanceof IntegerRangeType) {
				return null;
			}
			if ($innerType->getMin() !== null && $innerType->getMax() !== null) {
				continue;
			}

			$bounded = false;
		}

		return $bounded;
	}

}
