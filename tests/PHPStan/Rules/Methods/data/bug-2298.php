<?php

namespace Bug2298;

abstract class BitbucketDriver
{

	/**
	 * @var VcsDriver
	 */
	protected $fallbackDriver;

	protected $rootIdentifier;

}

class HgBitbucketDriver extends BitbucketDriver
{

	public function getRootIdentifier(): string
	{
		if ($this->fallbackDriver) {
			return $this->fallbackDriver->getRootIdentifier();
		}

		if (null === $this->rootIdentifier) {
			return $this->fallbackDriver->getRootIdentifier();
		}

		return 'foo';
	}

}

interface VcsDriver
{

	public function getRootIdentifier(): string;

}
