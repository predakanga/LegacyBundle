<?php

namespace TDW\LegacyBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class RouterDecorator implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    /**
     * @var Router
     */
    protected $decoratedRouter;
    /**
     * @var LegacyRouteLoader
     */
    protected $routeLoader;
    /**
     * @var string
     */
    protected $generateMode;
    /**
     * @var string
     */
    protected $matchMode;
    /**
     * @var array
     */
    protected $legacyRoutes;

    public function __construct(Router $decoratedRouter, LegacyRouteLoader $routeLoader, string $matchMode, string $generateMode)
    {
        $this->decoratedRouter = $decoratedRouter;
        $this->routeLoader = $routeLoader;
        $this->matchMode = $matchMode;
        $this->generateMode = $generateMode;
    }

    public function setContext(RequestContext $context): void
    {
        $this->decoratedRouter->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->decoratedRouter->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->decoratedRouter->getRouteCollection();
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if($this->generateMode === 'legacy') {
            throw new \Exception("Generation is not yet supported");
        } else {
            return $this->decoratedRouter->generate($name, $parameters, $referenceType);
        }
    }

    public function match($pathinfo): array
    {
        if($this->matchMode !== 'symfony') {
            // Create a temporary request to encapsulate this match, along with the superglobals
            $request = Request::createFromGlobals();
            $request->server->set('REQUEST_URI', $pathinfo);

            $legacyMatch = $this->matchLegacyRoute($request);
            if($legacyMatch) {
                return $legacyMatch;
            }
        }
        if($this->matchMode !== 'legacy') {
            return $this->decoratedRouter->match($pathinfo);
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s"', $pathinfo));
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request): array
    {
        if($this->matchMode !== 'symfony') {
            $legacyMatch = $this->matchLegacyRoute($request);
            if($legacyMatch) {
                return $legacyMatch;
            }
        }
        if($this->matchMode !== 'legacy') {
            return $this->decoratedRouter->matchRequest($request);
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s"', $request->getPathInfo()));
    }

    protected function matchLegacyRoute(Request $request)
    {
        $route = $this->findMatchingRoute($request);
        if(!$route) {
            return null;
        }

        $symfonyRoute = $this->getRouteCollection()->get($route['_route']);
        if(!$symfonyRoute) {
            return null;
        }

        // Apply all of the get variables as route parameters
        $toRet = $request->query->all();
        $toRet['_controller'] = $symfonyRoute->getDefault('_controller');
        $toRet['_route'] = $route['_route'];

        return $toRet;
    }

    protected function findMatchingRoute(Request $request)
    {
        if($this->legacyRoutes === null) {
            $this->loadLegacyRoutes();
        }

        $path = $request->getPathInfo();
        if(isset($this->legacyRoutes[$path])) {
            // Because this is sorted on generation, the first match is the most specific match
            foreach($this->legacyRoutes[$path] as $route) {
                $match = true;

                foreach($route['get'] as $key => $value) {
                    if(!$request->query->has($key) || $request->query->get($key) != $value) {
                        $match = false;
                    }
                }
                foreach($route['post'] as $key => $value) {
                    if(!$request->request->has($key) || $request->request->get($key) != $value) {
                        $match = false;
                    }
                }

                if($match) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir) {
        if($this->decoratedRouter instanceof WarmableInterface) {
            $this->decoratedRouter->warmUp($cacheDir);
        }
    }

    private function loadLegacyRoutes(): void {
        $this->legacyRoutes = $this->routeLoader->getRoutes();
    }
}
