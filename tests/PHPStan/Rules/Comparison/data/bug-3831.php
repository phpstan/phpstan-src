<?php declare(strict_types = 1);

namespace Bug3831;

class Template {

	/** @var list<string> */
	public $footer = [];

	public function render() : string {
		$content = '';
		$this->footer = [];

		// dynamic method call - could modify $this->footer
		$this->{'compileSection'}();

		if (count($this->footer) > 0) {
			$content = str_replace('some', 'thing', $content);
		}
		return $content;
	}

	private function compileSection(): void {
		$this->footer[] = 'section-name';
	}

}
