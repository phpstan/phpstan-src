<?php

namespace AppClassExtends;

use PHPStan\Analyser\MutatingScope;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;

class Foo extends FileTypeMapper
{

}

class Bar extends ObjectType
{

}

class MyScope extends MutatingScope
{

}
