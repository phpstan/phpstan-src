<?php

namespace App\InterfaceExtends;

use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;

interface Foo extends DynamicThrowTypeExtensionProvider
{

}

interface Bar extends DynamicFunctionThrowTypeExtension
{

}

interface Lorem extends \PHPStan\Type\Type
{

}

interface Dolor extends ReflectionProvider
{

}

interface Ipsum extends ExtendedMethodReflection
{

}
