<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_unique;
use function count;
use function in_array;

#[AutowiredService]
final class OpensslCipherFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private PhpVersion $phpVersion,
		private OpenSslCipherMethodsProvider $cipherMethodsProvider,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['openssl_cipher_iv_length', 'openssl_cipher_key_length'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return null;
		}

		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$strings = $scope->getType($functionCall->getArgs()[0]->value)->getConstantStrings();
		$results = array_unique(array_map(fn (ConstantStringType $algorithm): bool => $this->cipherMethodsProvider->isSupportedCipherMethod($algorithm->getValue()), $strings));

		if (count($results) !== 1) {
			return null;
		}

		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		return $results[0]
			? TypeCombinator::remove($returnType, new ConstantBooleanType(false))
			: new ConstantBooleanType(false);
	}

}
