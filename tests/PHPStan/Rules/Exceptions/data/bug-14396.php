<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug14396;

enum Status {
    case A;
    case B;
    case C;
}

class Item {
    public function __construct(
        public ?Status $status
    ) {}
}

/**
* @param list<Item> $list
*/
function countAFromCollection(array $list): int
{
    $count = 0;

    foreach ($list as $item) {
        match ($item->status) {
            Status::A => ++$count,
            Status::B,
            Status::C,
            null => null,
        };
    }

    return $count;
}

function countAFromItem(Item $item): ?int {
	return match ($item->status) {
		Status::A => 1,
		Status::B,
		Status::C,
		null => null,
	};
}
