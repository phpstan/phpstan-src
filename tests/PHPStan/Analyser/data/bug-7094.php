<?php declare(strict_types = 1);

namespace Bug7094;

/**
 * @template T of array<string, mixed>
 */
trait AttributeTrait
{
    /**
     * @param key-of<T> $key
     */
    public function hasAttribute(string $key): bool
    {
        $attr = $this->getAttributes();

        return isset($attr[$key]);
    }

    /**
     * @template K of key-of<T>
     * @param K $key
     * @param T[K] $val
     */
    public function setAttribute(string $key, $val): void
    {
        $attr = $this->getAttributes();
        $attr[$key] = $val;
        $this->setAttributes($attr);
    }

    /**
     * @template K of key-of<T>
     * @param K $key
     * @return T[K]|null
     */
    public function getAttribute(string $key)
    {
        return $this->getAttributes()[$key] ?? null;
    }

    /**
     * @param key-of<T> $key
     */
    public function unsetAttribute(string $key): void
    {
        $attr = $this->getAttributes();
        unset($attr[$key]);
        $this->setAttributes($attr);
    }
}

/**
 * @phpstan-type Attrs array{foo?: string, bar?: 5|6|7, baz?: bool}
 */
class Foo {
    /** @use AttributeTrait<Attrs> */
	use AttributeTrait;

	/** @return Attrs */
	public function getAttributes(): array
	{
		return [];
	}

	/** @param Attrs $attr */
	public function setAttributes(array $attr): void
	{
	}
}


$f = new Foo;
$f->setAttribute('unknown-attr-err', 3);
$f->setAttribute('foo', 3); // invalid type should error!
$f->setAttribute('bar', 3); // invalid type should error!
$f->setAttribute('bar', 5); // valid!
$f->setAttribute('foo', 5); // NOT VALID
$f->getAttribute('unknown-attr-err');
