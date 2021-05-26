<?php

namespace PHPStan\InterfaceExtends;

use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;

interface Foo extends DynamicThrowTypeExtensionProvider
{

}

interface Bar extends DynamicFunctionThrowTypeExtension
{

}
