<?php declare(strict_types = 1);

namespace Bug8774;

use function PHPStan\Testing\assertType;

class ModerateCtrl
{
	private const DISABLE_KEYS_AND_LABELS = [
		'DisablePosting' => 'Posting on forum and comments',
		'DisableAvatar' => 'Avatar and Custom Icon'
	];

	public static function handleModerate(): void
	{
		$summaryTemplates = [
			'PermissionID' => "Class changed from <b>'%s'</b> to <b>'%s'</b>.",
			'Example' => "An example format string containing 3 placeholders: %s, %s, %s",
			'Reset' => '%s reset.',
		];

		assertType("'%s reset.'", $summaryTemplates['Reset']);

		foreach (self::DISABLE_KEYS_AND_LABELS as $key => $label) {
			$summaryTemplates[$key] = "Disable $label status %s.";
		}

		assertType("'%s reset.'", $summaryTemplates['Reset']);

		$editSummary[] = sprintf($summaryTemplates['Reset'], 'Passkey');

		assertType("array{'Passkey reset.'}", $editSummary);
	}
}
