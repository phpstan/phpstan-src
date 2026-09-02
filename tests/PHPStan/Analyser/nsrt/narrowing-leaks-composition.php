<?php declare(strict_types = 1);

namespace NarrowingLeaksComposition;

use function PHPStan\Testing\assertType;

class RatingValue
{

	public function getValue(): int
	{
		return 1;
	}

}

class Rating
{

	public function getValue(): RatingValue
	{
		return new RatingValue();
	}

}

class Repo
{

	public function findByPostAndUser(int $postId, int $userId): ?Rating
	{
		return null;
	}

}

class Presenter
{

	private Repo $repo;

	public function isUserLoggedIn(): bool
	{
		return true;
	}

	public function isCompetitionPost(): bool
	{
		return true;
	}

	public function run(int $postId, int $userId): void
	{
		$userRating = $this->isUserLoggedIn()
			? $this->repo->findByPostAndUser($postId, $userId)
			: null;
		$a = $userRating?->getValue()->getValue() ?? 0;
		assertType('NarrowingLeaksComposition\Rating|null', $userRating);
		$b = [
			'alreadyRated' => $userRating !== null,
			'value' => $userRating?->getValue()->getValue() ?? 0,
		];
		// evaluating `$userRating !== null` as an array item must not narrow
		// the continuing scope
		assertType('NarrowingLeaksComposition\Rating|null', $userRating);
		$c = $userRating?->getValue()->getValue() ?? 0;

		$isCompetitionPost = $this->isCompetitionPost();
		// the ternary above must not keep the remembered call pinned to true
		assertType('bool', $this->isUserLoggedIn());
		$d = $isCompetitionPost && $this->isUserLoggedIn()
			? 1
			: 0;
		assertType('0|1', $d);
	}

}
