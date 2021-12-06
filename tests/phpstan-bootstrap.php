<?php declare(strict_types = 1);

use ReturnTypes\Foo;
use ReturnTypes\FooAlias;
use TestAccessProperties\FooAccessProperties;
use TestAccessProperties\FooAccessPropertiesAlias;

class_alias(Foo::class, FooAlias::class, true);
class_alias(FooAccessProperties::class, FooAccessPropertiesAlias::class, true);
