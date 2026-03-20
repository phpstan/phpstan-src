<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class ParameterAllowedConstantsMapProvider
{

	/** @var array<string, array<string, array{type: string, constants: list<string>, exclusiveGroups?: list<list<string>>}>>|null */
	private ?array $map = null;

	public function getForFunctionParameter(string $functionName, string $parameterName): ?ParameterAllowedConstants
	{
		return $this->get($functionName, $parameterName);
	}

	public function getForMethodParameter(string $className, string $methodName, string $parameterName): ?ParameterAllowedConstants
	{
		return $this->get($className . '::' . $methodName, $parameterName);
	}

	private function get(string $key, string $parameterName): ?ParameterAllowedConstants
	{
		$map = $this->getMap();

		if (!isset($map[$key][$parameterName])) {
			return null;
		}

		/** @var array{type: 'single'|'bitmask', constants: list<string>, exclusiveGroups?: list<list<string>>} $config */
		$config = $map[$key][$parameterName];

		return new ParameterAllowedConstants(
			$config['type'],
			$config['constants'],
			$config['exclusiveGroups'] ?? [],
		);
	}

	/**
	 * @return array<string, array<string, array{type: string, constants: list<string>, exclusiveGroups?: list<list<string>>}>>
	 */
	private function getMap(): array
	{
		return $this->map ??= require __DIR__ . '/../../resources/constantToFunctionParameterMap.php';
	}

}
