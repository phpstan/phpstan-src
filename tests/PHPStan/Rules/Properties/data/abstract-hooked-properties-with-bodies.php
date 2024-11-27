<?php declare(strict_types=1);

namespace AbstractHookedPropertiesWithBodies;

abstract class AbstractPerson
{
	public abstract string $name {
		get => $this->name;
		set => $this->name = $value;
	}

	public abstract string $lastName {
		get => $this->lastName;
		set => $this->lastName = $value;
	}

	public abstract string $middleName {
		get => $this->name;
		set;
	}

	public abstract string $familyName {
		get;
		set;
	}
}
