<?php declare(strict_types=1);

namespace NonAbstractHookedPropertiesInAbstractClass;

abstract class AbstractPerson
{
	public string $name { get; set; }

	public string $lastName { get; set; }
}

abstract class Foo
{

	public string $name {
		get {

		}

		set {

		}
	}

	public string $name2 {
		get;

		set {

		}
	}

	public string $name3;

}
