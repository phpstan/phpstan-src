<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;
use function array_map;

class IntersectionTypeUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	private string $methodName;

	/** @var UnresolvedMethodPrototypeReflection[] */
	private array $methodPrototypes;

	private ?MethodReflection $transformedMethod = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

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
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}

		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodName, array_map(static function (UnresolvedMethodPrototypeReflection $prototype): UnresolvedMethodPrototypeReflection {
			return $prototype->doNotResolveTemplateTypeMapToBounds();
		}, $this->methodPrototypes));
	}

	public function getNakedMethod(): MethodReflection
	{
		return $this->getTransformedMethod();
	}

	public function getTransformedMethod(): MethodReflection
	{
		if ($this->transformedMethod !== null) {
			return $this->transformedMethod;
		}
		$methods = array_map(static function (UnresolvedMethodPrototypeReflection $prototype): MethodReflection {
			return $prototype->getTransformedMethod();
		}, $this->methodPrototypes);

		return $this->transformedMethod = new IntersectionTypeMethodReflection($this->methodName, $methods);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self($this->methodName, array_map(static function (UnresolvedMethodPrototypeReflection $prototype) use ($type): UnresolvedMethodPrototypeReflection {
			return $prototype->withCalledOnType($type);
		}, $this->methodPrototypes));
	}

}
