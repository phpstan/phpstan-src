<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Node\Constant\PhpDocClassConstantReference;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Node as PhpDocNode;
use PHPStan\Reflection\ClassReflection;
use function get_object_vars;
use function is_array;

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
			foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
				$this->collectPhpDocConstantFetches($phpDocNode, $result);
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

			foreach ($methodPhpDoc->getPhpDocNodes() as $phpDocNode) {
				$this->collectPhpDocConstantFetches($phpDocNode, $result);
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

			foreach ($propertyPhpDoc->getPhpDocNodes() as $phpDocNode) {
				$this->collectPhpDocConstantFetches($phpDocNode, $result);
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

	/**
	 * @param PhpDocClassConstantReference[] $result
	 */
	private function collectPhpDocConstantFetches(PhpDocNode $phpDocNode, array &$result): void
	{
		if ($phpDocNode instanceof ConstFetchNode) {
			if ($phpDocNode->className !== '') {
				$result[] = new PhpDocClassConstantReference($phpDocNode->className, $phpDocNode->name);
			}
			return;
		}

		foreach (get_object_vars($phpDocNode) as $prop) {
			if ($prop instanceof PhpDocNode) {
				$this->collectPhpDocConstantFetches($prop, $result);
			} elseif (is_array($prop)) {
				foreach ($prop as $item) {
					if (!($item instanceof PhpDocNode)) {
						continue;
					}

					$this->collectPhpDocConstantFetches($item, $result);
				}
			}
		}
	}

}
