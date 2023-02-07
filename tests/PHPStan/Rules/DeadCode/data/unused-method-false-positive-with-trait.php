<?php // lint >= 8.1

namespace UnusedMethodFalsePositiveWithTrait;

use ReflectionEnum;

enum LocalOnlineReservationTime: string
{

	use LabeledEnumTrait;

	case MORNING = 'morning';
	case AFTERNOON = 'afternoon';
	case EVENING = 'evening';

	public static function getPeriodForHour(string $hour): self
	{
		$hour = self::hourToNumber($hour);

		throw new \Exception('Internal error');
	}

	private static function hourToNumber(string $hour): int
	{
		return (int) str_replace(':', '', $hour);
	}

}

trait LabeledEnumTrait
{

	use EnumTrait;

}

trait EnumTrait
{

	/**
	 * @return list<static>
	 */
	public static function getDeprecatedEnums(): array
	{
		static $cache = [];
		if ($cache === []) {
			$reflection = new ReflectionEnum(self::class);
			$cases = $reflection->getCases();

			foreach ($cases as $case) {
				$docComment = $case->getDocComment();
				if ($docComment === false || !str_contains($docComment, '@deprecated')) {
					continue;
				}
				$cache[] = self::from($case->getBackingValue());
			}
		}

		return $cache;
	}

	public function isDeprecated(): bool
	{
		return $this->equalsAny(self::getDeprecatedEnums());
	}

	public function equalsAny(...$that): bool
	{
		return in_array($this, $that, true);
	}

}
