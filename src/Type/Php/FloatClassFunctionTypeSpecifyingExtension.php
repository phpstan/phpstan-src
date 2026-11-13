<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function strtolower;
use const INF;
use const NAN;
use const PHP_FLOAT_MAX;

/**
 * is_nan(), is_finite() and is_infinite() as the values each branch rules out.
 * An int is finite and never NAN; a string argument is coerced, so it stays in
 * both branches.
 */
#[AutowiredService]
final class FloatClassFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	/**
	 * The values the true branch and the false branch rule out, null for another function.
	 *
	 * @return array{Type, Type}|null
	 */
	public static function getRuledOutTypes(string $functionName): ?array
	{
		$nan = new ConstantFloatType(NAN);
		$infinite = TypeCombinator::union(new ConstantFloatType(-INF), new ConstantFloatType(INF));
		$finite = TypeCombinator::union(new IntegerType(), FloatRangeType::fromInterval(-PHP_FLOAT_MAX, PHP_FLOAT_MAX));

		switch (strtolower($functionName)) {
			case 'is_nan':
				return [TypeCombinator::union($finite, $infinite), $nan];
			case 'is_finite':
				return [TypeCombinator::union($nan, $infinite), $finite];
			case 'is_infinite':
				return [TypeCombinator::union($nan, $finite), $infinite];
		}

		return null;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return !$context->null() && self::getRuledOutTypes($functionReflection->getName()) !== null;
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$ruledOutTypes = self::getRuledOutTypes($functionReflection->getName());
		$args = $node->getArgs();
		if (!isset($args[0]) || $ruledOutTypes === null) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create(
			$args[0]->value,
			$context->true() ? $ruledOutTypes[0] : $ruledOutTypes[1],
			TypeSpecifierContext::createFalse(),
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
