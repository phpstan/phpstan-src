<?php // lint >= 8.1

namespace Bug14100;

class Locality
{

	public function getParent(): ?self
	{
		return null;
	}

	public function isShownInFilter(): bool
	{
		return false;
	}

	public function getId(): int
	{
		return 1;
	}

	public function getType(): LocalityType
	{
		return LocalityType::CITY;
	}

}

enum LocalityType
{

	case REGION;
	case RESORT;
	case MOUNTAINS;
	case ISLAND;
	case WINE_AREA;
	case CITY;

	public function contains(self $s): bool
	{
		return false;
	}

}

/** @param list<Locality> $localities */
function foo(array $localities): void
{
	usort($localities, static fn (Locality $a, Locality $b): int => (int) ($b->getParent() !== null) <=> (int) ($a->getParent() !== null)
		?: (int) $a->getType()->contains(LocalityType::REGION) <=> (int) $b->getType()->contains(LocalityType::REGION)
			?: (int) $b->getType()->contains(LocalityType::CITY) <=> (int) $a->getType()->contains(LocalityType::CITY)
				?: (int) $b->getType()->contains(LocalityType::RESORT) <=> (int) $a->getType()->contains(LocalityType::RESORT)
					?: (int) $b->getType()->contains(LocalityType::MOUNTAINS) <=> (int) $a->getType()->contains(LocalityType::MOUNTAINS)
						?: (int) $b->getType()->contains(LocalityType::ISLAND) <=> (int) $a->getType()->contains(LocalityType::ISLAND)
							?: (int) $b->getType()->contains(LocalityType::WINE_AREA) <=> (int) $a->getType()->contains(LocalityType::WINE_AREA)
								?: (int) $b->isShownInFilter() <=> (int) $a->isShownInFilter()
									?: $a->getId() <=> $b->getId()
	);
}
