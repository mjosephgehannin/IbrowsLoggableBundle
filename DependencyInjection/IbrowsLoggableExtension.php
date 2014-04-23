<?php

namespace Ibrows\LoggableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class IbrowsLoggableExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->registerContainerParametersRecursive($container, $this->getAlias(), $config);


        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');


        $loggableClass = ($container->getParameter('stof_doctrine_extensions.listener.loggable.class'));
        $r = new \ReflectionClass($loggableClass);
        if ($loggableClass == null
            || (!$r->isSubclassOf('Ibrows\LoggableBundle\Listener\LoggableListener')
                && $loggableClass != 'Ibrows\LoggableBundle\Listener\LoggableListener')
        ) {
            throw new \InvalidArgumentException('loggable Listener must be subclass of Ibrows\LoggableBundle\Listener\LoggableListener use:
            stof_doctrine_extensions:
                orm:
                    default:
                      softdeleteable: true
                      loggable: true
                class:
                    loggable: Ibrows\LoggableBundle\Listener\LoggableListener
            ');
        }
    }

    protected function registerContainerParametersRecursive(ContainerBuilder $container, $alias, $config)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($config),
            \RecursiveIteratorIterator::SELF_FIRST);

        foreach($iterator as $value){
            $path = array( );
            for($i = 0; $i <= $iterator->getDepth(); $i++){
                $path[] = $iterator->getSubIterator($i)->key();
            }
            $key = $alias . '.' . implode(".", $path);
            $container->setParameter($key, $value);
        }
    }
}
