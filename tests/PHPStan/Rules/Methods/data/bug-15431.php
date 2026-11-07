<?php declare(strict_types = 1);

namespace Bug15431;

/**
 * @template T of array<mixed>
 */
class WeakAnalysingBoxedArray
{
    /** @var T */
    public array $value = [];
}

/**
 * @template TKey of object|array<mixed>
 * @template TValue of object|array<mixed>
 * @template TOwner of object
 */
class WeakAnalysingMap
{
    /**
     * @template T of TKey|TValue
     *
     * @param (T is object ? T : WeakAnalysingBoxedArray<T>) $value
     *
     * @return T
     */
    protected function unboxValue(object $value)
    {
        return $value instanceof WeakAnalysingBoxedArray
            ? $value->value
            : $value;
    }

    /**
     * @param (TValue is object ? TValue : WeakAnalysingBoxedArray<TValue>) $v
     *
     * @return TValue|null
     */
    public function unboxValueOnly($v)
    {
        return $this->unboxValue($v);
    }
}
