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
 * @template T of int
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
 * @template TypeAlias
 */
class Ipsum
{

}

new /** @template stdClass */ class
{

};

new /** @template T of Zazzzu */ class
{

};

new /** @template T of int */ class
{

};

new /** @template T of baz */ class
{

};

new /** @template TypeAlias */ class
{

};

interface BaseViewData
{

}

/**
 * @template T
 */
class BaseModel
{

}

/**
 * @template TViewData of BaseViewData
 * @template TModel of BaseModel<TViewData>
 */
class BaseRepository
{

}
