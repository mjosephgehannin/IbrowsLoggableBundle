<?php

namespace Ibrows\LoggableBundle;

use Ibrows\LoggableBundle\DependencyInjection\CompilerPass;
use Ibrows\LoggableBundle\Listener\LoggableListener;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IbrowsLoggableBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new CompilerPass());
    }

    /**
     * LoggableListener as last listener aka shizzle my nizze
     */
    public function boot()
    {
        //shizzle my nizzle
        $eventManager = $this->container->get('doctrine')->getManager()->getEventManager();
        /* @var ContainerAwareEventManager $eventManager */
        $allListeners = $eventManager->getListeners('onFlush');
        foreach($allListeners as $listener){
            if($listener instanceof LoggableListener){
                $eventManager->removeEventListener('onFlush', $listener);
                $eventManager->addEventListener('onFlush', $listener);
            }
        }
    }

}
