<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

class IntersectionTypeUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	private string $methodName;

	/** @var UnresolvedMethodPrototypeReflection[] */
	private array $methodPrototypes;

	/**
	 * @param UnresolvedMethodPrototypeReflection[] $methodPrototypes
	 */
	public function __construct(
		string $methodName,
		array $methodPrototypes
	)
	{
		$this->methodName = $methodName;
		$this->methodPrototypes = $methodPrototypes;
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		return new self($this->methodName, array_map(static function (UnresolvedMethodPrototypeReflection $prototype): UnresolvedMethodPrototypeReflection {
			return $prototype->doNotResolveTemplateTypeMapToBounds();
		}, $this->methodPrototypes));
	}

	public function getNakedMethod(): MethodReflection
	{
		return $this->getTransformedMethod();
	}

	public function getTransformedMethod(): MethodReflection
	{
		$methods = array_map(static function (UnresolvedMethodPrototypeReflection $prototype): MethodReflection {
			return $prototype->getTransformedMethod();
		}, $this->methodPrototypes);

		return new IntersectionTypeMethodReflection($this->methodName, $methods);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self($this->methodName, array_map(static function (UnresolvedMethodPrototypeReflection $prototype) use ($type): UnresolvedMethodPrototypeReflection {
			return $prototype->withCalledOnType($type);
		}, $this->methodPrototypes));
	}

}
