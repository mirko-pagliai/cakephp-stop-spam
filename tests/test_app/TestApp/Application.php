<?php

namespace App;

use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;
use StopSpam\Plugin as StopSpam;

class Application extends BaseApplication
{
    public function bootstrap()
    {
        $this->addPlugin(StopSpam::class);
    }

    public function middleware($middlewareQueue)
    {
        return $middlewareQueue->add(new RoutingMiddleware($this));
    }
}
