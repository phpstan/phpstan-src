<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14138;

/**
 * @template T of array
 */
abstract class AbstractApiData
{
    public function __construct(
        /** @var T */
        protected array $data
    ) {}

    /**
     * @return T
     */
    public function getData(): array
    {
        return $this->data;
    }
}


/**
 * @extends AbstractApiData<array{
 *     foo: int,
 *     bar: int,
 * }>
 */
class Foo extends AbstractApiData {}

function testing(): void {
    $a = new Foo(["foo" => 1]);
}
