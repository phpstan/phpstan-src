<?php

namespace Bug13013;

class OfferTemplateOverviewVM
{
	/**
	 * @var array<int, list<mixed>>
	 */
	public array $mailsGroupedByTemplate;

	/**
	 * @var array<int, array<MailStatus::CODE_*, int>>
	 */
	protected array $mailCounts;

	private function __construct()
	{
		$this->mailsGroupedByTemplate = [];
		$this->mailCounts = [];
	}

	public function countMailStates(): void
	{
		foreach ($this->mailsGroupedByTemplate as $templateId => $mails) {
			$this->mailCounts[$templateId] = [
				MailStatus::notActive()->code => 0,
				MailStatus::simulation()->code => 0,
				MailStatus::active()->code => 0,
			];
		}
	}
}

final class MailStatus
{
	private const CODE_NOT_ACTIVE = 0;

	private const CODE_SIMULATION = 1;

	private const CODE_ACTIVE = 2;

	/**
	 * @var self::CODE_*
	 */
	public int $code;

	public string $name;

	public string $description;

	/**
	 * @param self::CODE_* $status
	 */
	public function __construct(int $status, string $name, string $description)
	{
		$this->code = $status;
		$this->name = $name;
		$this->description = $description;
	}

	public static function notActive(): self
	{
		return new self(self::CODE_NOT_ACTIVE, _('Pausiert'), _('Es findet kein Mailversand an Kunden statt'));
	}

	public static function simulation(): self
	{
		return new self(self::CODE_SIMULATION, _('Simulation'), _('Wenn Template zugewiesen, werden im Simulationsmodus E-Mails nur in der Datenbank gespeichert und nicht an den Kunden gesendet'));
	}

	public static function active(): self
	{
		return new self(self::CODE_ACTIVE, _('Aktiv'), _('Wenn Template zugewiesen, findet Mailversand an Kunden statt'));
	}

}
