<?php declare(strict_types = 1);

namespace Bug3951;

interface Subject{}
interface Filterable extends Subject{}

class A{
	/**
	 * @param TSubject $subject
	 *
	 * @return TSubject|null
	 *
	 * @template TSubject as Subject
	 */
	protected function filterSubject(Subject $subject) : ?Subject
	{
		if (!$subject instanceof Filterable) {
			return $subject;
		}

		return $this->filter($subject);
	}

	/**
	 * @param TSubject $subject
	 *
	 * @return TSubject|null
	 *
	 * @template TSubject as Filterable
	 */
	public function filter(Filterable $subject) : ?Filterable
	{
		return (rand(0,1) ? null : $subject);
	}
}
