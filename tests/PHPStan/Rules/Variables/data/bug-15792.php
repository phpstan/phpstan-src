<?php // lint >= 8.4

declare(strict_types=1);

namespace Bug15792;

abstract class BaseRecord
{
	abstract public string $name { get; }
}

final class Record extends BaseRecord
{
	public string $name = 'demo';
}

$record = new Record();
unset($record->name);

abstract class BaseSetRecord
{
	abstract public string $name { set; }
}

final class SetRecord extends BaseSetRecord
{
	public string $name = 'demo';
}

function (SetRecord $r): void {
	unset($r->name);
};

abstract class BaseMixed
{
	abstract public string $name {
		get;
		set => strtolower($value);
	}
}

final class MixedRecord extends BaseMixed
{
	public string $name = 'demo';
}

function (MixedRecord $r): void {
	unset($r->name);
};

abstract class BaseConcrete
{
	public string $name = 'demo' {
		get => $this->name;
	}
}

final class Concrete extends BaseConcrete
{
	public string $name = 'demo';
}

function (Concrete $r): void {
	unset($r->name);
};

abstract class IntermediateRecord extends BaseRecord
{
	public string $name = 'demo';
}

final class GrandchildRecord extends IntermediateRecord
{
}

function (GrandchildRecord $r): void {
	unset($r->name);
};

trait AbstractNameTrait
{
	abstract public string $name { get; }
}

abstract class BaseTraitRecord
{
	use AbstractNameTrait;
}

final class TraitRecord extends BaseTraitRecord
{
	public string $name = 'demo';
}

function (TraitRecord $r): void {
	unset($r->name);
};

class MiddleRecord extends BaseRecord
{
	public string $name = 'demo';
}

final class LeafRecord extends MiddleRecord
{
	final public string $name = 'leaf';
}

function (LeafRecord $r): void {
	unset($r->name);
};
