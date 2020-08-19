<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Php\PhpVersion;

class SignatureMapProvider
{

	private \PHPStan\Reflection\SignatureMap\SignatureMapParser $parser;

	private PhpVersion $phpVersion;

	/** @var mixed[]|null */
	private ?array $signatureMap = null;

	/** @var array<string, array{hasSideEffects: bool}>|null */
	private ?array $functionMetadata = null;

	public function __construct(SignatureMapParser $parser, PhpVersion $phpVersion)
	{
		$this->parser = $parser;
		$this->phpVersion = $phpVersion;
	}

	public function hasFunctionSignature(string $name): bool
	{
		$signatureMap = $this->getSignatureMap();
		return array_key_exists(strtolower($name), $signatureMap);
	}

	public function getFunctionSignature(string $functionName, ?string $className): FunctionSignature
	{
		$functionName = strtolower($functionName);

		if (!$this->hasFunctionSignature($functionName)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$signatureMap = self::getSignatureMap();

		return $this->parser->getFunctionSignature(
			$signatureMap[$functionName],
			$className
		);
	}

	public function hasFunctionMetadata(string $name): bool
	{
		$signatureMap = $this->getFunctionMetadataMap();
		return array_key_exists(strtolower($name), $signatureMap);
	}

	/**
	 * @param string $functionName
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array
	{
		$functionName = strtolower($functionName);

		if (!$this->hasFunctionMetadata($functionName)) {
			throw new \PHPStan\ShouldNotHappenException();
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
	private function getSignatureMap(): array
	{
		if ($this->signatureMap === null) {
			$signatureMap = require __DIR__ . '/../../../resources/functionMap.php';
			if (!is_array($signatureMap)) {
				throw new \PHPStan\ShouldNotHappenException('Signature map could not be loaded.');
			}

			$signatureMap = array_change_key_case($signatureMap, CASE_LOWER);

			if ($this->phpVersion->getVersionId() >= 70400) {
				$php74MapDelta = require __DIR__ . '/../../../resources/functionMap_php74delta.php';
				if (!is_array($php74MapDelta)) {
					throw new \PHPStan\ShouldNotHappenException('Signature map could not be loaded.');
				}

				$signatureMap = $this->computeSignatureMap($signatureMap, $php74MapDelta);
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
		foreach ($delta['old'] as $key) {
			unset($signatureMap[strtolower($key)]);
		}
		foreach ($delta['new'] as $key => $signature) {
			$signatureMap[strtolower($key)] = $signature;
		}

		return $signatureMap;
	}

}
