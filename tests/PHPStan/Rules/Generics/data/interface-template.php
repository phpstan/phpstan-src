<?php

namespace InterfaceTemplateType;

/**
 * @template stdClass
 */
interface Foo
{

}

/**
 * @template T of Zazzzu
 */
interface Bar
{

}

/**
 * @template T of int
 */
interface Baz
{

}

/**
 * @template TypeAlias
 */
interface Lorem
{

}

interface BaseViewData
{

}

/**
 * @template T
 */
interface BaseModel
{

}

/**
 * @template TViewData of BaseViewData
 * @template TModel of BaseModel<TViewData>
 */
interface BaseRepository
{

}
