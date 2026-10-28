<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15520Property;

interface SeasonResolver
{
	public function current(): string;
}

interface Category
{
	public function imageFor(string $season): ?string;
}

final class Presenter
{
	public function __construct(
		private SeasonResolver $seasonResolver,
	)
	{
	}

	/**
	 * @param list<?Category> $categories
	 * @return list<?string>
	 */
	public function images(array $categories): array
	{
		$images = [];
		foreach ($categories as $category) {
			$images[] = $category?->imageFor($this->seasonResolver->current());
		}

		return $images;
	}
}

final class WhilePresenter
{
	public function __construct(
		private SeasonResolver $seasonResolver,
	)
	{
	}

	/**
	 * @return list<?string>
	 */
	public function images(?Category $category): array
	{
		$images = [];
		while (count($images) < 10) {
			$images[] = $category?->imageFor($this->seasonResolver->current());
		}

		return $images;
	}
}

final class PropertyNamePresenter
{
	public function __construct(
		private string $name,
	)
	{
	}

	/**
	 * @param list<?object> $objects
	 * @return list<mixed>
	 */
	public function values(array $objects): array
	{
		$values = [];
		foreach ($objects as $object) {
			$values[] = $object?->{$this->name};
		}

		return $values;
	}
}
