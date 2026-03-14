<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12798;

interface Colorable
{
	public function color(): string;
}

trait HasColors
{
    /** @return array<string|int, string> */
    public static function colors(): array {
        return array_reduce(self::cases(), function (array $colors, self $case) {
            $key = is_subclass_of($case, \BackedEnum::class) ? $case->value : $case->name;
            $color = is_subclass_of($case, Colorable::class) ? $case->color() : 'gray';

            $colors[$key] = $color;
            return $colors;
        }, []);
    }
}

enum AlertLevelBacked: int implements Colorable
{
	use HasColors;

	case Low      = 1;
	case Medium   = 2;
	case Critical = 3;

	public function color(): string
    {
        return match ($this) {
            self::Low      => 'green',
            self::Medium   => 'yellow',
            self::Critical => 'red',
        };
    }
}

enum AlertLevel
{
	use HasColors;

	case Low;
	case Medium;
	case Critical;
}
