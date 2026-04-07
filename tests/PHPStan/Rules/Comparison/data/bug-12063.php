<?php declare(strict_types=1);

namespace Bug12063;

use BadFunctionCallException;

final class View
{
    public function existingMethod(): void
    {
    }
}

final class TwigExtension
{
    private View $viewFunctions;

    public function __construct(View $viewFunctions)
    {
        $this->viewFunctions = $viewFunctions;
    }

    public function iterateFunctions(): void
    {
        $functionMappings = [
            'i_exist' => 'existingMethod',
			'i_dont_exist' => 'nonExistingMethod'
        ];

        $functions = [];
        foreach ($functionMappings as $nameFrom => $nameTo) {
            $callable = [$this->viewFunctions, $nameTo];
            if (!is_callable($callable)) {
                throw new BadFunctionCallException("Function $nameTo does not exist in view functions");
            }
        }
    }
}
