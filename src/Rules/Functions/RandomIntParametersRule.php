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
use function array_unique;
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

		if ($isSmaller->yes() || $isSmaller->maybe() && $this->reportMaybes && !$this->isAlwaysValidDueToSharedVariables($args[0]->value, $args[1]->value, $scope)) {
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

	private function isAlwaysValidDueToSharedVariables(Node\Expr $minExpr, Node\Expr $maxExpr, Scope $scope): bool
	{
		$vars1 = $this->extractVariableNames($minExpr);
		if ($vars1 === []) {
			return false;
		}

		$vars2 = $this->extractVariableNames($maxExpr);

		$hasShared = false;
		foreach ($vars1 as $var => $_) {
			if (isset($vars2[$var])) {
				$hasShared = true;
				break;
			}
		}

		if (!$hasShared) {
			return false;
		}

		// Get all variables from both expressions
		$allVars = $vars1 + $vars2;

		// Get boundary values for each variable
		$varBoundaries = [];
		foreach ($allVars as $var => $_) {
			$varType = $scope->getType(new Node\Expr\Variable($var))->toInteger();
			if ($varType instanceof ConstantIntegerType) {
				$varBoundaries[$var] = [$varType->getValue()];
			} elseif ($varType instanceof IntegerRangeType) {
				$min = $varType->getMin();
				$max = $varType->getMax();
				if ($min === null || $max === null) {
					return false;
				}
				$varBoundaries[$var] = array_unique([$min, $max]);
			} else {
				return false;
			}
		}

		// Generate all combinations of boundary values
		/** @var array<array<string, int>> $combinations */
		$combinations = [[]];
		foreach ($varBoundaries as $var => $values) {
			$newCombinations = [];
			foreach ($combinations as $combo) {
				foreach ($values as $value) {
					$newCombo = $combo;
					$newCombo[$var] = $value;
					$newCombinations[] = $newCombo;
				}
			}
			$combinations = $newCombinations;
		}

		// For each combination, evaluate both expressions and check max >= min
		foreach ($combinations as $combo) {
			$minValue = $this->evaluateExpr($minExpr, $combo);
			$maxValue = $this->evaluateExpr($maxExpr, $combo);

			if ($minValue === null || $maxValue === null) {
				return false;
			}

			if ($maxValue < $minValue) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<string, int> $varValues
	 */
	private function evaluateExpr(Node\Expr $expr, array $varValues): ?int
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			return $varValues[$expr->name] ?? null;
		}

		if ($expr instanceof Node\Scalar\Int_) {
			return $expr->value;
		}

		if ($expr instanceof Node\Expr\BinaryOp) {
			$left = $this->evaluateExpr($expr->left, $varValues);
			$right = $this->evaluateExpr($expr->right, $varValues);
			if ($left === null || $right === null) {
				return null;
			}

			return match (true) {
				$expr instanceof Node\Expr\BinaryOp\Plus => $left + $right,
				$expr instanceof Node\Expr\BinaryOp\Minus => $left - $right,
				$expr instanceof Node\Expr\BinaryOp\Mul => $left * $right,
				default => null,
			};
		}

		if ($expr instanceof Node\Expr\UnaryMinus) {
			$val = $this->evaluateExpr($expr->expr, $varValues);
			return $val !== null ? -$val : null;
		}

		if ($expr instanceof Node\Expr\UnaryPlus) {
			return $this->evaluateExpr($expr->expr, $varValues);
		}

		return null;
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
