<?php

namespace MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('media_api')
            ->children()
                ->scalarNode('upload_max_filesize')->defaultValue('50M')->end()
            ->end();
        return $treeBuilder;
    }
}
