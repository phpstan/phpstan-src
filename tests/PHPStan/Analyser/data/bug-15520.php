<?php declare(strict_types = 1);

namespace Bug15520;

interface Category
{
	public function imageFor(string $season): ?string;
}

final class Presenter
{
	/**
	 * @param list<?Category> $categories
	 * @return list<?string>
	 */
	public function images(array $categories): array
	{
		$images = [];
		foreach ($categories as $category) {
			$images[] = $category?->imageFor($this->undefinedMethod($this->undefinedProperty), 1 + 'x');
		}

		return $images;
	}

	/**
	 * @return list<?string>
	 */
	public function whileLoop(?Category $category): array
	{
		$images = [];
		while (count($images) < 10) {
			$images[] = $category?->imageFor($this->undefinedWhile());
		}

		return $images;
	}

	/**
	 * @return list<?string>
	 */
	public function doWhileLoop(?Category $category): array
	{
		$images = [];
		do {
			$images[] = $category?->imageFor($this->undefinedDoWhile());
		} while (count($images) < 10);

		return $images;
	}

	/**
	 * @return list<?string>
	 */
	public function forLoop(?Category $category): array
	{
		$images = [];
		for ($i = 0; $i < 10; $i++) {
			$images[] = $category?->imageFor($this->undefinedFor());
		}

		return $images;
	}

	/**
	 * @param list<?Category> $categories
	 * @return list<?string>
	 */
	public function closureArgument(array $categories): array
	{
		$images = [];
		foreach ($categories as $category) {
			$images[] = $category?->imageFor((function (): string {
				return $this->undefinedInClosure();
			})());
			$images[] = $category?->imageFor((fn (): string => $this->undefinedInArrowFunction())());
		}

		return $images;
	}

	/**
	 * @param list<?object> $objects
	 * @return list<mixed>
	 */
	public function nullsafePropertyFetch(array $objects, string $name): array
	{
		$values = [];
		foreach ($objects as $object) {
			$values[] = $object?->{$name . $this->undefinedInPropertyName()};
		}

		return $values;
	}

	/**
	 * @param list<?Category> $categories
	 * @return list<?string>
	 */
	public function nestedAndChained(array $categories, string $method): array
	{
		$images = [];
		foreach ($categories as $category) {
			$images[] = $category?->imageFor($category?->imageFor($this->undefinedInNested()) ?? '');
			$images[] = $category?->{$method . $this->undefinedInMethodName()}();
		}

		return $images;
	}
}
