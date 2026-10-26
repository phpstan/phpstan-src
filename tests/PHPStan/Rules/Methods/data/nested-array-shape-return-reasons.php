<?php // lint >= 8.0

namespace NestedArrayShapeReturnReasons;

final class ChildData
{

	public function __construct(private int $age, private ?bool $bedEntitlement)
	{
	}

}

final class CapacityHelperTest
{

	/**
	 * @return array<string, array{list<array{int, int, int, list<int>, bool}>, int, list<ChildData>, bool}>
	 */
	public static function capacitiesData(): array
	{
		$case = static fn (
			array $capacities,
			int $adults,
			array $children = [],
			bool $hasAnimals = false,
		): array => [
			array_values($capacities),
			$adults,
			array_map(
				static fn (array|int $child): ChildData => is_array($child)
					? new ChildData($child[0], $child[1])
					: new ChildData($child, null),
				array_values($children),
			),
			$hasAnimals,
		];

		$child = static fn (int $age, ?bool $withBed = null): array => [$age, $withBed];

		$filteredCapacity = static fn (
			int $maxRooms,
			int $totalBeds,
			int $minPeople = 1,
			array $bedsMaxAges = [],
			bool $hasPetsAllowed = false,
		): array => [
			$maxRooms,
			$totalBeds,
			$minPeople,
			array_values($bedsMaxAges),
			$hasPetsAllowed,
			true,
		];

		$discardedCapacity = static fn (
			int $maxRooms,
			int $totalBeds,
			int $minPeople = 1,
			array $bedsMaxAges = [],
			bool $hasPetsAllowed = false,
		): array => [
			$maxRooms,
			$totalBeds,
			$minPeople,
			array_values($bedsMaxAges),
			$hasPetsAllowed,
			false,
		];

		return [
			'case 0' => $case(
				capacities: [
					$filteredCapacity(maxRooms: 1, totalBeds: 2),
				],
				adults: 2,
			),
			'case 1' => $case(
				capacities: [
					$discardedCapacity(maxRooms: 1, totalBeds: 2),
				],
				adults: 3,
			),
			'case 2' => $case(
				capacities: [
					$discardedCapacity(maxRooms: 1, totalBeds: 2),
					$filteredCapacity(maxRooms: 1, totalBeds: 3),
				],
				adults: 3,
			),
			'case 3' => $case(
				capacities: [
					$filteredCapacity(maxRooms: 1, totalBeds: 2, bedsMaxAges: [12, 10]),
				],
				adults: 1,
				children: [$child(11), $child(4, false)],
			),
			'case 4' => $case(
				capacities: [
					$filteredCapacity(maxRooms: 2, totalBeds: 4, minPeople: 3, hasPetsAllowed: true),
					$discardedCapacity(maxRooms: 1, totalBeds: 4, bedsMaxAges: [16, 10, 5]),
				],
				adults: 2,
				children: [$child(15), $child(3, true), 8],
				hasAnimals: true,
			),
			'case 5' => $case(
				capacities: [
					$discardedCapacity(maxRooms: 1, totalBeds: 3, minPeople: 2, bedsMaxAges: [13, 10, 6]),
				],
				adults: 4,
				children: [$child(2)],
			),
			'case 6' => $case(
				capacities: [
					$filteredCapacity(maxRooms: 3, totalBeds: 6, minPeople: 5, bedsMaxAges: [17, 12, 10]),
					$filteredCapacity(maxRooms: 2, totalBeds: 5, minPeople: 4, bedsMaxAges: [3, 0]),
				],
				adults: 5,
				children: [$child(17), $child(12, true), $child(9, false), 1],
			),
			'case 7' => $case(
				capacities: [
					$discardedCapacity(maxRooms: 2, totalBeds: 2, minPeople: 7, bedsMaxAges: [8]),
				],
				adults: 2,
				children: [$child(5), 5, 7],
				hasAnimals: true,
			),
			'case 8' => $case(
				capacities: [
					$filteredCapacity(maxRooms: 2, totalBeds: 3, minPeople: 8, bedsMaxAges: [11, 11]),
				],
				adults: 3,
				children: [$child(10, true), $child(13)],
			),
		];
	}

}
