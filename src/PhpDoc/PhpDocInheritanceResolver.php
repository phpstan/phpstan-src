<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Type\FileTypeMapper;
use function array_key_exists;
use function count;
use function is_bool;
use function strtolower;

#[AutowiredService]
final class PhpDocInheritanceResolver
{

	public function __construct(private FileTypeMapper $fileTypeMapper)
	{
	}

	public function resolvePhpDocForProperty(
		ClassReflection $declaringClass,
		string $propertyName,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
	): ?ResolvedPhpDocBlock
	{
		$parent = $declaringClass->getParentClass();
		if ($parent !== null) {
			$parentMethod = $this->resolvePropertyPhpDocFromParentClass($declaringClass, $parent, $propertyName, $currentResolvedPhpDoc);
			if ($parentMethod !== null) {
				return $parentMethod;
			}
		}

		foreach ($declaringClass->getImmediateInterfaces() as $interface) {
			$interfaceMethod = $this->resolvePropertyPhpDocFromParentClass($declaringClass, $interface, $propertyName, $currentResolvedPhpDoc);
			if ($interfaceMethod === null) {
				continue;
			}

			return $interfaceMethod;
		}

		return $currentResolvedPhpDoc;
	}

	public function resolvePhpDocForConstant(
		ClassReflection $declaringClass,
		string $constantName,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
	): ?ResolvedPhpDocBlock
	{
		$parent = $declaringClass->getParentClass();
		if ($parent !== null) {
			$parentMethod = $this->resolveConstantPhpDocFromParentClass($declaringClass, $parent, $constantName, $currentResolvedPhpDoc);
			if ($parentMethod !== null) {
				return $parentMethod;
			}
		}

		foreach ($declaringClass->getImmediateInterfaces() as $interface) {
			$interfaceMethod = $this->resolveConstantPhpDocFromParentClass($declaringClass, $interface, $constantName, $currentResolvedPhpDoc);
			if ($interfaceMethod === null) {
				continue;
			}

			return $interfaceMethod;
		}

		return $currentResolvedPhpDoc;
	}

	/**
	 * @param array<int, string> $currentPositionalParameterNames
	 */
	public function resolvePhpDocForMethod(
		ClassReflection $declaringClass,
		string $methodName,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
		array $currentPositionalParameterNames,
	): ?ResolvedPhpDocBlock
	{
		$parent = $declaringClass->getParentClass();
		if ($parent !== null) {
			if ($parent->hasNativeMethod($methodName)) {
				$parentMethod = $parent->getNativeMethod($methodName);
				if (!$parentMethod->isPrivate() && $parentMethod->getResolvedPhpDoc() !== null) {
					if ($parentMethod->getName() !== '__construct' || !$parentMethod->getDeclaringClass()->isBuiltin()) {
						return $this->resolveMethodPhpDocFromParentClass($parentMethod, $parentMethod->getResolvedPhpDoc(), $declaringClass, $parent, $currentResolvedPhpDoc, $currentPositionalParameterNames);
					}
				}
			}
		}

		foreach ($declaringClass->getImmediateInterfaces() as $interface) {
			if (!$interface->hasNativeMethod($methodName)) {
				continue;
			}

			$interfaceMethod = $interface->getNativeMethod($methodName);
			if ($interfaceMethod->isPrivate()) {
				continue;
			}
			if ($interfaceMethod->getResolvedPhpDoc() === null) {
				continue;
			}
			return $this->resolveMethodPhpDocFromParentClass($interfaceMethod, $interfaceMethod->getResolvedPhpDoc(), $declaringClass, $interface, $currentResolvedPhpDoc, $currentPositionalParameterNames);

		}

		foreach ($declaringClass->getTraits() as $trait) {
			if (!$trait->hasNativeMethod($methodName)) {
				continue;
			}

			$traitMethod = $trait->getNativeMethod($methodName);
			if ($traitMethod->getDocComment() === null) {
				continue;
			}
			if ($declaringClass->getFileName() === null) {
				continue;
			}

			$abstract = $traitMethod->isAbstract();
			if (is_bool($abstract)) {
				if (!$abstract) {
					continue;
				}
			} elseif (!$abstract->yes()) {
				continue;
			}

			$resolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc(
				$declaringClass->getFileName(),
				$declaringClass->getName(),
				$trait->getName(),
				$methodName,
				$traitMethod->getDocComment(),
			);

			return $this->resolveMethodPhpDocFromParentClass($traitMethod, $resolvedPhpDocBlock, $declaringClass, $trait, $currentResolvedPhpDoc, $currentPositionalParameterNames);
		}

		return $currentResolvedPhpDoc;
	}

	/**
	 * @param array<int, string> $currentPositionalParameterNames
	 */
	private function resolveMethodPhpDocFromParentClass(
		ExtendedMethodReflection $parentMethod,
		ResolvedPhpDocBlock $resolvedPhpDocBlock,
		ClassReflection $declaringClass,
		ClassReflection $parent,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
		array $currentPositionalParameterNames,
	): ResolvedPhpDocBlock
	{
		if ($currentResolvedPhpDoc === null) {
			$currentResolvedPhpDoc = ResolvedPhpDocBlock::createEmpty();
		}

		$methodVariants = $parentMethod->getVariants();
		$positionalMethodParameterNames = [];
		$lowercaseMethodName = strtolower($parentMethod->getName());
		if (
			count($methodVariants) === 1
			&& $lowercaseMethodName !== '__construct'
			&& $lowercaseMethodName !== strtolower($parentMethod->getDeclaringClass()->getName())
		) {
			$methodParameters = $methodVariants[0]->getParameters();
			foreach ($methodParameters as $methodParameter) {
				$positionalMethodParameterNames[] = $methodParameter->getName();
			}
		} else {
			$positionalMethodParameterNames = $currentPositionalParameterNames;
		}

		$parentClassForMerge = $parent;
		$phpDocDeclaringClass = $parentMethod->getDeclaringClass();
		if ($phpDocDeclaringClass->getName() !== $parent->getName()) {
			$ancestor = $parent->getAncestorWithClassName($phpDocDeclaringClass->getName());
			if ($ancestor !== null) {
				$parentClassForMerge = $ancestor;
			}
		}

		return $currentResolvedPhpDoc->merge($resolvedPhpDocBlock, new InheritedPhpDocParameterMapping(self::remapParameterNames($currentPositionalParameterNames, $positionalMethodParameterNames)), $declaringClass, $parentClassForMerge);
	}

	/**
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 * @return array<string, string>
	 */
	private static function remapParameterNames(
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames,
	): array
	{
		$parameterNameMapping = [];
		foreach ($originalPositionalParameterNames as $i => $parameterName) {
			if (!array_key_exists($i, $newPositionalParameterNames)) {
				continue;
			}
			$parameterNameMapping[$newPositionalParameterNames[$i]] = $parameterName;
		}

		return $parameterNameMapping;
	}

	private function resolveConstantPhpDocFromParentClass(
		ClassReflection $declaringClass,
		ClassReflection $parent,
		string $constantName,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
	): ?ResolvedPhpDocBlock
	{
		if (!$parent->hasConstant($constantName)) {
			return null;
		}

		$parentConstant = $parent->getConstant($constantName);
		if ($parentConstant->isPrivate()) {
			return null;
		}

		if ($parentConstant->getResolvedPhpDoc() === null) {
			return null;
		}

		if ($currentResolvedPhpDoc === null) {
			$currentResolvedPhpDoc = ResolvedPhpDocBlock::createEmpty();
		}

		$parentClassForMerge = $parent;
		$phpDocDeclaringClass = $parentConstant->getDeclaringClass();
		if ($phpDocDeclaringClass->getName() !== $parent->getName()) {
			$ancestor = $parent->getAncestorWithClassName($phpDocDeclaringClass->getName());
			if ($ancestor !== null) {
				$parentClassForMerge = $ancestor;
			}
		}

		return $currentResolvedPhpDoc->merge($parentConstant->getResolvedPhpDoc(), new InheritedPhpDocParameterMapping([]), $declaringClass, $parentClassForMerge);
	}

	private function resolvePropertyPhpDocFromParentClass(
		ClassReflection $declaringClass,
		ClassReflection $parent,
		string $propertyName,
		?ResolvedPhpDocBlock $currentResolvedPhpDoc,
	): ?ResolvedPhpDocBlock
	{
		if (!$parent->hasNativeProperty($propertyName)) {
			return null;
		}

		$parentProperty = $parent->getNativeProperty($propertyName);
		if ($parentProperty->isPrivate()) {
			return null;
		}

		if ($parentProperty->getResolvedPhpDoc() === null) {
			return null;
		}

		if ($currentResolvedPhpDoc === null) {
			$currentResolvedPhpDoc = ResolvedPhpDocBlock::createEmpty();
		}

		$parentClassForMerge = $parent;
		$phpDocDeclaringClass = $parentProperty->getDeclaringClass();
		if ($phpDocDeclaringClass->getName() !== $parent->getName()) {
			$ancestor = $parent->getAncestorWithClassName($phpDocDeclaringClass->getName());
			if ($ancestor !== null) {
				$parentClassForMerge = $ancestor;
			}
		}

		return $currentResolvedPhpDoc->merge($parentProperty->getResolvedPhpDoc(), new InheritedPhpDocParameterMapping([]), $declaringClass, $parentClassForMerge);
	}

}
