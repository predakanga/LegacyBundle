<?php

namespace TDW\LegacyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class LegacyExtension extends ConfigurableExtension {
    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container) {
        if($mergedConfig['router']['enabled']) {
            $loader = new XmlFileLoader($container, new FileLocator('@LegacyBundle/Resources/config'));
            $loader->load('services.xml');

            $definition = $container->getDefinition('legacy.router');
            $definition->replaceArgument(2, $mergedConfig['router']['match']);
            $definition->replaceArgument(3, $mergedConfig['router']['generate']);
        }
    }
}