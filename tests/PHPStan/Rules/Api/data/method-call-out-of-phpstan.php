<?php declare(strict_types = 1);

namespace App\MethodCall;

use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Debug\DumpTypeRule;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Php\ArrayKeyDynamicReturnTypeExtension;

class Foo
{

	public function doFoo(DumpTypeRule $rule): void
	{
		echo $rule->getNodeType(); // not allowed
	}

	public function doBar(ConstantArrayType $arrayType): void
	{
		$arrayType->getKeyTypes(); // @api above ConstantArrayType
	}

	public function doBaz(ArrayKeyDynamicReturnTypeExtension $ext, FunctionReflection $func): void
	{
		$ext->isFunctionSupported($func); // not allowed
	}

	public function doLorem(FileTypeMapper $fileTypeMapper): void
	{
		$fileTypeMapper->getResolvedPhpDoc('foo', null, null, null, '/** */'); // @api above method
	}

	public function doIpsum(TemplateType $type): void
	{
		echo $type->getName(); // @api above interface
	}

}
