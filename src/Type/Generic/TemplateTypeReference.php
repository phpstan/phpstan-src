<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

class TemplateTypeReference
{

	private TemplateType $type;

	private TemplateTypeVariance $positionVariance;

	public function __construct(TemplateType $type, TemplateTypeVariance $positionVariance)
	{
		$this->type = $type;
		$this->positionVariance = $positionVariance;
	}

	public function getType(): TemplateType
	{
		return $this->type;
	}

	public function getPositionVariance(): TemplateTypeVariance
	{
		return $this->positionVariance;
	}

}
