<?php

namespace TDW\LegacyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder() {
        $builder = new TreeBuilder();
        $root = $builder->root('legacy');

        $root
            ->children()
                ->arrayNode('router')->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->enumNode('match')->values(['legacy', 'symfony', 'both'])->defaultValue('both')->end()
                        ->enumNode('generate')->values(['legacy', 'symfony'])->defaultValue('symfony')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}