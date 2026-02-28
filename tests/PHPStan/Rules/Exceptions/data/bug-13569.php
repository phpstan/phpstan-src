<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13569;

enum ReactionType: string
{
	case EMOJI_HEART = '❤️';

	public static function tryFromName(string $name): ?self
	{
		try {
			return ReactionType::{$name};
		} catch (\Error) {
			return null;
		}
	}
}
