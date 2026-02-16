<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
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
use function explode;
use function in_array;
use function is_numeric;

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
		return $functionReflection->getName() === 'version_compare'
			&& !$context->null()
			&& count($node->getArgs()) === 3;
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();

		$operatorStrings = $scope->getType($args[2]->value)->getConstantStrings();
		if (count($operatorStrings) !== 1) {
			return new SpecifiedTypes([], []);
		}

		$operator = $operatorStrings[0]->getValue();
		if (!in_array($operator, VersionCompareFunctionDynamicReturnTypeExtension::VALID_OPERATORS, true)) {
			return new SpecifiedTypes([], []);
		}

		$phpVersionArgIndex = $this->getPhpVersionArgIndex($args[0]->value, $args[1]->value);
		if ($phpVersionArgIndex === null) {
			return new SpecifiedTypes([], []);
		}

		$otherArgIndex = $phpVersionArgIndex === 0 ? 1 : 0;
		$versionStrings = $scope->getType($args[$otherArgIndex]->value)->getConstantStrings();
		if (count($versionStrings) !== 1) {
			return new SpecifiedTypes([], []);
		}

		$versionId = self::parseVersionStringToId($versionStrings[0]->getValue());
		if ($versionId === null) {
			return new SpecifiedTypes([], []);
		}

		// When PHP_VERSION is the second argument, the comparison direction is swapped
		$swapped = $phpVersionArgIndex === 1;

		$phpVersionIdExpr = new ConstFetch(new Name('PHP_VERSION_ID'));
		$versionIdExpr = new Int_($versionId);

		$comparisonExpr = $this->buildComparisonExpr($phpVersionIdExpr, $versionIdExpr, $operator, $swapped);
		if ($comparisonExpr === null) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->specifyTypesInCondition($scope, $comparisonExpr, $context);
	}

	private function getPhpVersionArgIndex(Expr $arg0, Expr $arg1): ?int
	{
		if ($arg0 instanceof ConstFetch && $arg0->name->toString() === 'PHP_VERSION') {
			return 0;
		}
		if ($arg1 instanceof ConstFetch && $arg1->name->toString() === 'PHP_VERSION') {
			return 1;
		}

		return null;
	}

	/**
	 * @return int|null The PHP_VERSION_ID equivalent of the version string
	 */
	public static function parseVersionStringToId(string $version): ?int
	{
		$parts = explode('.', $version);
		if (count($parts) > 3) {
			return null;
		}

		$major = $parts[0];
		$minor = $parts[1] ?? '0';
		$patch = $parts[2] ?? '0';

		if (!is_numeric($major) || !is_numeric($minor) || !is_numeric($patch)) {
			return null;
		}

		return (int) $major * 10000 + (int) $minor * 100 + (int) $patch;
	}

	private function buildComparisonExpr(Expr $phpVersionIdExpr, Expr $versionIdExpr, string $operator, bool $swapped): ?Expr
	{
		// Normalize operator aliases
		$normalizedOp = match ($operator) {
			'<', 'lt' => '<',
			'<=', 'le' => '<=',
			'>', 'gt' => '>',
			'>=', 'ge' => '>=',
			'==', '=', 'eq' => '==',
			'!=', '<>', 'ne' => '!=',
			default => null,
		};

		if ($normalizedOp === null) {
			return null;
		}

		// When swapped (PHP_VERSION is second arg), reverse the comparison direction
		if ($swapped) {
			$normalizedOp = match ($normalizedOp) {
				'<' => '>',
				'<=' => '>=',
				'>' => '<',
				'>=' => '<=',
				default => $normalizedOp, // == and != are symmetric
			};
		}

		return match ($normalizedOp) {
			'<' => new Smaller($phpVersionIdExpr, $versionIdExpr),
			'<=' => new SmallerOrEqual($phpVersionIdExpr, $versionIdExpr),
			'>' => new Smaller($versionIdExpr, $phpVersionIdExpr),
			'>=' => new SmallerOrEqual($versionIdExpr, $phpVersionIdExpr),
			'==' => new Identical($phpVersionIdExpr, $versionIdExpr),
			default => new NotIdentical($phpVersionIdExpr, $versionIdExpr),
		};
	}

}
