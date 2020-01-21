<?php
declare(strict_types=1);

namespace App;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use StopSpam\Plugin as StopSpam;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        $this->addPlugin(StopSpam::class);
    }

    public function middleware(MiddlewareQueue $middleware)
    {
        return $middleware->add(new RoutingMiddleware($this));
    }
}
