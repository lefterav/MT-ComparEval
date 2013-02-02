<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\CliRouter,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	private $consoleMode = false;

	public function __construct( $consoleMode ) {
		$this->consoleMode = $consoleMode;
	}

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		if( $this->consoleMode ) {
			$router[] = new CliRouter(); 
		} else {
			$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
			$router[] = new Route('api/sentences', 'Api:Sentences:default');
			$router[] = new Route('tasks/<id1>-<id2>/compare', 'Tasks:compare');
			$router[] = new Route('tasks/<id>/detail', 'Tasks:detail');
			$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		}		

		return $router;
	}

}