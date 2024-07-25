<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\Native\NativeParameterWithPhpDocsReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function strtolower;

final class NativeFunctionReflectionProvider
{

	/** @var NativeFunctionReflection[] */
	private array $functionMap = [];

	public function __construct(private SignatureMapProvider $signatureMapProvider, private Reflector $reflector, private FileTypeMapper $fileTypeMapper, private StubPhpDocProvider $stubPhpDocProvider)
	{
	}

	public function findFunctionReflection(string $functionName): ?NativeFunctionReflection
	{
		$lowerCasedFunctionName = strtolower($functionName);
		$realFunctionName = $lowerCasedFunctionName;
		if (isset($this->functionMap[$lowerCasedFunctionName])) {
			return $this->functionMap[$lowerCasedFunctionName];
		}

		if (!$this->signatureMapProvider->hasFunctionSignature($lowerCasedFunctionName)) {
			return null;
		}

		$throwType = null;
		$reflectionFunctionAdapter = null;
		$isDeprecated = false;
		$phpDocReturnType = null;
		$asserts = Assertions::createEmpty();
		$docComment = null;
		$returnsByReference = TrinaryLogic::createMaybe();
		try {
			$reflectionFunction = $this->reflector->reflectFunction($functionName);
			$reflectionFunctionAdapter = new ReflectionFunction($reflectionFunction);
			$returnsByReference = TrinaryLogic::createFromBoolean($reflectionFunctionAdapter->returnsReference());
			$realFunctionName = $reflectionFunction->getName();
			if ($reflectionFunction->getFileName() !== null) {
				$fileName = $reflectionFunction->getFileName();
				$docComment = $reflectionFunction->getDocComment();
				if ($docComment !== null) {
					$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
					$throwsTag = $resolvedPhpDoc->getThrowsTag();
					if ($throwsTag !== null) {
						$throwType = $throwsTag->getType();
					}
					$isDeprecated = $reflectionFunction->isDeprecated();
				}
			}
		} catch (IdentifierNotFound | InvalidIdentifierName) {
			// pass
		}

		$functionSignaturesResult = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);

		$phpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($lowerCasedFunctionName, array_map(static fn (ParameterSignature $parameter): string => $parameter->getName(), $functionSignaturesResult['positional'][0]->getParameters()));
		if ($phpDoc !== null) {
			if ($phpDoc->hasPhpDocString()) {
				$docComment = $phpDoc->getPhpDocString();
			}
			if ($phpDoc->getThrowsTag() !== null) {
				$throwType = $phpDoc->getThrowsTag()->getType();
			}
			$asserts = Assertions::createFromResolvedPhpDocBlock($phpDoc);
			$phpDocReturnType = $this->getReturnTypeFromPhpDoc($phpDoc);
		}

		$variantsByType = ['positional' => []];
		foreach ($functionSignaturesResult as $signatureType => $functionSignatures) {
			foreach ($functionSignatures ?? [] as $functionSignature) {
				$variantsByType[$signatureType][] = new FunctionVariantWithPhpDocs(
					TemplateTypeMap::createEmpty(),
					null,
					array_map(static function (ParameterSignature $parameterSignature) use ($phpDoc): NativeParameterWithPhpDocsReflection {
						$type = $parameterSignature->getType();

						$phpDocType = null;
						$immediatelyInvokedCallable = TrinaryLogic::createMaybe();
						$closureThisType = null;
						if ($phpDoc !== null) {
							if (array_key_exists($parameterSignature->getName(), $phpDoc->getParamTags())) {
								$phpDocType = $phpDoc->getParamTags()[$parameterSignature->getName()]->getType();
							}
							if (array_key_exists($parameterSignature->getName(), $phpDoc->getParamsImmediatelyInvokedCallable())) {
								$immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($phpDoc->getParamsImmediatelyInvokedCallable()[$parameterSignature->getName()]);
							}
							if (array_key_exists($parameterSignature->getName(), $phpDoc->getParamClosureThisTags())) {
								$closureThisType = $phpDoc->getParamClosureThisTags()[$parameterSignature->getName()]->getType();
							}
						}

						return new NativeParameterWithPhpDocsReflection(
							$parameterSignature->getName(),
							$parameterSignature->isOptional(),
							TypehintHelper::decideType($type, $phpDocType),
							$phpDocType ?? new MixedType(),
							$type,
							$parameterSignature->passedByReference(),
							$parameterSignature->isVariadic(),
							$parameterSignature->getDefaultValue(),
							$phpDoc !== null ? NativeFunctionReflectionProvider::getParamOutTypeFromPhpDoc($parameterSignature->getName(), $phpDoc) : null,
							$immediatelyInvokedCallable,
							$closureThisType,
						);
					}, $functionSignature->getParameters()),
					$functionSignature->isVariadic(),
					TypehintHelper::decideType($functionSignature->getReturnType(), $phpDocReturnType),
					$phpDocReturnType ?? new MixedType(),
					$functionSignature->getReturnType(),
				);
			}
		}

		if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
			$hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName)['hasSideEffects']);
		} else {
			$hasSideEffects = TrinaryLogic::createMaybe();
		}

		$functionReflection = new NativeFunctionReflection(
			$realFunctionName,
			$variantsByType['positional'],
			$variantsByType['named'] ?? null,
			$throwType,
			$hasSideEffects,
			$isDeprecated,
			$asserts,
			$docComment,
			$returnsByReference,
		);
		$this->functionMap[$lowerCasedFunctionName] = $functionReflection;

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

	private static function getParamOutTypeFromPhpDoc(string $paramName, ResolvedPhpDocBlock $stubPhpDoc): ?Type
	{
		$paramOutTags = $stubPhpDoc->getParamOutTags();

		if (array_key_exists($paramName, $paramOutTags)) {
			return $paramOutTags[$paramName]->getType();
		}

		return null;
	}

}
