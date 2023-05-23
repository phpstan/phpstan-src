<?php

namespace Bug4061;

abstract class AbstractArticle
{
	public static function createFromOcArticle(): void
	{
		$isSingleArticle = self::isWithinSingleArticle();
	}

	private static function isWithinSingleArticle(): bool
	{
		return SingleArticle::class === get_called_class();
	}
}

final class SingleArticle extends AbstractArticle
{
	public static function call(): void
	{
		self::createFromOcArticle();
	}

}
