<?php

namespace InvalidTypeInTypeAlias;

/**
 * @phpstan-type Foo array{}
 * @phpstan-type InvalidFoo what{}
 *
 * @psalm-type Foo array{}
 * @psalm-type InvalidFoo what{}
 */
class Foo
{



}
