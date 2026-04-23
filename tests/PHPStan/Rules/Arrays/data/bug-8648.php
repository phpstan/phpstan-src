<?php declare(strict_types = 1);

namespace Bug8648;

class Data
{
}

class HelloWorld
{
	/**
	 * @var mixed[]
	 */
	private $data;

	/**
	 * @return mixed[]
	 */
    public function processData(): array
    {
        $this->data['foo'] = [
            'id' => 'some_id',
        ];

        foreach (['a' => 'aa', 'b' => 'bb', 'c' => 'cc'] as $type => $value) {
            $this->data['foo']['bar'][] = [
                'type' => $type,
                'value' => $value,
            ];
        }

		return $this->data;
    }
}
