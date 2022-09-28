<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypehintHelper;
use ReflectionFunctionAbstract;
use function array_change_key_case;
use function array_key_exists;
use function array_keys;
use function is_array;
use function sprintf;
use function strtolower;
use const CASE_LOWER;

class FunctionSignatureMapProvider implements SignatureMapProvider
{

	/** @var mixed[]|null */
	private ?array $signatureMap = null;

	/** @var array<string, array{hasSideEffects: bool}>|null */
	private ?array $functionMetadata = null;

	public function __construct(
		private SignatureMapParser $parser,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function hasMethodSignature(string $className, string $methodName): bool
	{
		return $this->hasFunctionSignature(sprintf('%s::%s', $className, $methodName));
	}

	public function hasFunctionSignature(string $name): bool
	{
		return array_key_exists(strtolower($name), $this->getSignatureMap());
	}

	public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array
	{
		return $this->getFunctionSignatures(sprintf('%s::%s', $className, $methodName), $className, $reflectionMethod);
	}

	public function getFunctionSignatures(string $functionName, ?string $className, ?ReflectionFunctionAbstract $reflectionFunction): array
	{
		$functionName = strtolower($functionName);

		$signatures = [$this->createSignature($functionName, $className, $reflectionFunction)];
		$i = 1;
		$variantFunctionName = $functionName . '\'' . $i;
		while ($this->hasFunctionSignature($variantFunctionName)) {
			$signatures[] = $this->createSignature($variantFunctionName, $className, $reflectionFunction);
			$i++;
			$variantFunctionName = $functionName . '\'' . $i;
		}

		return $signatures;
	}

	private function createSignature(string $functionName, ?string $className, ?ReflectionFunctionAbstract $reflectionFunction): FunctionSignature
	{
		if (!$reflectionFunction instanceof ReflectionMethod && !$reflectionFunction instanceof ReflectionFunction && $reflectionFunction !== null) {
			throw new ShouldNotHappenException();
		}
		$signatureMap = self::getSignatureMap();
		$signature = $this->parser->getFunctionSignature(
			$signatureMap[$functionName],
			$className,
		);
		$parameters = [];
		foreach ($signature->getParameters() as $i => $parameter) {
			if ($reflectionFunction === null) {
				$parameters[] = $parameter;
				continue;
			}
			$nativeParameters = $reflectionFunction->getParameters();
			if (!array_key_exists($i, $nativeParameters)) {
				$parameters[] = $parameter;
				continue;
			}

			$parameters[] = new ParameterSignature(
				$parameter->getName(),
				$parameter->isOptional(),
				$parameter->getType(),
				TypehintHelper::decideTypeFromReflection($nativeParameters[$i]->getType()),
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$nativeParameters[$i]->isDefaultValueAvailable() ? $this->initializerExprTypeResolver->getType(
					$nativeParameters[$i]->getDefaultValueExpression(),
					InitializerExprContext::fromReflectionParameter($nativeParameters[$i]),
				) : null,
			);
		}

		if ($reflectionFunction === null) {
			$nativeReturnType = new MixedType();
		} else {
			$nativeReturnType = TypehintHelper::decideTypeFromReflection($reflectionFunction->getReturnType());
		}

		return new FunctionSignature(
			$parameters,
			$signature->getReturnType(),
			$nativeReturnType,
			$signature->isVariadic(),
		);
	}

	public function hasMethodMetadata(string $className, string $methodName): bool
	{
		return $this->hasFunctionMetadata(sprintf('%s::%s', $className, $methodName));
	}

	public function hasFunctionMetadata(string $name): bool
	{
		$signatureMap = $this->getFunctionMetadataMap();
		return array_key_exists(strtolower($name), $signatureMap);
	}

	/**
	 * @return array{hasSideEffects: bool}
	 */
	public function getMethodMetadata(string $className, string $methodName): array
	{
		return $this->getFunctionMetadata(sprintf('%s::%s', $className, $methodName));
	}

	/**
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array
	{
		$functionName = strtolower($functionName);

		if (!$this->hasFunctionMetadata($functionName)) {
			throw new ShouldNotHappenException();
		}

		return $this->getFunctionMetadataMap()[$functionName];
	}

	/**
	 * @return array<string, array{hasSideEffects: bool}>
	 */
	private function getFunctionMetadataMap(): array
	{
		if ($this->functionMetadata === null) {
			/** @var array<string, array{hasSideEffects: bool}> $metadata */
			$metadata = require __DIR__ . '/../../../resources/functionMetadata.php';
			$this->functionMetadata = array_change_key_case($metadata, CASE_LOWER);
		}

		return $this->functionMetadata;
	}

	/**
	 * @return mixed[]
	 */
	public function getSignatureMap(): array
	{
		if ($this->signatureMap === null) {
			$signatureMap = require __DIR__ . '/../../../resources/functionMap.php';
			if (!is_array($signatureMap)) {
				throw new ShouldNotHappenException('Signature map could not be loaded.');
			}

			$signatureMap = array_change_key_case($signatureMap, CASE_LOWER);

			if ($this->phpVersion->getVersionId() >= 70400) {
				$php74MapDelta = require __DIR__ . '/../../../resources/functionMap_php74delta.php';
				if (!is_array($php74MapDelta)) {
					throw new ShouldNotHappenException('Signature map could not be loaded.');
				}

				$signatureMap = $this->computeSignatureMap($signatureMap, $php74MapDelta);
			}

			if ($this->phpVersion->getVersionId() >= 80000) {
				$php80MapDelta = require __DIR__ . '/../../../resources/functionMap_php80delta.php';
				if (!is_array($php80MapDelta)) {
					throw new ShouldNotHappenException('Signature map could not be loaded.');
				}

				$signatureMap = $this->computeSignatureMap($signatureMap, $php80MapDelta);
			}

			if ($this->phpVersion->getVersionId() >= 80100) {
				$php81MapDelta = require __DIR__ . '/../../../resources/functionMap_php81delta.php';
				if (!is_array($php81MapDelta)) {
					throw new ShouldNotHappenException('Signature map could not be loaded.');
				}

				$signatureMap = $this->computeSignatureMap($signatureMap, $php81MapDelta);
			}

			if ($this->phpVersion->getVersionId() >= 80200) {
				$php82MapDelta = require __DIR__ . '/../../../resources/functionMap_php82delta.php';
				if (!is_array($php82MapDelta)) {
					throw new ShouldNotHappenException('Signature map could not be loaded.');
				}

				$signatureMap = $this->computeSignatureMap($signatureMap, $php82MapDelta);
			}

			$this->signatureMap = $signatureMap;
		}

		return $this->signatureMap;
	}

	/**
	 * @param array<string, mixed> $signatureMap
	 * @param array<string, array<string, mixed>> $delta
	 * @return array<string, mixed>
	 */
	private function computeSignatureMap(array $signatureMap, array $delta): array
	{
		foreach (array_keys($delta['old']) as $key) {
			unset($signatureMap[strtolower($key)]);
		}
		foreach ($delta['new'] as $key => $signature) {
			$signatureMap[strtolower($key)] = $signature;
		}

		return $signatureMap;
	}

}
