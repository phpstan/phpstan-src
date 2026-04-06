<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function is_string;
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

		if ($this->reflectionProvider->resolveFunctionName($node->name, $scope) !== 'random_int') {
			return [];
		}

		$args = $node->getArgs();
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

		if ($isSmaller->yes() || $isSmaller->maybe() && $this->reportMaybes && !$this->sharesVariable($args[0]->value, $args[1]->value)) {
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

	private function sharesVariable(Node\Expr $expr1, Node\Expr $expr2): bool
	{
		$vars1 = $this->extractVariableNames($expr1);
		if ($vars1 === []) {
			return false;
		}

		$vars2 = $this->extractVariableNames($expr2);

		foreach ($vars1 as $var => $_) {
			if (isset($vars2[$var])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string, true>
	 */
	private function extractVariableNames(Node\Expr $expr): array
	{
		$vars = [];
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$vars[$expr->name] = true;
		}

		foreach ($expr->getSubNodeNames() as $name) {
			$subNode = $expr->{$name};
			if (!($subNode instanceof Node\Expr)) {
				continue;
			}

			foreach ($this->extractVariableNames($subNode) as $var => $_) {
				$vars[$var] = true;
			}
		}

		return $vars;
	}

}
