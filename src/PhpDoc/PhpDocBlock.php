<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDoc\Tag\AssertTagParameter;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function count;
use function is_bool;
use function strtolower;
use function substr;

final class PhpDocBlock
{

	/**
	 * @param array<string, string> $parameterNameMapping
	 * @param array<int, self> $parents
	 */
	private function __construct(
		private string $docComment,
		private ?string $file,
		private ClassReflection $classReflection,
		private ?string $trait,
		private array $parameterNameMapping,
		private array $parents,
	)
	{
	}

	public function getDocComment(): string
	{
		return $this->docComment;
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getTrait(): ?string
	{
		return $this->trait;
	}

	/**
	 * @return array<int, self>
	 */
	public function getParents(): array
	{
		return $this->parents;
	}

	/**
	 * @template T
	 * @param array<string, T> $array
	 * @return array<string, T>
	 */
	public function transformArrayKeysWithParameterNameMapping(array $array): array
	{
		$newArray = [];
		foreach ($array as $key => $value) {
			if (!array_key_exists($key, $this->parameterNameMapping)) {
				continue;
			}
			$newArray[$this->parameterNameMapping[$key]] = $value;
		}

		return $newArray;
	}

	public function transformConditionalReturnTypeWithParameterNameMapping(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$parameterName = substr($type->getParameterName(), 1);
				if (array_key_exists($parameterName, $this->parameterNameMapping)) {
					$type = $type->changeParameterName('$' . $this->parameterNameMapping[$parameterName]);
				}
			}

			return $traverse($type);
		});
	}

	public function transformAssertTagParameterWithParameterNameMapping(AssertTagParameter $parameter): AssertTagParameter
	{
		$parameterName = substr($parameter->getParameterName(), 1);
		if (array_key_exists($parameterName, $this->parameterNameMapping)) {
			$parameter = $parameter->changeParameterName('$' . $this->parameterNameMapping[$parameterName]);
		}

		return $parameter;
	}

	public static function resolvePhpDocBlockForProperty(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $propertyName,
		?string $file,
	): self
	{
		$docBlocksFromParents = [];
		foreach (self::getParentReflections($classReflection) as $parentReflection) {
			$oneResult = self::resolvePropertyPhpDocBlockFromClass(
				$parentReflection,
				$propertyName,
			);

			if ($oneResult === null) { // Null if it is private or from a wrong trait.
				continue;
			}

			$docBlocksFromParents[] = $oneResult;
		}

		return new self(
			$docComment ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
			$file,
			$classReflection,
			$trait,
			[],
			$docBlocksFromParents,
		);
	}

	public static function resolvePhpDocBlockForConstant(
		?string $docComment,
		ClassReflection $classReflection,
		string $constantName,
		?string $file,
	): self
	{
		$docBlocksFromParents = [];
		foreach (self::getParentReflections($classReflection) as $parentReflection) {
			$oneResult = self::resolveConstantPhpDocBlockFromClass(
				$parentReflection,
				$constantName,
			);

			if ($oneResult === null) { // Null if it is private or from a wrong trait.
				continue;
			}

			$docBlocksFromParents[] = $oneResult;
		}

		return new self(
			$docComment ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
			$file,
			$classReflection,
			null,
			[],
			$docBlocksFromParents,
		);
	}

	/**
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 */
	public static function resolvePhpDocBlockForMethod(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $methodName,
		?string $file,
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames,
	): self
	{
		$docBlocksFromParents = [];
		foreach (self::getParentReflections($classReflection) as $parentReflection) {
			$oneResult = self::resolveMethodPhpDocBlockFromClass(
				$parentReflection,
				$methodName,
				$newPositionalParameterNames,
			);

			if ($oneResult === null) { // Null if it is private or from a wrong trait.
				continue;
			}

			$docBlocksFromParents[] = $oneResult;
		}

		foreach ($classReflection->getTraits(true) as $traitReflection) {
			if (!$traitReflection->hasNativeMethod($methodName)) {
				continue;
			}
			$traitMethod = $traitReflection->getNativeMethod($methodName);
			$abstract = $traitMethod->isAbstract();
			if (is_bool($abstract)) {
				if (!$abstract) {
					continue;
				}
			} elseif (!$abstract->yes()) {
				continue;
			}

			$methodVariant = $traitMethod->getOnlyVariant();
			$positionalMethodParameterNames = [];
			foreach ($methodVariant->getParameters() as $methodParameter) {
				$positionalMethodParameterNames[] = $methodParameter->getName();
			}

			$docBlocksFromParents[] = new self(
				$traitMethod->getDocComment() ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
				$classReflection->getFileName(),
				$classReflection,
				$traitReflection->getName(),
				self::remapParameterNames($newPositionalParameterNames, $positionalMethodParameterNames),
				[],
			);
		}

		return new self(
			$docComment ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
			$file,
			$classReflection,
			$trait,
			self::remapParameterNames($originalPositionalParameterNames, $newPositionalParameterNames),
			$docBlocksFromParents,
		);
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

	/**
	 * @return array<int, ClassReflection>
	 */
	private static function getParentReflections(ClassReflection $classReflection): array
	{
		$result = [];

		$parent = $classReflection->getParentClass();
		if ($parent !== null) {
			$result[] = $parent;
		}

		foreach ($classReflection->getInterfaces() as $interface) {
			$result[] = $interface;
		}

		return $result;
	}

	private static function resolveConstantPhpDocBlockFromClass(
		ClassReflection $classReflection,
		string $name,
	): ?self
	{
		if ($classReflection->hasConstant($name)) {
			$parentReflection = $classReflection->getConstant($name);
			if ($parentReflection->isPrivate()) {
				return null;
			}

			$classReflection = $parentReflection->getDeclaringClass();

			return self::resolvePhpDocBlockForConstant(
				$parentReflection->getDocComment() ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
				$classReflection,
				$name,
				$classReflection->getFileName(),
			);
		}

		return null;
	}

	private static function resolvePropertyPhpDocBlockFromClass(
		ClassReflection $classReflection,
		string $name,
	): ?self
	{
		if ($classReflection->hasNativeProperty($name)) {
			$parentReflection = $classReflection->getNativeProperty($name);
			if ($parentReflection->isPrivate()) {
				return null;
			}

			$classReflection = $parentReflection->getDeclaringClass();
			$traitReflection = $parentReflection->getDeclaringTrait();

			$trait = $traitReflection !== null
				? $traitReflection->getName()
				: null;

			return self::resolvePhpDocBlockForProperty(
				$parentReflection->getDocComment() ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
				$classReflection,
				$trait,
				$name,
				$classReflection->getFileName(),
			);
		}

		return null;
	}

	/**
	 * @param array<int, string> $positionalParameterNames
	 */
	private static function resolveMethodPhpDocBlockFromClass(
		ClassReflection $classReflection,
		string $name,
		array $positionalParameterNames,
	): ?self
	{
		if ($classReflection->hasNativeMethod($name)) {
			$parentReflection = $classReflection->getNativeMethod($name);
			if ($parentReflection->isPrivate()) {
				return null;
			}

			$classReflection = $parentReflection->getDeclaringClass();
			$traitReflection = null;
			if ($parentReflection instanceof PhpMethodReflection || $parentReflection instanceof ResolvedMethodReflection) {
				$traitReflection = $parentReflection->getDeclaringTrait();
			}
			$methodVariants = $parentReflection->getVariants();
			$positionalMethodParameterNames = [];
			$lowercaseMethodName = strtolower($parentReflection->getName());
			if (
				count($methodVariants) === 1
				&& $lowercaseMethodName !== '__construct'
				&& $lowercaseMethodName !== strtolower($parentReflection->getDeclaringClass()->getName())
			) {
				$methodParameters = $methodVariants[0]->getParameters();
				foreach ($methodParameters as $methodParameter) {
					$positionalMethodParameterNames[] = $methodParameter->getName();
				}
			} else {
				$positionalMethodParameterNames = $positionalParameterNames;
			}

			$trait = $traitReflection !== null
				? $traitReflection->getName()
				: null;

			return self::resolvePhpDocBlockForMethod(
				$parentReflection->getDocComment() ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
				$classReflection,
				$trait,
				$name,
				$classReflection->getFileName(),
				$positionalParameterNames,
				$positionalMethodParameterNames,
			);
		}

		return null;
	}

}
