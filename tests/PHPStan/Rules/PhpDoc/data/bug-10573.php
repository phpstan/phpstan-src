<?php

namespace Bug10573;

/**
 * @template TAttribute of string
 * @template TSubject of mixed
 */
abstract class Voter
{
	/**
	 * Determines if the attribute and subject are supported by this voter.
	 *
	 * @param string $attribute
	 * @param mixed $subject
	 *
	 * @phpstan-assert-if-true TSubject $subject
	 * @phpstan-assert-if-true TAttribute $attribute
	 *
	 * @return bool
	 */
	abstract protected function supports(string $attribute, $subject);
}

class Post {}

/**
 * @extends Voter<string, Post>
 */
class PostVoter extends Voter {
	function supports($attribute, $subject): bool
	{
		return $attribute === 'POST_READ' && $subject instanceof Post;
	}
}
