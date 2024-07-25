<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDoc\Tag\AssertTagParameter;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function count;
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
		private bool $explicit,
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

	public function isExplicit(): bool
	{
		return $this->explicit;
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

	/**
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 */
	public static function resolvePhpDocBlockForProperty(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $propertyName,
		?string $file,
		?bool $explicit,
		array $originalPositionalParameterNames, // unused
		array $newPositionalParameterNames, // unused
	): self
	{
		return self::resolvePhpDocBlockTree(
			$docComment,
			$classReflection,
			$trait,
			$propertyName,
			$file,
			'hasNativeProperty',
			'getNativeProperty',
			__FUNCTION__,
			$explicit,
			[],
			[],
		);
	}

	/**
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 */
	public static function resolvePhpDocBlockForConstant(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait, // unused
		string $constantName,
		?string $file,
		?bool $explicit,
		array $originalPositionalParameterNames, // unused
		array $newPositionalParameterNames, // unused
	): self
	{
		return self::resolvePhpDocBlockTree(
			$docComment,
			$classReflection,
			null,
			$constantName,
			$file,
			'hasConstant',
			'getConstant',
			__FUNCTION__,
			$explicit,
			[],
			[],
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
		?bool $explicit,
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames,
	): self
	{
		return self::resolvePhpDocBlockTree(
			$docComment,
			$classReflection,
			$trait,
			$methodName,
			$file,
			'hasNativeMethod',
			'getNativeMethod',
			__FUNCTION__,
			$explicit,
			$originalPositionalParameterNames,
			$newPositionalParameterNames,
		);
	}

	/**
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 */
	private static function resolvePhpDocBlockTree(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $name,
		?string $file,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		?bool $explicit,
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames,
	): self
	{
		$docBlocksFromParents = self::resolveParentPhpDocBlocks(
			$classReflection,
			$name,
			$hasMethodName,
			$getMethodName,
			$resolveMethodName,
			$explicit ?? $docComment !== null,
			$newPositionalParameterNames,
		);

		return new self(
			$docComment ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
			$file,
			$classReflection,
			$trait,
			$explicit ?? true,
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
	 * @param array<int, string> $positionalParameterNames
	 * @return array<int, self>
	 */
	private static function resolveParentPhpDocBlocks(
		ClassReflection $classReflection,
		string $name,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		bool $explicit,
		array $positionalParameterNames,
	): array
	{
		$result = [];
		$parentReflections = self::getParentReflections($classReflection);

		foreach ($parentReflections as $parentReflection) {
			$oneResult = self::resolvePhpDocBlockFromClass(
				$parentReflection,
				$name,
				$hasMethodName,
				$getMethodName,
				$resolveMethodName,
				$explicit,
				$positionalParameterNames,
			);

			if ($oneResult === null) { // Null if it is private or from a wrong trait.
				continue;
			}

			$result[] = $oneResult;
		}

		return $result;
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

	/**
	 * @param array<int, string> $positionalParameterNames
	 */
	private static function resolvePhpDocBlockFromClass(
		ClassReflection $classReflection,
		string $name,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		bool $explicit,
		array $positionalParameterNames,
	): ?self
	{
		if ($classReflection->$hasMethodName($name)) {
			/** @var PropertyReflection|MethodReflection|ConstantReflection $parentReflection */
			$parentReflection = $classReflection->$getMethodName($name);
			if ($parentReflection->isPrivate()) {
				return null;
			}

			$classReflection = $parentReflection->getDeclaringClass();

			if ($parentReflection instanceof PhpPropertyReflection || $parentReflection instanceof ResolvedPropertyReflection) {
				$traitReflection = $parentReflection->getDeclaringTrait();
				$positionalMethodParameterNames = [];
			} elseif ($parentReflection instanceof MethodReflection) {
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
			} else {
				$traitReflection = null;
				$positionalMethodParameterNames = [];
			}

			$trait = $traitReflection !== null
				? $traitReflection->getName()
				: null;

			return self::$resolveMethodName(
				$parentReflection->getDocComment() ?? ResolvedPhpDocBlock::EMPTY_DOC_STRING,
				$classReflection,
				$trait,
				$name,
				$classReflection->getFileName(),
				$explicit,
				$positionalParameterNames,
				$positionalMethodParameterNames,
			);
		}

		return null;
	}

}
