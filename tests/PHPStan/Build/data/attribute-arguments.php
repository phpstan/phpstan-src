<?php

namespace AttributeArguments;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService(name: 'foo')]
class Foo
{

}

#[AutowiredService('foo')]
class Bar
{

}
