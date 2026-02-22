<?php declare(strict_types = 1);

namespace Bug13669;

final class Foo
{
	/**
	 * @var array<int, array<MailStatus::CODE_*, int>>
	 */
	private array $mailCounts;

	/** @var array<int, array<MailStatus::CODE_*>> */
	private array $sources;

	/** @param array<int, array<MailStatus::CODE_*>> $sources */
	private function __construct(array $sources)
	{
		$this->mailCounts = [];
		$this->sources = $sources;
	}


	public function countMailStates(): void
	{
		foreach ($this->sources as $templateId => $mails) {
			$this->mailCounts[$templateId] = [
				MailStatus::CODE_DELETED => 0,
				MailStatus::CODE_NOT_ACTIVE => 0,
				MailStatus::CODE_ACTIVE => 0,
				MailStatus::CODE_SIMULATION => 0,
			];

			foreach ($mails as $mail) {
				++$this->mailCounts[$templateId][$mail];
			}
		}
	}

}

final class MailStatus
{
	public const CODE_DELETED = -1;

	public const CODE_NOT_ACTIVE = 0;

	public const CODE_SIMULATION = 1;

	public const CODE_ACTIVE = 2;
}
