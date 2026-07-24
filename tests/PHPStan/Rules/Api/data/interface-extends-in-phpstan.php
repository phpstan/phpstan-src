<?php

namespace PHPStan\InterfaceExtends;

use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;

interface Foo extends ReflectionProviderProvider
{

}

interface Bar extends DynamicFunctionThrowTypeExtension
{

}
