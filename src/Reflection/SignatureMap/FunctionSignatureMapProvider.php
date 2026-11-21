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
use function array_slice;
use function array_values;
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

	/** @var array<string, array<string, list<int>>> */
	private static array $parametersChangedByDelta = [];

	/** @var array<string, array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}>|null */
	private static ?array $functionMetadata = null;

	/** @var array<string, array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}|false> */
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
				TypehintHelper::decideTypeFromReflection($nativeParameters[$i]->getType()),
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
			$nativeReturnType = TypehintHelper::decideTypeFromReflection($reflectionFunction->getReturnType());
		}

		return new FunctionSignature(
			$parameters,
			$signature->getReturnType(),
			$nativeReturnType,
			$signature->isVariadic(),
		);
	}

	public function isFunctionDeprecated(string $functionName): bool
	{
		return false;
	}

	public function isMethodDeprecated(string $className, string $methodName): bool
	{
		return false;
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
	 * @return array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}|null
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

			/** @var array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}|null $row */
			self::$arenaMetadataRows[$lowerName] = $row ?? false;

			return $row;
		}

		return self::getFunctionMetadataMap()[$lowerName] ?? null;
	}

	/**
	 * @return array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}
	 */
	public function getMethodMetadata(string $className, string $methodName): array
	{
		return $this->getFunctionMetadata(sprintf('%s::%s', $className, $methodName));
	}

	/**
	 * @return array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}
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
	 * @return array<string, array{effects: list<string>, hasSideEffects?: bool, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}>
	 */
	private static function getFunctionMetadataMap(): array
	{
		if (self::$functionMetadata === null) {
			/** @var array<string, array{effects: list<string>, hasSideEffects?: false, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}> $metadata */
			$metadata = require __DIR__ . '/../../../resources/functionMetadata.php';
			foreach ($metadata as $name => $entry) {
				if (
					isset($entry['hasSideEffects'])
					|| isset($entry['pureUnlessCallableIsImpureParameters'])
					|| isset($entry['pureUnlessParameterPassedParameters'])
				) {
					continue;
				}

				$metadata[$name]['hasSideEffects'] = $entry['effects'] !== [];
			}
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

		foreach ($this->getSignatureMapDeltaFiles() as $file) {
			$signatureMap = $this->computeSignatureMap($signatureMap, $this->loadSignatureMapDelta($file));
		}

		self::$signatureMaps[$cacheKey] = $signatureMap;
		ArenaCache::publishHash('sigmap-' . $cacheKey, $signatureMap);

		return $signatureMap;
	}

	/**
	 * Positions of the parameters whose type the last delta replacing the signature changes.
	 *
	 * Such a type describes exactly what the parameter accepts, so it may leave out members
	 * of the native union type, like objects that PHP still accepts but deprecates.
	 * Other function map types leave them in, because the native type can be newer than them.
	 *
	 * @return list<int>
	 */
	public function getParametersChangedByDelta(string $name): array
	{
		$cacheKey = sprintf('%d-%d', $this->phpVersion->getVersionId(), $this->stricterFunctionMap ? 1 : 0);
		if (!array_key_exists($cacheKey, self::$parametersChangedByDelta)) {
			$changedParameters = [];
			foreach ($this->getSignatureMapDeltaFiles() as $file) {
				$delta = $this->loadSignatureMapDelta($file);
				foreach (array_keys($delta['old'] + $delta['new']) as $key) {
					unset($changedParameters[strtolower($key)]);
				}
				foreach ($delta['new'] as $key => $signature) {
					if (!array_key_exists($key, $delta['old'])) {
						continue;
					}

					$oldParameterTypes = array_values(array_slice($delta['old'][$key], 1));
					$positions = [];
					foreach (array_values(array_slice($signature, 1)) as $i => $parameterType) {
						if (($oldParameterTypes[$i] ?? null) === $parameterType) {
							continue;
						}

						$positions[] = $i;
					}

					$changedParameters[strtolower($key)] = $positions;
				}
			}

			self::$parametersChangedByDelta[$cacheKey] = $changedParameters;
		}

		return self::$parametersChangedByDelta[$cacheKey][strtolower($name)] ?? [];
	}

	/**
	 * @return list<string>
	 */
	private function getSignatureMapDeltaFiles(): array
	{
		$directory = __DIR__ . '/../../../resources';
		$versionId = $this->phpVersion->getVersionId();
		$files = [];
		if ($this->stricterFunctionMap) {
			$files[] = $directory . '/functionMap_bleedingEdge.php';
		}
		if ($versionId >= 70400) {
			$files[] = $directory . '/functionMap_php74delta.php';
		}
		if ($versionId >= 80000) {
			$files[] = $directory . '/functionMap_php80delta.php';
			if ($this->stricterFunctionMap) {
				$files[] = $directory . '/functionMap_php80delta_bleedingEdge.php';
			}
		}
		if ($versionId >= 80100) {
			$files[] = $directory . '/functionMap_php81delta.php';
		}
		if ($versionId >= 80200) {
			$files[] = $directory . '/functionMap_php82delta.php';
		}
		if ($versionId >= 80300) {
			$files[] = $directory . '/functionMap_php83delta.php';
		}
		if ($versionId >= 80400) {
			$files[] = $directory . '/functionMap_php84delta.php';
		}
		if ($versionId >= 80500) {
			$files[] = $directory . '/functionMap_php85delta.php';
		}
		if ($versionId >= 80600) {
			$files[] = $directory . '/functionMap_php86delta.php';
		}

		return $files;
	}

	/**
	 * @return array{new: array<string, mixed[]>, old: array<string, mixed[]>}
	 */
	private function loadSignatureMapDelta(string $file): array
	{
		$signatureMapDelta = include $file;
		if (!is_array($signatureMapDelta)) {
			throw new ShouldNotHappenException(sprintf('Signature map file "%s" could not be loaded.', $file));
		}

		/** @var array{new: array<string, mixed[]>, old: array<string, mixed[]>} $signatureMapDelta */
		return $signatureMapDelta;
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
