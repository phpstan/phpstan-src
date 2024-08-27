<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;
use function array_map;

final class UnionTypeUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	private ?ExtendedMethodReflection $transformedMethod = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	/**
	 * @param UnresolvedMethodPrototypeReflection[] $methodPrototypes
	 */
	public function __construct(
		private string $methodName,
		private array $methodPrototypes,
	)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}

		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodName, array_map(static fn (UnresolvedMethodPrototypeReflection $prototype): UnresolvedMethodPrototypeReflection => $prototype->doNotResolveTemplateTypeMapToBounds(), $this->methodPrototypes));
	}

	public function getNakedMethod(): ExtendedMethodReflection
	{
		return $this->getTransformedMethod();
	}

	public function getTransformedMethod(): ExtendedMethodReflection
	{
		if ($this->transformedMethod !== null) {
			return $this->transformedMethod;
		}

		$methods = array_map(static fn (UnresolvedMethodPrototypeReflection $prototype): MethodReflection => $prototype->getTransformedMethod(), $this->methodPrototypes);

		return $this->transformedMethod = new UnionTypeMethodReflection($this->methodName, $methods);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self($this->methodName, array_map(static fn (UnresolvedMethodPrototypeReflection $prototype): UnresolvedMethodPrototypeReflection => $prototype->withCalledOnType($type), $this->methodPrototypes));
	}

}
