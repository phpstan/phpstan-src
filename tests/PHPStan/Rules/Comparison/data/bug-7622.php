<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7622;

final class AnalyticsKpiType
{
    public const SESSION_COUNT = 'session_count';
    public const MISSION_COUNT = 'mission_count';
    public const SESSION_GAP = 'session_gap';
}

class HelloWorld
{

    /**
     * @param AnalyticsKpiType::* $currentKpi
     * @param int[]               $filteredMemberIds
     */
    public function test(string $currentKpi, array $filteredMemberIds): int
    {
        return match ($currentKpi) {
            AnalyticsKpiType::SESSION_COUNT => 12,
            AnalyticsKpiType::MISSION_COUNT => 5,
            AnalyticsKpiType::SESSION_GAP => 14,
        };
    }
}
