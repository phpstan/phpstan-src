<?php // lint >= 8.4

declare(strict_types=1);

namespace Bug15792Exceptions;

abstract class BaseRecord
{
	abstract public string $name { get; }
}

final class Record extends BaseRecord
{
	public string $name = 'demo';
}

abstract class BaseHookedRecord
{
	public string $name = 'demo' {
		get => $this->name;
	}
}

final class HookedRecord extends BaseHookedRecord
{
	public string $name = 'demo';
}

function (Record $r, HookedRecord $h): void {
	try {
		echo $r->name;
	} catch (\Exception $e) {
	}

	try {
		echo $h->name;
	} catch (\Exception $e) {
	}
};
