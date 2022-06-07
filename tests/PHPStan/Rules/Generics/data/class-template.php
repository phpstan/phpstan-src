<?php

namespace ClassTemplateType;

/**
 * @template stdClass
 */
class Foo
{

}

/**
 * @template T of Zazzzu
 */
class Bar
{

}

/**
 * @template T of float
 */
class Baz
{

}

/**
 * @template T of baz
 */
class Lorem
{

}

/**
 * @phpstan-type ExportedAlias string
 * @template TypeAlias
 */
class Ipsum
{

}

/**
 * @phpstan-type LocalAlias string
 * @phpstan-import-type ExportedAlias from Ipsum as ImportedAlias
 * @template LocalAlias
 * @template ExportedAlias
 * @template ImportedAlias
 */
class Dolor
{

}

new /** @template stdClass */ class
{

};

new /** @template T of Zazzzu */ class
{

};

new /** @template T of float */ class
{

};

new /** @template T of baz */ class
{

};

new /** @template TypeAlias */ class
{

};

/**
 * @template T of 'string'
 */
class Sit
{

}

/**
 * @template T of 5
 */
class Amet
{

}
