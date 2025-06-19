<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function count;
use function is_array;
use function version_compare;

#[AutowiredService]
final class VersionCompareFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%phpVersion%')]
		private int|array|null $configPhpVersion,
		private ComposerPhpVersionFactory $composerPhpVersionFactory,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'version_compare';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$version1Strings = $this->getVersionStrings($args[0]->value, $scope);
		$version2Strings = $this->getVersionStrings($args[1]->value, $scope);
		$counts = [
			count($version1Strings),
			count($version2Strings),
		];

		if (isset($args[2])) {
			$operatorStrings = $scope->getType($args[2]->value)->getConstantStrings();
			$counts[] = count($operatorStrings);
			$returnType = new BooleanType();
		} else {
			$returnType = TypeCombinator::union(
				new ConstantIntegerType(-1),
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			);
		}

		if (count(array_filter($counts, static fn (int $count): bool => $count === 0)) > 0) {
			return $returnType; // one of the arguments is not a constant string
		}

		if (count(array_filter($counts, static fn (int $count): bool => $count > 1)) > 1) {
			return $returnType; // more than one argument can have multiple possibilities, avoid combinatorial explosion
		}

		$types = [];
		foreach ($version1Strings as $version1String) {
			foreach ($version2Strings as $version2String) {
				if (isset($operatorStrings)) {
					foreach ($operatorStrings as $operatorString) {
						$value = version_compare($version1String->getValue(), $version2String->getValue(), $operatorString->getValue());
						$types[$value] = new ConstantBooleanType($value);
					}
				} else {
					$value = version_compare($version1String->getValue(), $version2String->getValue());
					$types[$value] = new ConstantIntegerType($value);
				}
			}
		}
		return TypeCombinator::union(...$types);
	}

	/**
	 * @return ConstantStringType[]
	 */
	private function getVersionStrings(Expr $expr, Scope $scope): array
	{
		if (
			$expr instanceof Expr\ConstFetch
			&& $expr->name->toString() === 'PHP_VERSION'
		) {
			if (is_array($this->configPhpVersion)) {
				$minVersion = new PhpVersion($this->configPhpVersion['min']);
				$maxVersion = new PhpVersion($this->configPhpVersion['max']);
			} else {
				$minVersion = $this->composerPhpVersionFactory->getMinVersion();
				$maxVersion = $this->composerPhpVersionFactory->getMaxVersion();
			}

			if ($minVersion !== null && $maxVersion !== null) {
				return [
					new ConstantStringType($minVersion->getVersionString()),
					new ConstantStringType($maxVersion->getVersionString()),
				];
			}
		}

		return $scope->getType($expr)->getConstantStrings();
	}

}
