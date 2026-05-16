<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Node\Constant\PhpDocClassConstantReference;
use PHPStan\Reflection\ClassReflection;

/**
 * @api
 */
final class ClassConstantsNode extends NodeAbstract implements VirtualNode
{

	/** @var PhpDocClassConstantReference[]|null */
	private ?array $phpDocFetches = null;

	/**
	 * @param ClassConst[] $constants
	 * @param ClassConstantFetch[] $fetches
	 */
	public function __construct(private ClassLike $class, private array $constants, private array $fetches, private ClassReflection $classReflection)
	{
		parent::__construct($class->getAttributes());
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return ClassConst[]
	 */
	public function getConstants(): array
	{
		return $this->constants;
	}

	/**
	 * @return ClassConstantFetch[]
	 */
	public function getFetches(): array
	{
		return $this->fetches;
	}

	/**
	 * @return PhpDocClassConstantReference[]
	 */
	public function getPhpDocFetches(): array
	{
		if ($this->phpDocFetches !== null) {
			return $this->phpDocFetches;
		}

		$result = [];
		$className = $this->classReflection->getName();

		$resolvedPhpDoc = $this->classReflection->getResolvedPhpDoc();
		if ($resolvedPhpDoc !== null) {
			foreach ($resolvedPhpDoc->getClassConstantReferences() as $reference) {
				$result[] = $reference;
			}
		}

		foreach ($this->classReflection->getNativeReflection()->getMethods() as $method) {
			if ($method->getDeclaringClass()->getName() !== $className) {
				continue;
			}
			if (!$this->classReflection->hasNativeMethod($method->getName())) {
				continue;
			}
			$methodReflection = $this->classReflection->getNativeMethod($method->getName());
			$methodPhpDoc = $methodReflection->getResolvedPhpDoc();
			if ($methodPhpDoc === null) {
				continue;
			}

			foreach ($methodPhpDoc->getClassConstantReferences() as $reference) {
				$result[] = $reference;
			}
		}

		foreach ($this->classReflection->getNativeReflection()->getProperties() as $property) {
			if ($property->getDeclaringClass()->getName() !== $className) {
				continue;
			}
			if (!$this->classReflection->hasNativeProperty($property->getName())) {
				continue;
			}
			$propertyReflection = $this->classReflection->getNativeProperty($property->getName());
			$propertyPhpDoc = $propertyReflection->getResolvedPhpDoc();
			if ($propertyPhpDoc === null) {
				continue;
			}

			foreach ($propertyPhpDoc->getClassConstantReferences() as $reference) {
				$result[] = $reference;
			}
		}

		$this->phpDocFetches = $result;

		return $result;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ClassConstantsNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

}
