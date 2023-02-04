<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta\TypeMapping;

use LogicException;
use function array_key_exists;
use function sprintf;
use function strtolower;

final class CallReturnOverrideCollection
{

	/** @var array<string, MethodCallTypeOverride|FunctionCallTypeOverride> */
	private array $overridesByFqn = [];

	public function addMethodCallOverride(MethodCallTypeOverride $override): void
	{
		$fqn = sprintf('%s::%s', $override->classlikeName, $override->methodName);

		$key = strtolower($fqn);

		if (array_key_exists($key, $this->overridesByFqn)) {
			throw new LogicException(sprintf("An override for method '%s' has already been defined", $fqn));
		}

		$this->overridesByFqn[$key] = $override;
	}

	public function addFunctionCallOverride(FunctionCallTypeOverride $override): void
	{
		$fqn = $override->functionName;

		$key = strtolower($fqn);

		if (array_key_exists($key, $this->overridesByFqn)) {
			throw new LogicException(sprintf("An override for function '%s' has already been defined", $fqn));
		}

		$this->overridesByFqn[$key] = $override;
	}

	/**
	 * @return array<string, MethodCallTypeOverride|FunctionCallTypeOverride>
	 */
	public function getAllOverrides(): array
	{
		return $this->overridesByFqn;
	}

	public function getOverrideForCall(string $fqn): MethodCallTypeOverride|FunctionCallTypeOverride|null
	{
		$key = strtolower($fqn);

		return $this->overridesByFqn[$key] ?? null;
	}

}
