<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class DummyParameterWithPhpDocs extends DummyParameter implements ParameterReflectionWithPhpDocs
{

	public function __construct(
		string $name,
		Type $type,
		bool $optional,
		?PassedByReference $passedByReference,
		bool $variadic,
		?Type $defaultValue,
		private Type $nativeType,
		private Type $phpDocType,
		private ?Type $outType,
		private TrinaryLogic $immediatelyInvokedCallable,
		private ?Type $closureThisType,
	)
	{
		parent::__construct($name, $type, $optional, $passedByReference, $variadic, $defaultValue);
	}

	public function getPhpDocType(): Type
	{
		return $this->phpDocType;
	}

	public function getNativeType(): Type
	{
		return $this->nativeType;
	}

	public function getOutType(): ?Type
	{
		return $this->outType;
	}

	public function isImmediatelyInvokedCallable(): TrinaryLogic
	{
		return $this->immediatelyInvokedCallable;
	}

	public function getClosureThisType(): ?Type
	{
		return $this->closureThisType;
	}

}
