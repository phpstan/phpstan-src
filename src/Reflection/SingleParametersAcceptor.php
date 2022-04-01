<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class SingleParametersAcceptor implements ParametersAcceptor
{

	public function __construct(private ParametersAcceptor $acceptor)
	{
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->acceptor->getTemplateTypeMap();
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->acceptor->getResolvedTemplateTypeMap();
	}

	/**
	 * @return array<int, ParameterReflection>
	 */
	public function getParameters(): array
	{
		return $this->acceptor->getParameters();
	}

	public function isVariadic(): bool
	{
		return $this->acceptor->isVariadic();
	}

	public function getReturnType(): Type
	{
		return TypeTraverser::map($this->acceptor->getReturnType(), static function (Type $type, callable $traverse) {
			while ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
				$type = $type->getResult();
			}

			return $traverse($type);
		});
	}

}
