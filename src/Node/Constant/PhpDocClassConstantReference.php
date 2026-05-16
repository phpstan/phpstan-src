<?php declare(strict_types = 1);

namespace PHPStan\Node\Constant;

final class PhpDocClassConstantReference
{

	public function __construct(private string $className, private string $constantName)
	{
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getConstantName(): string
	{
		return $this->constantName;
	}

}
