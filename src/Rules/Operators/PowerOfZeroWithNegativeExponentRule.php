<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[RegisteredRule(level: 2)]
final class PowerOfZeroWithNegativeExponentRule implements MultipleNodeTypesRule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Expr\BinaryOp\Pow::class, Expr\AssignOp\Pow::class, Expr\FuncCall::class];
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesPowerOfZeroWithNegativeExponent()) {
			return [];
		}

		if ($node instanceof Expr\BinaryOp\Pow) {
			$base = $node->left;
			$exponent = $node->right;
		} elseif ($node instanceof Expr\AssignOp\Pow) {
			$base = $node->var;
			$exponent = $node->expr;
		} elseif ($node instanceof Expr\FuncCall) {
			$args = $this->getPowArgs($node, $scope);
			if ($args === null) {
				return [];
			}
			[$base, $exponent] = $args;
		} else {
			return [];
		}

		$baseType = $scope->getType($base);
		$exponentType = $scope->getType($exponent);
		if (!$baseType->isObject()->no() || !$exponentType->isObject()->no()) {
			return [];
		}

		if (
			!$scope->getScopeType(new Expr\BinaryOp\Equal(new TypeExpr($baseType), new Node\Scalar\Int_(0)))->isTrue()->yes()
			|| !$scope->getScopeType(new Expr\BinaryOp\Smaller(new TypeExpr($exponentType), new Node\Scalar\Int_(0)))->isTrue()->yes()
		) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message('Power of base 0 and negative exponent is deprecated in PHP 8.4.')
			->tip('Use fpow() to get the IEEE 754 result.')
			->identifier('pow.zeroBaseNegativeExponent');

		// fpow() has float parameters, so only int and float operands keep working with strict types
		if (
			!$node instanceof Expr\AssignOp\Pow
			&& $this->isIntOrFloat($baseType)
			&& $this->isIntOrFloat($exponentType)
		) {
			if ($node instanceof Expr\FuncCall) {
				$errorBuilder->fixNode($node, static fn (Expr\FuncCall $node) => new Expr\FuncCall(new Node\Name('fpow'), $node->args));
			} else {
				$errorBuilder->fixNode($node, static fn (Expr\BinaryOp\Pow $node) => new Expr\FuncCall(new Node\Name('fpow'), [
					new Node\Arg($node->left),
					new Node\Arg($node->right),
				]));
			}
		}

		return [$errorBuilder->build()];
	}

	/**
	 * @return array{Expr, Expr}|null
	 */
	private function getPowArgs(Expr\FuncCall $node, Scope $scope): ?array
	{
		if (!$node->name instanceof Node\Name || count($node->getArgs()) !== 2) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return null;
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($functionReflection->getName() !== 'pow') {
			return null;
		}

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$functionReflection->getVariants(),
				$functionReflection->getNamedArgumentsVariants(),
			),
			$node,
		);
		if ($normalizedFuncCall === null) {
			return null;
		}

		$args = $normalizedFuncCall->getArgs();
		if (count($args) !== 2) {
			return null;
		}

		return [$args[0]->value, $args[1]->value];
	}

	private function isIntOrFloat(Type $type): bool
	{
		return (new UnionType([new IntegerType(), new FloatType()]))->isSuperTypeOf($type)->yes();
	}

}
