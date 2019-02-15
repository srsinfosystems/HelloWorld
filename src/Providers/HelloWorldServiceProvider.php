<?php
 
namespace HelloWorld\Providers;
  

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
 
class HelloWorldServiceProvider extends RouteServiceProvider
{
 
    /**
     * Register the service provider.
     */
 
    public function register()
    {
 		 $this->getApplication()->register(RouteServiceProvider::class);
    }

    public function map(Router $router)
    {
 		$router->get('hello','HelloWorld\Controllers\ContentController@sayHello');
    }
}

?>