<?php
/**
 * Created by iBROWS AG.
 * User: marcsteiner
 * Date: 27.02.14
 * Time: 17:12
 */

namespace Ibrows\LoggableBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder)
    {

        $configs = $containerBuilder->getParameter('ibrows_loggable.loggable');
        $logger = $containerBuilder->getDefinition('stof_doctrine_extensions.listener.loggable');
        foreach($configs as $key => $value){
            $logger->addMethodCall('set'.$key,array($value));
        }
        $containerBuilder->setAlias('ibrows_loggable.listener', 'stof_doctrine_extensions.listener.loggable');

    }
}
