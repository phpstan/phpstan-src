<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType;
use PHPStan\Type\UnionType;

class NativeFunctionReflectionProvider
{

	/** @var NativeFunctionReflection[] */
	private static array $functionMap = [];

	private \PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider;

	public function __construct(SignatureMapProvider $signatureMapProvider)
	{
		$this->signatureMapProvider = $signatureMapProvider;
	}

	public function findFunctionReflection(string $functionName): ?NativeFunctionReflection
	{
		$lowerCasedFunctionName = strtolower($functionName);
		if (isset(self::$functionMap[$lowerCasedFunctionName])) {
			return self::$functionMap[$lowerCasedFunctionName];
		}

		if (!$this->signatureMapProvider->hasFunctionSignature($lowerCasedFunctionName)) {
			return null;
		}

		$variants = [];
		$i = 0;
		while ($this->signatureMapProvider->hasFunctionSignature($lowerCasedFunctionName, $i)) {
			$functionSignature = $this->signatureMapProvider->getFunctionSignature($lowerCasedFunctionName, null, $i);
			$returnType = $functionSignature->getReturnType();
			$variants[] = new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				null,
				array_map(static function (ParameterSignature $parameterSignature) use ($lowerCasedFunctionName): NativeParameterReflection {
					$type = $parameterSignature->getType();
					if (
						$parameterSignature->getName() === 'values'
						&& (
							$lowerCasedFunctionName === 'printf'
							|| $lowerCasedFunctionName === 'sprintf'
						)
					) {
						$type = new UnionType([
							new StringAlwaysAcceptingObjectWithToStringType(),
							new IntegerType(),
							new FloatType(),
							new NullType(),
							new BooleanType(),
						]);
					}
					return new NativeParameterReflection(
						$parameterSignature->getName(),
						$parameterSignature->isOptional(),
						$type,
						$parameterSignature->passedByReference(),
						$parameterSignature->isVariadic(),
						null
					);
				}, $functionSignature->getParameters()),
				$functionSignature->isVariadic(),
				$returnType
			);

			$i++;
		}

		if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
			$hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName)['hasSideEffects']);
		} else {
			$hasSideEffects = TrinaryLogic::createMaybe();
		}
		$functionReflection = new NativeFunctionReflection(
			$lowerCasedFunctionName,
			$variants,
			null,
			$hasSideEffects
		);
		self::$functionMap[$lowerCasedFunctionName] = $functionReflection;

		return $functionReflection;
	}

}
