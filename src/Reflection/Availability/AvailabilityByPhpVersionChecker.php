<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Availability;

use PHPStan\Php\PhpVersion;

class AvailabilityByPhpVersionChecker
{

	private PhpVersion $phpVersion;

	/** @var array<string, array{int|null, int|null}>|null */
	private ?array $functionMap = null;

	/** @var array<string, array{int|null, int|null}>|null */
	private ?array $classMap = null;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionAvailable(string $functionName): ?bool
	{
		$map = $this->getFunctionMap();
		$lowerFunctionName = strtolower($functionName);
		if (!array_key_exists($lowerFunctionName, $map)) {
			return null;
		}

		return $this->resolveByVersions($map[$lowerFunctionName]);
	}

	public function isClassAvailable(string $className): ?bool
	{
		$map = $this->getClassMap();
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $map)) {
			return null;
		}

		return $this->resolveByVersions($map[$lowerClassName]);
	}

	/**
	 * @param array{int|null, int|null} $versions
	 * @return bool
	 */
	private function resolveByVersions(array $versions): bool
	{
		$min = $versions[0];
		$max = $versions[1];

		if ($min === null) {
			if ($max === null) {
				return false;
			}

			return $this->phpVersion->getVersionId() < $max;
		}

		if ($max === null) {
			return $this->phpVersion->getVersionId() >= $min;
		}

		return $this->phpVersion->getVersionId() < $max && $this->phpVersion->getVersionId() >= $min;
	}

	/**
	 * @return array<string, array{int|null, int|null}>
	 */
	private function getFunctionMap(): array
	{
		if ($this->functionMap !== null) {
			return $this->functionMap;
		}

		$this->functionMap = require __DIR__ . '/../../../resources/functionPhpVersions.php';

		return $this->functionMap;
	}

	/**
	 * @return array<string, array{int|null, int|null}>
	 */
	private function getClassMap(): array
	{
		if ($this->classMap !== null) {
			return $this->classMap;
		}

		$this->classMap = require __DIR__ . '/../../../resources/classPhpVersions.php';

		return $this->classMap;
	}

}
