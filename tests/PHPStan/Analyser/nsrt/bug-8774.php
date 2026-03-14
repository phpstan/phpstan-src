<?php declare(strict_types = 1);

namespace Bug8774;

use function PHPStan\Testing\assertType;

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

		assertType("array{PermissionID: 'Class changed from <b>\\'%s\\'</b> to <b>\\'%s\\'</b>.', Reset: '%s reset.', DisablePosting?: 'Disable Avatar and Custom Icon status %s.'|'Disable Posting on forum and comments status %s.', DisableAvatar?: 'Disable Avatar and Custom Icon status %s.'|'Disable Posting on forum and comments status %s.'}", $summaryTemplates);
		assertType("'%s reset.'", $summaryTemplates['Reset']);
	}
}
