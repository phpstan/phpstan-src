<?php // lint >= 8.1

namespace Bug6394;

enum EntryType: string
{
	case CREDIT = 'credit';
	case DEBIT = 'debit';
}

class Foo
{

	public function getType(): EntryType
	{
		return $this->type;
	}

	public function getAmount(): int
	{
		return match($this->getType()) {
			EntryType::DEBIT => 1,
			EntryType::CREDIT => 2,
		};
	}
}
