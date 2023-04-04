<?php

namespace Bug8473;

/**
 * @template TKey of int
 * @template TValue
 * @psalm-type PagesType = object{
 *     test: int,
 * }
 */
class Paginator
{

}

/** @extends Paginator<int, AccountEntity> */
class AccountCollection extends Paginator
{
}

class AccountEntity
{}
