<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use Composer\Semver\VersionParser;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\ComposerHelper;
use UnexpectedValueException;
use function array_key_exists;
use function count;
use function end;
use function implode;
use function is_string;
use function ksort;
use function phpversion;
use function preg_match;
use function sprintf;
use function str_contains;

#[AutowiredService]
final class ExtensionVersionProvider
{

	/** @var array<string, int>|null */
	private ?array $extensionVersions = null;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
	)
	{
	}

	/** @return array<string, int> */
	public function getExtensionVersions(): array
	{
		if ($this->extensionVersions !== null) {
			return $this->extensionVersions;
		}

		$composerConfig = null;
		if (count($this->composerAutoloaderProjectPaths) > 0) {
			$composerConfig = ComposerHelper::getComposerConfig(end($this->composerAutoloaderProjectPaths));
		}

		$extensionVersions = [];
		foreach (PhpStormStubsMap::EXTENSION_VERSIONS as $extensionName => $versionMaps) {
			[$version, $useRuntimeVersion] = $this->getComposerExtensionVersion($composerConfig, $extensionName, $versionMaps);
			if ($version === null && $useRuntimeVersion) {
				$version = $this->getRuntimeExtensionVersion($extensionName, $versionMaps);
			}
			if ($version === null) {
				continue;
			}

			$extensionVersions[$extensionName] = $version;
		}
		ksort($extensionVersions);

		return $this->extensionVersions = $extensionVersions;
	}

	public function getCacheKey(): string
	{
		$parts = [];
		foreach ($this->getExtensionVersions() as $extensionName => $version) {
			$parts[] = sprintf('%s:%d', $extensionName, $version);
		}

		return implode(',', $parts);
	}

	/**
	 * @param array<string, mixed>|null $composerConfig
	 * @param array<int|string, array{classes: array<string, string>, functions: array<string, string>, constants: array<string, string>}> $versionMaps
	 * @return array{int|null, bool}
	 */
	private function getComposerExtensionVersion(?array $composerConfig, string $extensionName, array $versionMaps): array
	{
		if ($composerConfig === null) {
			return [null, true];
		}

		$extensionPackageName = 'ext-' . $extensionName;
		$platformVersion = $composerConfig['config']['platform'][$extensionPackageName] ?? null;
		if (is_string($platformVersion)) {
			return [$this->getKnownVersion($platformVersion, $versionMaps), false];
		}

		$requiredVersion = $composerConfig['require'][$extensionPackageName] ?? null;
		if (!is_string($requiredVersion)) {
			return [null, true];
		}

		$majorVersion = $this->getConstraintMajor($requiredVersion);
		if ($majorVersion === null) {
			return [null, true];
		}

		return [$this->getKnownVersion((string) $majorVersion, $versionMaps), false];
	}

	/**
	 * @param array<int|string, array{classes: array<string, string>, functions: array<string, string>, constants: array<string, string>}> $versionMaps
	 */
	private function getRuntimeExtensionVersion(string $extensionName, array $versionMaps): ?int
	{
		$version = phpversion($extensionName);
		if ($version === false) {
			return null;
		}

		return $this->getKnownVersion($version, $versionMaps);
	}

	/**
	 * @param array<int|string, array{classes: array<string, string>, functions: array<string, string>, constants: array<string, string>}> $versionMaps
	 */
	private function getKnownVersion(string $version, array $versionMaps): ?int
	{
		if (preg_match('~^(\d+)~', $version, $matches) !== 1) {
			return null;
		}

		$majorVersion = (int) $matches[1];
		if (!array_key_exists($majorVersion, $versionMaps)) {
			return null;
		}

		return $majorVersion;
	}

	/**
	 */
	private function getConstraintMajor(string $constraint): ?int
	{
		if (str_contains($constraint, '|')) {
			return null;
		}

		try {
			$parsedConstraint = (new VersionParser())->parseConstraints($constraint);
		} catch (UnexpectedValueException) {
			return null;
		}

		$lowerBound = $this->getVersionMajor($parsedConstraint->getLowerBound()->getVersion());
		$upperBound = $this->getVersionMajor($parsedConstraint->getUpperBound()->getVersion());
		if ($lowerBound === null || $upperBound === null) {
			return null;
		}

		if ($lowerBound === $upperBound) {
			return $lowerBound;
		}

		if ($upperBound !== $lowerBound + 1 || $parsedConstraint->getUpperBound()->isInclusive()) {
			return null;
		}

		return $lowerBound;
	}

	private function getVersionMajor(string $version): ?int
	{
		if (preg_match('~^(\d+)~', $version, $matches) !== 1) {
			return null;
		}

		return (int) $matches[1];
	}

}
