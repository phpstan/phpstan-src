<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Cache\ArenaCache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
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

#[AutowiredService(as: FunctionSignatureMapProvider::class)]
final class FunctionSignatureMapProvider implements SignatureMapProvider
{

	/** @var array<string, mixed[]> */
	private static array $signatureMaps = [];

	/**
	 * Rows already fetched from the shared arena (false = authoritatively
	 * absent there), so steady-state lookups cost one local array access —
	 * the same as reading the full map would.
	 *
	 * @var array<string, mixed[]|false>
	 */
	private static array $arenaSignatureRows = [];

	/** @var array<string, array{hasSideEffects: bool}>|null */
	private static ?array $functionMetadata = null;

	/** @var array<string, array{hasSideEffects: bool}|false> */
	private static array $arenaMetadataRows = [];

	public function __construct(
		private SignatureMapParser $parser,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private PhpVersion $phpVersion,
		#[AutowiredParameter(ref: '%featureToggles.stricterFunctionMap%')]
		private bool $stricterFunctionMap,
	)
	{
	}

	public function hasMethodSignature(string $className, string $methodName): bool
	{
		return $this->hasFunctionSignature(sprintf('%s::%s', $className, $methodName));
	}

	public function hasFunctionSignature(string $name): bool
	{
		return $this->getSignatureMapEntry(strtolower($name)) !== null;
	}

	/**
	 * A row of the merged signature map, preferring the run's shared arena:
	 * the first process to need the map builds and publishes it, workers
	 * after that materialize only the rows they touch instead of the whole
	 * multi-megabyte map.
	 *
	 * @return mixed[]|null
	 */
	private function getSignatureMapEntry(string $lowerName): ?array
	{
		$cacheKey = sprintf('%d-%d', $this->phpVersion->getVersionId(), $this->stricterFunctionMap ? 1 : 0);
		if (array_key_exists($cacheKey, self::$signatureMaps)) {
			return self::$signatureMaps[$cacheKey][$lowerName] ?? null;
		}

		$memoKey = $cacheKey . '/' . $lowerName;
		if (array_key_exists($memoKey, self::$arenaSignatureRows)) {
			$row = self::$arenaSignatureRows[$memoKey];
			return $row === false ? null : $row;
		}

		$recordKey = 'sigmap-' . $cacheKey;
		if (ArenaCache::hasRecord($recordKey)) {
			$row = ArenaCache::lookupHash($recordKey, $lowerName);
			if (!is_array($row)) {
				$row = null;
			}
			self::$arenaSignatureRows[$memoKey] = $row ?? false;

			return $row;
		}

		return $this->getSignatureMap()[$lowerName] ?? null;
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

		return ['positional' => $signatures, 'named' => null];
	}

	private function createSignature(string $functionName, ?string $className, ?ReflectionFunctionAbstract $reflectionFunction): FunctionSignature
	{
		if (!$reflectionFunction instanceof ReflectionMethod && !$reflectionFunction instanceof ReflectionFunction && $reflectionFunction !== null) {
			throw new ShouldNotHappenException();
		}
		$signatureRow = $this->getSignatureMapEntry($functionName);
		if ($signatureRow === null) {
			throw new ShouldNotHappenException(sprintf('Function %s is not in the signature map.', $functionName));
		}
		$signature = $this->parser->getFunctionSignature(
			$signatureRow,
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
				TypehintHelper::decideTypeFromReflection($nativeParameters[$i]->getType(), isBuiltin: true),
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$nativeParameters[$i]->isDefaultValueAvailable() ? $this->initializerExprTypeResolver->getType(
					$nativeParameters[$i]->getDefaultValueExpression(),
					InitializerExprContext::fromReflectionParameter($nativeParameters[$i]),
				) : null,
				$parameter->getOutType(),
			);
		}

		if ($reflectionFunction === null) {
			$nativeReturnType = new MixedType();
		} else {
			$nativeReturnType = TypehintHelper::decideTypeFromReflection($reflectionFunction->getReturnType(), isBuiltin: true);
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
		return $this->getFunctionMetadataEntry(strtolower($name)) !== null;
	}

	/**
	 * @return array{hasSideEffects: bool}|null
	 */
	private function getFunctionMetadataEntry(string $lowerName): ?array
	{
		if (self::$functionMetadata !== null) {
			return self::$functionMetadata[$lowerName] ?? null;
		}

		if (array_key_exists($lowerName, self::$arenaMetadataRows)) {
			$row = self::$arenaMetadataRows[$lowerName];
			return $row === false ? null : $row;
		}

		if (ArenaCache::hasRecord('funcmeta')) {
			$row = ArenaCache::lookupHash('funcmeta', $lowerName);
			if (!is_array($row)) {
				$row = null;
			}

			/** @var array{hasSideEffects: bool}|null $row */
			self::$arenaMetadataRows[$lowerName] = $row ?? false;

			return $row;
		}

		return self::getFunctionMetadataMap()[$lowerName] ?? null;
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
		$entry = $this->getFunctionMetadataEntry(strtolower($functionName));
		if ($entry === null) {
			throw new ShouldNotHappenException();
		}

		return $entry;
	}

	/**
	 * @return array<string, array{hasSideEffects: bool}>
	 */
	private static function getFunctionMetadataMap(): array
	{
		if (self::$functionMetadata === null) {
			/** @var array<string, array{hasSideEffects: bool}> $metadata */
			$metadata = require __DIR__ . '/../../../resources/functionMetadata.php';
			self::$functionMetadata = array_change_key_case($metadata, CASE_LOWER);
			ArenaCache::publishHash('funcmeta', self::$functionMetadata);
		}

		return self::$functionMetadata;
	}

	/**
	 * @return mixed[]
	 */
	public function getSignatureMap(): array
	{
		$cacheKey = sprintf('%d-%d', $this->phpVersion->getVersionId(), $this->stricterFunctionMap ? 1 : 0);
		if (array_key_exists($cacheKey, self::$signatureMaps)) {
			return self::$signatureMaps[$cacheKey];
		}

		$signatureMap = require __DIR__ . '/../../../resources/functionMap.php';
		if (!is_array($signatureMap)) {
			throw new ShouldNotHappenException('Signature map could not be loaded.');
		}

		$signatureMap = array_change_key_case($signatureMap, CASE_LOWER);

		if ($this->stricterFunctionMap) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_bleedingEdge.php');
		}

		if ($this->phpVersion->getVersionId() >= 70400) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php74delta.php');
		}

		if ($this->phpVersion->getVersionId() >= 80000) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php80delta.php');

			if ($this->stricterFunctionMap) {
				$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php80delta_bleedingEdge.php');
			}
		}

		if ($this->phpVersion->getVersionId() >= 80100) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php81delta.php');
		}

		if ($this->phpVersion->getVersionId() >= 80200) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php82delta.php');
		}

		if ($this->phpVersion->getVersionId() >= 80300) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php83delta.php');
		}

		if ($this->phpVersion->getVersionId() >= 80400) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php84delta.php');
		}

		if ($this->phpVersion->getVersionId() >= 80500) {
			$signatureMap = $this->computeSignatureMapFile($signatureMap, __DIR__ . '/../../../resources/functionMap_php85delta.php');
		}

		self::$signatureMaps[$cacheKey] = $signatureMap;
		ArenaCache::publishHash('sigmap-' . $cacheKey, $signatureMap);

		return $signatureMap;
	}

	/**
	 * @param array<string, mixed> $signatureMap
	 * @return array<string, mixed>
	 */
	private function computeSignatureMapFile(array $signatureMap, string $file): array
	{
		$signatureMapDelta = include $file;
		if (!is_array($signatureMapDelta)) {
			throw new ShouldNotHappenException(sprintf('Signature map file "%s" could not be loaded.', $file));
		}

		return $this->computeSignatureMap($signatureMap, $signatureMapDelta);
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

	public function hasClassConstantMetadata(string $className, string $constantName): bool
	{
		return false;
	}

	public function getClassConstantMetadata(string $className, string $constantName): array
	{
		throw new ShouldNotHappenException();
	}

}
