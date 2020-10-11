<?php declare(strict_types = 1);

namespace PHPStan\Php;

class PhpVersion
{

	private int $versionId;

	public function __construct(int $versionId)
	{
		$this->versionId = $versionId;
	}

	public function getVersionId(): int
	{
		return $this->versionId;
	}

	public function getVersionString(): string
	{
		$first = (int) floor($this->versionId / 10000);
		$second = (int) floor(($this->versionId % 10000) / 100);
		$third = (int) floor($this->versionId % 100);

		return $first . '.' . $second . ($third !== 0 ? '.' . $third : '');
	}

	public function supportsNullCoalesceAssign(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsParameterContravariance(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsReturnCovariance(): bool
	{
		return $this->versionId >= 70400;
	}

	public function supportsNativeUnionTypes(): bool
	{
		return $this->versionId >= 80000;
	}

	public function deprecatesRequiredParameterAfterOptional(): bool
	{
		return $this->versionId >= 80000;
	}

	public function supportsLessOverridenParametersWithVariadic(): bool
	{
		return $this->versionId >= 80000;
	}

	public function requiresParenthesesForNestedTernaries(): bool
	{
		return $this->versionId >= 80000;
	}

}
