<?php

namespace Bug8850;

class Security
{
	public function getUser(): string
	{
		return 'foo';
	}
}

final class UserInSessionInRoleEndpointExtension
{
	use QueryBuilderHelperTrait;

	/** @var Security */
	private $security;

	public function __construct(
		Security $security
	) {
		$this->security = $security;
	}
}

trait QueryBuilderHelperTrait
{
	use OrganisationExtensionHelperTrait;
}

trait OrganisationExtensionHelperTrait
{
	use UserHelperTrait;

	public function getOrganisationIds(): void
	{
		$user = $this->getUser();
	}
}

trait UserHelperTrait
{
	public function getUser(): string
	{
		$user = $this->security->getUser();

		return 'foo';
	}
}
