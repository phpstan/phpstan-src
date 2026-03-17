<?php declare(strict_types = 1);

namespace Bug14096;

use function PHPStan\Testing\assertType;

class AbstractView {}
class App {}
interface ServerRequestInterface {}

class Test
{
	/**
	 * @template T of App
	 *
	 * @param \Closure(ServerRequestInterface): T $createAppFx
	 * @param \Closure(T): ServerRequestInterface $simulateRequestFx
	 *
	 * @return T
	 */
	protected function simulateAppCallback(\Closure $createAppFx, \Closure $simulateRequestFx): App
	{
		$appBase = $createAppFx(new class() implements ServerRequestInterface {});
		$request = $simulateRequestFx($appBase);

		$app = $createAppFx($request);

		return $app;
	}

	/**
	 * @template T of AbstractView
	 *
	 * @param \Closure(ServerRequestInterface): T $createViewFx
	 * @param \Closure(T): ServerRequestInterface $simulateRequestFx
	 *
	 * @return T
	 */
	protected function simulateViewCallback(\Closure $createViewFx, \Closure $simulateRequestFx): AbstractView
	{
		$view = null;
		$this->simulateAppCallback(static function (ServerRequestInterface $request) use ($createViewFx, &$view) {
			$view = $createViewFx($request);

			return new App();
		}, static function () use ($simulateRequestFx, &$view) {
			assertType('T of Bug14096\AbstractView (method Bug14096\Test::simulateViewCallback(), argument)|null', $view);

			return $simulateRequestFx($view);
		});

		assertType('T of Bug14096\AbstractView (method Bug14096\Test::simulateViewCallback(), argument)|null', $view);

		return $view;
	}
}
