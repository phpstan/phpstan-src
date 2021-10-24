<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

final class HashHmacFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const HMAC_ALGORITHMS = [
		'md2',
		'md4',
		'md5',
		'sha1',
		'sha224',
		'sha256',
		'sha384',
		'sha512/224',
		'sha512/256',
		'sha512',
		'sha3-224',
		'sha3-256',
		'sha3-384',
		'sha3-512',
		'ripemd128',
		'ripemd160',
		'ripemd256',
		'ripemd320',
		'whirlpool',
		'tiger128,3',
		'tiger160,3',
		'tiger192,3',
		'tiger128,4',
		'tiger160,4',
		'tiger192,4',
		'snefru',
		'snefru256',
		'gost',
		'gost-crypto',
		'haval128,3',
		'haval160,3',
		'haval192,3',
		'haval224,3',
		'haval256,3',
		'haval128,4',
		'haval160,4',
		'haval192,4',
		'haval224,4',
		'haval256,4',
		'haval128,5',
		'haval160,5',
		'haval192,5',
		'haval224,5',
		'haval256,5',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['hash_hmac', 'hash_hmac_file'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if ($functionReflection->getName() === 'hash_hmac') {
			$defaultReturnType = new StringType();
		} else {
			$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		if (!isset($functionCall->getArgs()[0])) {
			return $defaultReturnType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$values = TypeUtils::getConstantStrings($argType);
		if (count($values) !== 1) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$string = $values[0];

		return in_array($string->getValue(), self::HMAC_ALGORITHMS, true) ? $defaultReturnType : new ConstantBooleanType(false);
	}

}
