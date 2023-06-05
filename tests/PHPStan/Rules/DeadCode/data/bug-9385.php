<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug9385;

final class WorkDayCalculator
{
    private const DATE_FORMAT = 'Y-m-d';
    private const WORKDAY = 1;
    private const HOLIDAY = 0;

    /** @var non-empty-array<non-empty-string, int<0, 1>> */
    private array $days = ['2023-06-01' => 1, '2023-06-02' => 1];

    public function calculate(DateTimeInterface $start, DateTimeInterface $end): int
    {
        if (self::HOLIDAY === $this->getDay($start)) {
            throw new RuntimeException(
                sprintf('Start day should be a work one: %s', $start->format(self::DATE_FORMAT))
            );
        }

        $days = 0;
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $day) {
            $days += $this->getDay($day);
        }
        return $days;
    }

    /**
     * @return self::WORKDAY|self::HOLIDAY
     */
    private function getDay(DateTimeInterface $day): int
    {
        return $this->days[$day->format(self::DATE_FORMAT)];
    }
}
