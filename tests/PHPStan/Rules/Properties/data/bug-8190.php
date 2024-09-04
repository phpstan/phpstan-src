<?php

namespace Bug8190;

/**
 * @phpstan-type OwnerBackup array{name: string, isTest?: bool}
 */
class ClassA
{
	/** @var OwnerBackup */
	public array $ownerBackup;

	/**
	 * @param OwnerBackup|null $ownerBackup
	 */
	public function __construct(?array $ownerBackup)
	{
		$this->ownerBackup = $ownerBackup ?? [
			'name' => 'Deleted',
		];
	}


	/**
	 * @param OwnerBackup|null $ownerBackup
	 */
	public function setOwnerBackup(?array $ownerBackup): void
	{
		$this->ownerBackup = $ownerBackup ?: [
			'name' => 'Deleted',
		];
	}

	/**
	 * @param OwnerBackup|null $ownerBackup
	 */
	public function setOwnerBackupWorksForSomeReason(?array $ownerBackup): void
	{
		$this->ownerBackup = $ownerBackup !== null ? $ownerBackup : [
			'name' => 'Deleted',
		];
	}

	/**
	 * @param OwnerBackup|null $ownerBackup
	 */
	public function setOwnerBackupAlsoWorksForSomeReason(?array $ownerBackup): void
	{
		if ($ownerBackup) {
			$this->ownerBackup = $ownerBackup;
		} else {
			$this->ownerBackup = [
				'name' => 'Deleted',
			];
		}
	}
}
