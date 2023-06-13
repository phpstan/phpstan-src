<?php declare(strict_types=1); // lint >= 8.1

namespace Bug8412;

use InvalidArgumentException;

enum Zustand: string
{
	case Failed = 'failed';
	case Pending = 'pending';
}

final class HelloWorld
{
	public readonly ?int $value;

	public function __construct(Zustand $zustand)
	{
		$this->value = match ($zustand) {
			Zustand::Failed => 1,
			Zustand::Pending => 2,
			default => throw new InvalidArgumentException('Unknown Zustand: ' . $zustand->value),
		};
	}
}
