<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\ShouldNotHappenException;
use function array_slice;
use function count;
use function explode;
use function implode;
use function sprintf;

/** @api */
class InitializerExprContext implements NamespaceAnswerer
{

	/**
	 * @param non-empty-string|null $namespace
	 */
	private function __construct(
		private ?string $file,
		private ?string $namespace,
		private ?string $className,
		private ?string $traitName,
		private ?string $function,
		private ?string $method,
	)
	{
	}

	public static function fromScope(Scope $scope): self
	{
		return new self(
			$scope->getFile(),
			$scope->getNamespace(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$scope->isInAnonymousFunction() ? '{closure}' : ($scope->getFunction() !== null ? $scope->getFunction()->getName() : null),
			$scope->isInAnonymousFunction() ? '{closure}' : ($scope->getFunction() instanceof MethodReflection
				? sprintf('%s::%s', $scope->getFunction()->getDeclaringClass()->getName(), $scope->getFunction()->getName())
				: ($scope->getFunction() instanceof FunctionReflection ? $scope->getFunction()->getName() : null)),
		);
	}

	/**
	 * @return non-empty-string|null
	 */
	private static function parseNamespace(string $name): ?string
	{
		$parts = explode('\\', $name);
		if (count($parts) > 1) {
			$ns = implode('\\', array_slice($parts, 0, -1));
			if ($ns === '') {
				throw new ShouldNotHappenException('Namespace cannot be empty.');
			}
			return $ns;
		}

		return null;
	}

	public static function fromClassReflection(ClassReflection $classReflection): self
	{
		return self::fromClass($classReflection->getName(), $classReflection->getFileName());
	}

	public static function fromClass(string $className, ?string $fileName): self
	{
		return new self(
			$fileName,
			self::parseNamespace($className),
			$className,
			null,
			null,
			null,
		);
	}

	public static function fromReflectionParameter(ReflectionParameter $parameter): self
	{
		$declaringFunction = $parameter->getDeclaringFunction();
		if ($declaringFunction instanceof ReflectionFunction) {
			$file = $declaringFunction->getFileName();
			return new self(
				$file === false ? null : $file,
				self::parseNamespace($declaringFunction->getName()),
				null,
				null,
				$declaringFunction->getName(),
				$declaringFunction->getName(),
			);
		}

		$file = $declaringFunction->getFileName();

		$betterReflection = $declaringFunction->getBetterReflection();

		return new self(
			$file === false ? null : $file,
			self::parseNamespace($betterReflection->getDeclaringClass()->getName()),
			$declaringFunction->getDeclaringClass()->getName(),
			$betterReflection->getDeclaringClass()->isTrait() ? $betterReflection->getDeclaringClass()->getName() : null,
			$declaringFunction->getName(),
			sprintf('%s::%s', $declaringFunction->getDeclaringClass()->getName(), $declaringFunction->getName()),
		);
	}

	public static function fromStubParameter(
		?string $className,
		string $stubFile,
		ClassMethod|Function_ $function,
	): self
	{
		$namespace = null;
		if ($className !== null) {
			$namespace = self::parseNamespace($className);
		} else {
			if ($function instanceof Function_ && $function->namespacedName !== null) {
				$namespace = self::parseNamespace($function->namespacedName->toString());
			}
		}
		return new self(
			$stubFile,
			$namespace,
			$className,
			null,
			$function instanceof Function_ && $function->namespacedName !== null ? $function->namespacedName->toString() : ($function instanceof ClassMethod ? $function->name->toString() : null),
			$function instanceof ClassMethod && $className !== null
				? sprintf('%s::%s', $className, $function->name->toString())
				: ($function instanceof Function_ && $function->namespacedName !== null ? $function->namespacedName->toString() : null),
		);
	}

	public static function fromGlobalConstant(ReflectionConstant $constant): self
	{
		if ($constant->getNamespaceName() === '') {
			throw new ShouldNotHappenException('Namespace cannot be empty.');
		}

		return new self(
			$constant->getFileName(),
			$constant->getNamespaceName(),
			null,
			null,
			null,
			null,
		);
	}

	public static function createEmpty(): self
	{
		return new self(null, null, null, null, null, null);
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

	public function getClassName(): ?string
	{
		return $this->className;
	}

	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	public function getTraitName(): ?string
	{
		return $this->traitName;
	}

	public function getFunction(): ?string
	{
		return $this->function;
	}

	public function getMethod(): ?string
	{
		return $this->method;
	}

}
