<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug9939;

use function PHPStan\Testing\assertType;

enum Combinator
{
	case NEXT_SIBLING;
	case CHILD;
	case FOLLOWING_SIBLING;

    public function getText(): string
    {
        return match ($this) {
            self::NEXT_SIBLING => '+',
            self::CHILD => '>',
            self::FOLLOWING_SIBLING => '~',
        };
    }
}

/**
 * @template T of string|\Stringable|array<string|\Stringable>|Combinator|null
 */
class CssValue
{
	/**
	 * @param T $value
	 */
	public function __construct(private readonly mixed $value)
	{
	}

    /**
     * @return T
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
		assertType('T of array<string|Stringable>|Bug9939\Combinator|string|Stringable|null (class Bug9939\CssValue, argument)', $this->value);

        if ($this->value instanceof Combinator) {
			assertType('T of Bug9939\Combinator (class Bug9939\CssValue, argument)', $this->value);
            return $this->value->getText();
        }

		assertType('T of array<string|Stringable>|string|Stringable|null (class Bug9939\CssValue, argument)', $this->value);

        if (\is_array($this->value)) {
			assertType('T of array<string|Stringable> (class Bug9939\CssValue, argument)', $this->value);
            return implode($this->value);
        }

		assertType('T of string|Stringable|null (class Bug9939\CssValue, argument)', $this->value);

        return (string) $this->value;
    }
}
