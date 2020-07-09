<?php declare(strict_types = 1);

namespace PHPStan\Php;

class PhpVersion
{

	private int $versionId;

	public function __construct(int $versionId)
	{
		$this->versionId = $versionId;
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

}
