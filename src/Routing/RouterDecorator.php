<?php

namespace TDW\LegacyBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class RouterDecorator implements RouterInterface, RequestMatcherInterface {
    /**
     * @var Router
     */
    protected $decoratedRouter;
    /**
     * @var LegacyRouteLoader
     */
    protected $routeLoader;

    public function __construct(Router $decoratedRouter, LegacyRouteLoader $routeLoader, string $generateMode, string $fetchMode) {
        $this->decoratedRouter = $decoratedRouter;
        $this->routeLoader = $routeLoader;
    }

    public function setContext(RequestContext $context): void {
        $this->decoratedRouter->setContext($context);
    }

    public function getContext(): RequestContext {
        return $this->decoratedRouter->getContext();
    }

    public function getRouteCollection(): RouteCollection {
        return $this->decoratedRouter->getRouteCollection();
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string {
        return $this->decoratedRouter->generate($name, $parameters, $referenceType);
    }

    public function match($pathinfo): array {
        return $this->decoratedRouter->match($pathinfo);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request): array {
        return $this->decoratedRouter->matchRequest($request);
    }
}