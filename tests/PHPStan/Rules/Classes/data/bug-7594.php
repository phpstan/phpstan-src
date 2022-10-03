<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7594;

class HelloWorld
{

	public const ABILITY_BUILD = 0;
	public const ABILITY_MINE = 1;
	public const ABILITY_DOORS_AND_SWITCHES = 2;
	public const ABILITY_OPEN_CONTAINERS = 3;
	public const ABILITY_ATTACK_PLAYERS = 4;
	public const ABILITY_ATTACK_MOBS = 5;
	public const ABILITY_OPERATOR = 6;
	public const ABILITY_TELEPORT = 7;
	public const ABILITY_INVULNERABLE = 8;
	public const ABILITY_FLYING = 9;
	public const ABILITY_ALLOW_FLIGHT = 10;
	public const ABILITY_INSTABUILD = 11; //???
	public const ABILITY_LIGHTNING = 12; //???
	private const ABILITY_FLY_SPEED = 13;
	private const ABILITY_WALK_SPEED = 14;
	public const ABILITY_MUTED = 15;
	public const ABILITY_WORLD_BUILDER = 16;
	public const ABILITY_NO_CLIP = 17;

	public const NUMBER_OF_ABILITIES = 18;

	/**
	 * @param bool[] $boolAbilities
	 * @phpstan-param array<self::ABILITY_*, bool> $boolAbilities
	 */
	public function __construct(
		private array $boolAbilities,
	){}

	/**
	 * Returns a list of abilities set/overridden by this layer. If the ability value is not set, the index is omitted.
	 * @return bool[]
	 * @phpstan-return array<self::ABILITY_*, bool>
	 */
	public function getBoolAbilities() : array{ return $this->boolAbilities; }

	public static function decode(int $setAbilities, int $setAbilityValues) : self{
		$boolAbilities = [];
		for($i = 0; $i < self::NUMBER_OF_ABILITIES; $i++){
			if($i === self::ABILITY_FLY_SPEED || $i === self::ABILITY_WALK_SPEED){
				continue;
			}
			if(($setAbilities & (1 << $i)) !== 0){
				$boolAbilities[$i] = ($setAbilityValues & (1 << $i)) !== 0;
			}
		}

		return new self($boolAbilities);
	}
}
