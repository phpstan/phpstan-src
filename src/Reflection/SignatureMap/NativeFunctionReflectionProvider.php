<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;

class NativeFunctionReflectionProvider
{

	/** @var NativeFunctionReflection[] */
	private static array $functionMap = [];

	private \PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider;

	private \PHPStan\BetterReflection\Reflector\FunctionReflector $functionReflector;

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	public function __construct(SignatureMapProvider $signatureMapProvider, FunctionReflector $functionReflector, FileTypeMapper $fileTypeMapper)
	{
		$this->signatureMapProvider = $signatureMapProvider;
		$this->functionReflector = $functionReflector;
		$this->fileTypeMapper = $fileTypeMapper;
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

					if (
						$parameterSignature->getName() === 'fields'
						&& $lowerCasedFunctionName === 'fputcsv'
					) {
						$type = new ArrayType(
							new UnionType([
								new StringType(),
								new IntegerType(),
							]),
							new UnionType([
								new StringAlwaysAcceptingObjectWithToStringType(),
								new IntegerType(),
								new FloatType(),
								new NullType(),
								new BooleanType(),
							])
						);
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

		$throwType = null;
		try {
			$reflectionFunction = $this->functionReflector->reflect($functionName);
			if ($reflectionFunction->getFileName() !== null) {
				$fileName = $reflectionFunction->getFileName();
				$docComment = $reflectionFunction->getDocComment();
				$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
				$throwsTag = $resolvedPhpDoc->getThrowsTag();
				if ($throwsTag !== null) {
					$throwType = $throwsTag->getType();
				}
			}
		} catch (\PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound $e) {
			// pass
		} catch (InvalidIdentifierName $e) {
			// pass
		}

		$functionReflection = new NativeFunctionReflection(
			$lowerCasedFunctionName,
			$variants,
			$throwType,
			$hasSideEffects
		);
		self::$functionMap[$lowerCasedFunctionName] = $functionReflection;

		return $functionReflection;
	}

}
