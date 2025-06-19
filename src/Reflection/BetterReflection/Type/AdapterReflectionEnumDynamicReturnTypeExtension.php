<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function in_array;

#[AutowiredService]
final class AdapterReflectionEnumDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getClass(): string
	{
		return ReflectionEnum::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'getFileName',
			'getStartLine',
			'getEndLine',
			'getDocComment',
			'getReflectionConstant',
			'getParentClass',
			'getExtensionName',
			'getBackingType',
		], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if ($this->phpVersion->getVersionId() >= 80000) {
			return null;
		}

		if (in_array($methodReflection->getName(), ['getFileName', 'getExtensionName'], true)) {
			return new UnionType([
				new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]),
				new ConstantBooleanType(false),
			]);
		}

		if ($methodReflection->getName() === 'getDocComment') {
			return new UnionType([
				new StringType(),
				new ConstantBooleanType(false),
			]);
		}

		if (in_array($methodReflection->getName(), ['getStartLine', 'getEndLine'], true)) {
			return new IntegerType();
		}

		if ($methodReflection->getName() === 'getReflectionConstant') {
			return new UnionType([
				new ObjectType(ReflectionClassConstant::class),
				new ConstantBooleanType(false),
			]);
		}

		if ($methodReflection->getName() === 'getParentClass') {
			return new UnionType([
				new ObjectType(ReflectionClass::class),
				new ConstantBooleanType(false),
			]);
		}

		if ($methodReflection->getName() === 'getBackingType') {
			return new UnionType([
				new ObjectType(ReflectionNamedType::class),
				new NullType(),
			]);
		}

		return null;
	}

}
