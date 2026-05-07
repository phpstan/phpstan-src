<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function count;

#[AutowiredService]
final class VersionCompareFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return !$context->null()
			&& $functionReflection->getName() === 'version_compare'
			&& count($node->getArgs()) === 3;
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$parsed = VersionCompareHelper::parseVersionCompareFuncCall($node, $scope);
		if ($parsed === null) {
			return new SpecifiedTypes([], []);
		}

		[$phpVersionArgIndex, $versionId] = $parsed;

		$args = $node->getArgs();
		$operatorStrings = $scope->getType($args[2]->value)->getConstantStrings();
		if (count($operatorStrings) !== 1) {
			return new SpecifiedTypes([], []);
		}

		$comparisonClass = VersionCompareHelper::operatorToComparisonClass($operatorStrings[0]->getValue());
		if ($comparisonClass === null) {
			return new SpecifiedTypes([], []);
		}

		$phpVersionIdExpr = new ConstFetch(new Name('PHP_VERSION_ID'));
		$versionIdExpr = new Int_($versionId);

		if ($phpVersionArgIndex === 0) {
			$syntheticExpr = new $comparisonClass($phpVersionIdExpr, $versionIdExpr);
		} else {
			$syntheticExpr = new $comparisonClass($versionIdExpr, $phpVersionIdExpr);
		}

		return $this->typeSpecifier->specifyTypesInCondition($scope, $syntheticExpr, $context);
	}

}
