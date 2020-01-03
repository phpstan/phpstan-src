<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;

class PhpDocBlock
{

	/** @var string */
	private $docComment;

	/** @var string */
	private $file;

	/** @var ClassReflection */
	private $classReflection;

	/** @var string|null */
	private $trait;

	/** @var bool */
	private $explicit;

	/** @var array<string, string> */
	private $parameterNameMapping;

	/**
	 * @param string $docComment
	 * @param string $file
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string|null $trait
	 * @param bool $explicit
	 * @param array<string, string> $parameterNameMapping
	 */
	private function __construct(
		string $docComment,
		string $file,
		ClassReflection $classReflection,
		?string $trait,
		bool $explicit,
		array $parameterNameMapping
	)
	{
		$this->docComment = $docComment;
		$this->file = $file;
		$this->classReflection = $classReflection;
		$this->trait = $trait;
		$this->explicit = $explicit;
		$this->parameterNameMapping = $parameterNameMapping;
	}

	public function getDocComment(): string
	{
		return $this->docComment;
	}

	public function getFile(): string
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

	/**
	 * @param string|null $docComment
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string|null $trait
	 * @param string $propertyName
	 * @param string $file
	 * @param bool|null $explicit
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 * @return self|null
	 */
	public static function resolvePhpDocBlockForProperty(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $propertyName,
		string $file,
		?bool $explicit,
		array $originalPositionalParameterNames, // unused
		array $newPositionalParameterNames // unused
	): ?self
	{
		return self::resolvePhpDocBlock(
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
			[]
		);
	}

	/**
	 * @param string|null $docComment
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string|null $trait
	 * @param string $methodName
	 * @param string $file
	 * @param bool|null $explicit
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 * @return self|null
	 */
	public static function resolvePhpDocBlockForMethod(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $methodName,
		string $file,
		?bool $explicit,
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames
	): ?self
	{
		return self::resolvePhpDocBlock(
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
			$newPositionalParameterNames
		);
	}

	/**
	 * @param string|null $docComment
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string|null $trait
	 * @param string $name
	 * @param string $file
	 * @param string $hasMethodName
	 * @param string $getMethodName
	 * @param string $resolveMethodName
	 * @param bool|null $explicit
	 * @param array<int, string> $originalPositionalParameterNames
	 * @param array<int, string> $newPositionalParameterNames
	 * @return self|null
	 */
	private static function resolvePhpDocBlock(
		?string $docComment,
		ClassReflection $classReflection,
		?string $trait,
		string $name,
		string $file,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		?bool $explicit,
		array $originalPositionalParameterNames,
		array $newPositionalParameterNames
	): ?self
	{
		if (
			$docComment === null
			|| preg_match('#@inheritdoc|\{@inheritdoc\}#i', $docComment) > 0
		) {
			if ($classReflection->getParentClass() !== false) {
				$parentClassReflection = $classReflection->getParentClass();
				$phpDocBlockFromClass = self::resolvePhpDocBlockRecursive(
					$parentClassReflection,
					$trait,
					$name,
					$hasMethodName,
					$getMethodName,
					$resolveMethodName,
					$explicit ?? $docComment !== null,
					$originalPositionalParameterNames
				);
				if ($phpDocBlockFromClass !== null) {
					return $phpDocBlockFromClass;
				}
			}

			foreach ($classReflection->getInterfaces() as $interface) {
				$phpDocBlockFromClass = self::resolvePhpDocBlockFromClass(
					$interface,
					$name,
					$hasMethodName,
					$getMethodName,
					$resolveMethodName,
					$explicit ?? $docComment !== null,
					$originalPositionalParameterNames
				);
				if ($phpDocBlockFromClass !== null) {
					return $phpDocBlockFromClass;
				}
			}
		}

		$parameterNameMapping = [];
		foreach ($originalPositionalParameterNames as $i => $parameterName) {
			if (!array_key_exists($i, $newPositionalParameterNames)) {
				continue;
			}
			$parameterNameMapping[$newPositionalParameterNames[$i]] = $parameterName;
		}

		return $docComment !== null
			? new self($docComment, $file, $classReflection, $trait, $explicit ?? true, $parameterNameMapping)
			: null;
	}

	/**
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string|null $trait
	 * @param string $name
	 * @param string $hasMethodName
	 * @param string $getMethodName
	 * @param string $resolveMethodName
	 * @param bool $explicit
	 * @param array<int, string> $positionalParameterNames $positionalParameterNames
	 * @return self|null
	 */
	private static function resolvePhpDocBlockRecursive(
		ClassReflection $classReflection,
		?string $trait,
		string $name,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		bool $explicit,
		array $positionalParameterNames = []
	): ?self
	{
		$phpDocBlockFromClass = self::resolvePhpDocBlockFromClass(
			$classReflection,
			$name,
			$hasMethodName,
			$getMethodName,
			$resolveMethodName,
			$explicit,
			$positionalParameterNames
		);

		if ($phpDocBlockFromClass !== null) {
			return $phpDocBlockFromClass;
		}

		$parentClassReflection = $classReflection->getParentClass();
		if ($parentClassReflection !== false) {
			return self::resolvePhpDocBlockRecursive(
				$parentClassReflection,
				$trait,
				$name,
				$hasMethodName,
				$getMethodName,
				$resolveMethodName,
				$explicit,
				$positionalParameterNames
			);
		}

		return null;
	}

	/**
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param string $name
	 * @param string $hasMethodName
	 * @param string $getMethodName
	 * @param string $resolveMethodName
	 * @param bool $explicit
	 * @param array<int, string> $positionalParameterNames
	 * @return self|null
	 */
	private static function resolvePhpDocBlockFromClass(
		ClassReflection $classReflection,
		string $name,
		string $hasMethodName,
		string $getMethodName,
		string $resolveMethodName,
		bool $explicit,
		array $positionalParameterNames
	): ?self
	{
		if ($classReflection->getFileNameWithPhpDocs() !== null && $classReflection->$hasMethodName($name)) {
			/** @var \PHPStan\Reflection\PropertyReflection|\PHPStan\Reflection\MethodReflection $parentReflection */
			$parentReflection = $classReflection->$getMethodName($name);
			if ($parentReflection->isPrivate()) {
				return null;
			}
			if (
				!$parentReflection->getDeclaringClass()->isTrait()
				&& $parentReflection->getDeclaringClass()->getName() !== $classReflection->getName()
			) {
				return null;
			}

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
				if (count($methodVariants) === 1) {
					$methodParameters = $methodVariants[0]->getParameters();
					foreach ($methodParameters as $methodParameter) {
						$positionalMethodParameterNames[] = $methodParameter->getName();
					}
				}
			} else {
				$traitReflection = null;
				$positionalMethodParameterNames = [];
			}

			$trait = $traitReflection !== null
				? $traitReflection->getName()
				: null;

			if ($parentReflection->getDocComment() !== null) {
				return self::$resolveMethodName(
					$parentReflection->getDocComment(),
					$classReflection,
					$trait,
					$name,
					$classReflection->getFileNameWithPhpDocs(),
					$explicit,
					$positionalParameterNames,
					$positionalMethodParameterNames
				);
			}
		}

		return null;
	}

}
