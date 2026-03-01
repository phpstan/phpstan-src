<?php // lint >= 8.3

declare(strict_types = 1);

namespace Pr5105;

enum ReactionType: string
{
	case EMOJI_HEART = '❤️';

	public static function tryFromName1(string $name): ?self
	{
		try {
			return ReactionType::{$name};
		} catch (\RuntimeException) {
			return null;
		}
	}

	public static function tryFromName2(string $name): ?self
	{
		if ($name !== 'EMOJI_HEART') {
			return null;
		}

		try {
			return ReactionType::{$name};
		} catch (\Error) {
			return null;
		}
	}
}
