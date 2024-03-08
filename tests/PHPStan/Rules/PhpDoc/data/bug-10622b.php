<?php

namespace Bug10622;

/**
 * @template T of array<mixed>
 */
class FooBoxedArray
{
    /** @var T */
    private $value;

    /**
     * @param T $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * @return T
     */
    public function get(): array
    {
        return $this->value;
    }
}

/**
 * @template TKey of object|array<mixed>
 * @template TValue of object|array<mixed>
 */
class FooMap
{
    /**
     * @var array<int, array{
     *     \WeakReference<(TKey is object ? TKey : FooBoxedArray<TKey>)>,
     *     \WeakReference<(TValue is object ? TValue : FooBoxedArray<TValue>)>
     * }>
     */
    protected $weakKvByIndex = [];

    /**
     * @template T of TKey|TValue
     *
     * @param T $value
     *
     * @return (T is object ? T : FooBoxedArray<T>)
     */
    protected function boxValue($value): object
    {
        return is_array($value)
            ? new FooBoxedArray($value)
            : $value;
    }

    /**
     * @template T of TKey|TValue
     *
     * @param (T is object ? T : FooBoxedArray<T>) $value
     *
     * @return T
     */
    protected function unboxValue(object $value)
    {
        return $value instanceof FooBoxedArray
            ? $value->get()
            : $value;
    }
}
