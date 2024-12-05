<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use const ENT_SUBSTITUTE;

final class NonEmptyStringFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'addslashes',
			'addcslashes',
			'escapeshellarg',
			'escapeshellcmd',
			'htmlspecialchars',
			'htmlentities',
			'urlencode',
			'urldecode',
			'preg_quote',
			'rawurlencode',
			'rawurldecode',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		if (in_array($functionReflection->getName(), [
			'htmlspecialchars',
			'htmlentities',
		], true)) {
			if (!$this->isSubstituteFlagSet($args, $scope)) {
				return new StringType();
			}
		}

		$argType = $scope->getType($args[0]->value);
		if ($argType->isNonFalsyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($argType->isNonEmptyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		return new StringType();
	}

	/**
	 * @param Arg[] $args
	 */
	private function isSubstituteFlagSet(
		array $args,
		Scope $scope,
	): bool
	{
		if (!isset($args[1])) {
			return true;
		}
		$flagsType = $scope->getType($args[1]->value);
		if (!$flagsType instanceof ConstantIntegerType) {
			return false;
		}
		return (bool) ($flagsType->getValue() & ENT_SUBSTITUTE);
	}

}
