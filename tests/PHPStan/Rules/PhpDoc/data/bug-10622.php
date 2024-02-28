<?php

namespace Bug10622;

class Model {}

/**
 * @template TKey of array-key
 * @template TModel
 *
 */
class SupportCollection {}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 */
class Collection
{
    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TModel, TKey): TMapValue  $callback
     * @return (TMapValue is Model ? self<TKey, TMapValue> : SupportCollection<TKey, TMapValue>)
     */
    public function map(callable $callback) {}
}
