<?php declare(strict_types = 1);

namespace Bug14177;

class HelloWorld
{
	public function placeholderToEditor(string $html): void
	{
		$result = preg_replace_callback(
			'~\[image\\sid="(\\d+)"(?:\\shref="([^"]*)")?(?:\\sclass="([^"]*)")?]~',
			function (array $matches): string {
				$id = (int) $matches[1];

				$replacement = sprintf(
					'<img src="%s"%s/>',
					$id,
					array_key_exists(3, $matches) ? sprintf(' class="%s"', $matches[3]) : '',
				);

				return array_key_exists(2, $matches) && $matches[2] !== ''
					? sprintf('<a href="%s">%s</a>', $matches[2], $replacement)
					: $replacement;
			},
			$html,
		);
	}
}
