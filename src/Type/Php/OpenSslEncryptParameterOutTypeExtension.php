<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;
use function openssl_get_cipher_methods;
use function strtolower;
use function substr;

final class OpenSslEncryptParameterOutTypeExtension implements FunctionParameterOutTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'openssl_encrypt' && $parameter->getName() === 'tag';
	}

	public function getParameterOutTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		$cipherArg = $args[1] ?? null;

		if ($cipherArg === null) {
			return null;
		}

		$tagTypes = [];

		foreach ($scope->getType($cipherArg->value)->getConstantStrings() as $cipherType) {
			$cipher = strtolower($cipherType->getValue());
			$mode = substr($cipher, -3);

			if (!in_array($cipher, openssl_get_cipher_methods(), true)) {
				$tagTypes[] = new NullType();
				continue;
			}

			if (in_array($mode, ['gcm', 'ccm'], true)) {
				$tagTypes[] = TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				);

				continue;
			}

			$tagTypes[] = new NullType();
		}

		if ($tagTypes === []) {
			return TypeCombinator::addNull(TypeCombinator::intersect(
				new StringType(),
				new AccessoryNonEmptyStringType(),
			));
		}

		return TypeCombinator::union(...$tagTypes);
	}

}
