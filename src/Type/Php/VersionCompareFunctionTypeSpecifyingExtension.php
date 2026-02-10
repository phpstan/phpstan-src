<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\SimplePhpVersionParser;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function strtolower;

#[AutowiredService]
final class VersionCompareFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'version_compare' && $context->true();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$node->name instanceof Name) {
			return new SpecifiedTypes();
		}

		$args = $node->getArgs();
		if (count($args) < 2) {
			return new SpecifiedTypes();
		}

		$version1 = $args[0]->value;
		$version2 = $args[1]->value;

		if (
			$version1 instanceof ConstFetch
			&& $version1->name->name === 'PHP_VERSION'
			&& $version2 instanceof String_
		) {
			$integerVersionRange = $this->getVersionCompareType($version2->value, isset($args[2]) ? $args[2]->value : null, $scope);

			if ($integerVersionRange !== null) {
				$narrowedVersion = TypeCombinator::intersect($scope->getPhpVersion()->getType(), $integerVersionRange);
				return $this->typeSpecifier->create(
					new ConstFetch(new Name('\\PHP_VERSION_ID')),
					$narrowedVersion,
					$context,
					$scope,
				);
			}
		}

		if (
			$version2 instanceof ConstFetch
			&& $version2->name->name === 'PHP_VERSION'
			&& $version1 instanceof String_
		) {
			$integerVersionRange = $this->getVersionCompareType($version1->value, isset($args[2]) ? $args[2]->value : null, $scope);
			if ($integerVersionRange !== null) {
				$narrowedVersion = TypeCombinator::intersect($scope->getPhpVersion()->getType(), $integerVersionRange);
				return $this->typeSpecifier->create(
					new ConstFetch(new Name('\\PHP_VERSION_ID')),
					$narrowedVersion,
					$context,
					$scope,
				);
			}
		}

		return new SpecifiedTypes();
	}

	private function getVersionCompareType(string $value, ?Expr $operator, Scope $scope): ?Type
	{
		$parsedVersion = SimplePhpVersionParser::parseVersion($value);
		if ($parsedVersion === null) {
			return null;
		}

		if ($operator !== null) {
			$operators = $scope->getType($operator)->getConstantStrings();
			if (count($operators) !== 1) {
				return null;
			}

			$operatorString = $operators[0]->getValue();
		} else {
			$operatorString = '<';
		}

		if (!in_array($operatorString, VersionCompareFunctionDynamicReturnTypeExtension::VALID_OPERATORS, true)) {
			return null;
		}

		if (in_array($operatorString, ['<', 'lt'], true)) {
			return IntegerRangeType::fromInterval(null, $parsedVersion->getVersionId() - 1);
		}
		if (in_array($operatorString, ['<=', 'le'], true)) {
			return IntegerRangeType::fromInterval(null, $parsedVersion->getVersionId());
		}

		if (in_array($operatorString, ['>', 'gt'], true)) {
			return IntegerRangeType::fromInterval($parsedVersion->getVersionId() + 1, null);
		}
		if (in_array($operatorString, ['>=', 'ge'], true)) {
			return IntegerRangeType::fromInterval($parsedVersion->getVersionId(), null);
		}

		if (
			in_array($operatorString, ['==', '=', 'eq'], true)
		) {
			return new ConstantIntegerType($parsedVersion->getVersionId());
		}

		return TypeCombinator::remove(new IntegerType(), new ConstantIntegerType($parsedVersion->getVersionId()));
	}

}
