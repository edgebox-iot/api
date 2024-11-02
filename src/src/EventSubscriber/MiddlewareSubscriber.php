<?php

namespace App\EventSubscriber;

use App\Attribute\RunMiddleware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

class MiddlewareSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {

        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
        $attributes = $reflectionMethod->getAttributes();

        $run_middleware_instance= null;

        foreach ($attributes as $attribute) {

            if ($attribute->getName() !== RunMiddleware::class) {
                continue;
            }

            if ($run_middleware_instance == null) {
                $run_middleware_instance = $attribute->newInstance();
                $middleware = $run_middleware_instance;
            }

            $methodName = $middleware->name;
            
            $middleware_result = null;

            if (method_exists($controller[0], $methodName)) {
                $middleware_result = $controller[0]->$methodName();
            }

            // If return of middleware is Response or RedirectResponse, return it
            if ($middleware_result && ($middleware_result instanceof Response || $middleware_result instanceof RedirectResonse)) {
                $event->setController(fn() => $middleware_result);
            }
        }
    }
}