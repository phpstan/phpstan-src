<?php declare(strict_types = 1);

namespace Bug8774;

class ModerateCtrl
{
	private const DISABLE_KEYS_AND_LABELS = [
		'DisablePosting' => 'Posting on forum and comments',
		'DisableAvatar' => 'Avatar and Custom Icon',
	];

	public static function handleModerate(): void
	{
		$summaryTemplates = [
			'PermissionID' => "Class changed from <b>'%s'</b> to <b>'%s'</b>.",
			'Reset' => '%s reset.',
		];

		foreach (self::DISABLE_KEYS_AND_LABELS as $key => $label) {
			$summaryTemplates[$key] = "Disable $label status %s.";
		}

		echo sprintf($summaryTemplates['Reset'], 'foo');
	}
}
