<?php

namespace TDW\LegacyBundle\Routing;

use Doctrine\Common\Annotations\CachedReader;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use TDW\LegacyBundle\Util;

class LegacyRouteLoader implements ServiceSubscriberInterface, WarmableInterface {
    /**
     * @var RouterInterface
     */
    protected $symfonyRouter;
    /**
     * @var string
     */
    protected $cacheDir;
    /**
     * @var bool
     */
    protected $isDebug;
    /**
     * @var ContainerInterface
     */
    protected $locator;

    public function __construct(ContainerInterface $locator, RouterInterface $symfonyRouter, string $cacheDir, bool $isDebug) {
        $this->locator = $locator;
        $this->symfonyRouter = $symfonyRouter;
        $this->cacheDir = $cacheDir;
        $this->isDebug = $isDebug;
    }

    public function getRoutes(): array {
        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR . 'legacy_routes.php';
        $routeCache = new ConfigCache($cachePath, $this->isDebug);

        if(!$routeCache->isFresh()) {
            $symfonyRoutes = $this->symfonyRouter->getRouteCollection();
            $symfonyResources = $symfonyRoutes->getResources();

            $routeBatches = [];
            foreach($symfonyResources as $resource) {
                if($resource instanceof FileResource && strtolower(pathinfo($resource->getResource(), PATHINFO_EXTENSION)) === 'php') {
                    $routeBatches[] = $this->loadRoutes($resource);
                }
            }

            $routes = [];
            if(\count($routeBatches)) {
                $routes = array_merge(...$routeBatches);
            }

            $cacheTemplate = <<<'CODE'
<?php
return unserialize(%s);
CODE;
            $routeCache->write(sprintf($cacheTemplate, var_export(serialize($routes), true)), $symfonyResources);
        }

        return require $cachePath;
    }

    protected function loadRoutes(FileResource $resource): array {
        $toRet = [];
        /**
         * @var CachedReader $reader
         */
        $reader = $this->locator->get('annotation_reader');
        foreach(Util::findClasses($resource->getResource()) as $fqcn) {
            $reflClass = new \ReflectionClass($fqcn);

            foreach($reflClass->getMethods() as $reflMethod) {
                foreach($reader->getMethodAnnotations($reflMethod) as $annotation) {
                    if($annotation instanceof LegacyRoute) {
                        /**
                         * @var $symfonyRoute Route
                         */
                        $symfonyRoute = $reader->getMethodAnnotation($reflMethod, Route::class);
                        if(!$symfonyRoute || !$symfonyRoute->getName()) {
                            throw new \LogicException('LegacyRoute annotation must only be used together with a named Route annotation');
                        }
                        $toRet[] = $this->createRoute($annotation, $symfonyRoute->getName());
                    }
                }
            }
        }

        return $toRet;
    }

    protected function createRoute(LegacyRoute $annotation, string $routeName): array {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array {
        return ['annotation_reader'];
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir) {
        // Temporarily override the main cache dir
        $origCacheDir = $this->cacheDir;
        $this->cacheDir = $cacheDir;

        // And load the routes
        try {
            $this->getRoutes();
        } finally {
            $this->cacheDir = $origCacheDir;
        }
    }
}