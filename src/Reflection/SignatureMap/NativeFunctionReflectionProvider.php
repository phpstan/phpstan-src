<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
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
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\UnionType;
use function array_map;
use function strtolower;

class NativeFunctionReflectionProvider
{

	/** @var NativeFunctionReflection[] */
	private static array $functionMap = [];

	public function __construct(private SignatureMapProvider $signatureMapProvider, private Reflector $reflector, private FileTypeMapper $fileTypeMapper, private StubPhpDocProvider $stubPhpDocProvider)
	{
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

		$throwType = null;
		$reflectionFunctionAdapter = null;
		$isDeprecated = false;
		try {
			$reflectionFunction = $this->reflector->reflectFunction($functionName);
			$reflectionFunctionAdapter = new ReflectionFunction($reflectionFunction);
			if ($reflectionFunction->getFileName() !== null) {
				$fileName = $reflectionFunction->getFileName();
				$docComment = $reflectionFunction->getDocComment();
				$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
				$throwsTag = $resolvedPhpDoc->getThrowsTag();
				if ($throwsTag !== null) {
					$throwType = $throwsTag->getType();
				}
				$isDeprecated = $reflectionFunction->isDeprecated();
			}
		} catch (IdentifierNotFound | InvalidIdentifierName) {
			// pass
		}

		$functionSignatures = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);

		$phpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($lowerCasedFunctionName, array_map(static fn (ParameterSignature $parameter): string => $parameter->getName(), $functionSignatures[0]->getParameters()));
		if ($phpDoc !== null && $phpDoc->getThrowsTag() !== null) {
			$throwType = $phpDoc->getThrowsTag()->getType();
		}

		$variants = [];
		$functionSignatures = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);
		foreach ($functionSignatures as $functionSignature) {
			$variants[] = new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				null,
				array_map(static function (ParameterSignature $parameterSignature) use ($lowerCasedFunctionName, $phpDoc): NativeParameterReflection {
					$type = $parameterSignature->getType();

					$phpDocType = null;
					if ($phpDoc !== null) {
						$phpDocParam = $phpDoc->getParamTags()[$parameterSignature->getName()] ?? null;
						if ($phpDocParam !== null) {
							$phpDocType = $phpDocParam->getType();
						}
					}
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
							]),
						);
					}

					return new NativeParameterReflection(
						$parameterSignature->getName(),
						$parameterSignature->isOptional(),
						TypehintHelper::decideType($type, $phpDocType),
						$parameterSignature->passedByReference(),
						$parameterSignature->isVariadic(),
						$parameterSignature->getDefaultValue(),
					);
				}, $functionSignature->getParameters()),
				$functionSignature->isVariadic(),
				TypehintHelper::decideType($functionSignature->getReturnType(), $phpDoc !== null ? $this->getReturnTypeFromPhpDoc($phpDoc) : null),
			);
		}

		if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
			$hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName)['hasSideEffects']);
		} else {
			$hasSideEffects = TrinaryLogic::createMaybe();
		}

		$functionReflection = new NativeFunctionReflection(
			$lowerCasedFunctionName,
			$variants,
			$throwType,
			$hasSideEffects,
			$isDeprecated,
		);
		self::$functionMap[$lowerCasedFunctionName] = $functionReflection;

		return $functionReflection;
	}

	private function getReturnTypeFromPhpDoc(ResolvedPhpDocBlock $phpDoc): ?Type
	{
		$returnTag = $phpDoc->getReturnTag();
		if ($returnTag === null) {
			return null;
		}

		return $returnTag->getType();
	}

}
