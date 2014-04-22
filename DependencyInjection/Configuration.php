<?php

namespace Ibrows\LoggableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('ibrows_loggable');
        $rootNode->children()
            ->arrayNode('changeable')->addDefaultsIfNotSet()->children()
                ->booleanNode('CatchException')->defaultFalse()->end()
                ->scalarNode('ChangeEntityClass')->defaultValue('Ibrows\LoggableBundle\Entity\ChangeSet')->end()
            ->end()->end()
            ->arrayNode('loggable')->addDefaultsIfNotSet()->children()
                ->scalarNode('DefaultLogEntryClass')->defaultValue('Ibrows\LoggableBundle\Entity\Log')->end()
                ->scalarNode('LogParentEntryClass')->defaultValue('Ibrows\LoggableBundle\Entity\LogMany2Many')->end()
                ->scalarNode('LogCollectionEntryClass')->defaultValue('Ibrows\LoggableBundle\Entity\LogParent')->end()
                ->booleanNode('Enabled')->defaultTrue()->end()
                ->booleanNode('UseOnySingleIds')->defaultTrue()->end()
                ->booleanNode('DefaultAllVersioned')->defaultTrue()->end()
            ->end()->end()
        ->end();

        return $treeBuilder;
    }
}