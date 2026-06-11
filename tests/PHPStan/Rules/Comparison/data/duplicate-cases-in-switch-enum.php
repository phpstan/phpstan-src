<?php // lint >= 8.1

namespace DuplicateCasesInSwitchEnum;

enum Status: string
{

	case Active = 'active';
	case Inactive = 'inactive';
	case Pending = 'pending';

}

class Foo
{

	public function doFoo(Status $status): void
	{
		switch ($status) {
			case Status::Active:
				break;
			case Status::Inactive:
				break;
			case Status::Active:
				break;
		}
	}

	public function noDuplicates(Status $status): void
	{
		switch ($status) {
			case Status::Active:
				break;
			case Status::Inactive:
				break;
			case Status::Pending:
				break;
		}
	}

}
