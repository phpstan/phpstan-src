<?php // lint >= 8.0

namespace GetTemplateType;

use Generator;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class Foo
{

	public function doFoo(Type $type): void
	{
		$type->getTemplateType(Generator::class, 'TSend');
		$type->getTemplateType(Generator::class, 'TSendd');
	}

	public function doBar(ObjectType $type): void
	{
		$type->getTemplateType(Generator::class, 'TSend');
		$type->getTemplateType(Generator::class, 'TSendd');
	}

	public function doNamed(Type $type): void
	{
		$type->getTemplateType(templateTypeName: 'TSendd', ancestorClassName: Generator::class);
	}

}
