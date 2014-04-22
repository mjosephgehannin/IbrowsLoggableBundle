<?php

namespace Ibrows\LoggableBundle\Tests\Loggable;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Ibrows\LoggableBundle\Entity\LogMany2Many;
use Ibrows\LoggableBundle\Entity\LogParent;
use Ibrows\LoggableBundle\Listener\LoggableListener;
use Ibrows\LoggableBundle\Repository\LogMany2ManyRepository;
use Ibrows\LoggableBundle\Repository\LogParentRepository;
use Ibrows\LoggableBundle\Repository\LogRepository;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\Article;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\Comment;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\RelatedArticle;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\User;
use Ibrows\LoggableBundle\Util\Changer;
use Ibrows\LoggableBundle\Util\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractTest extends WebTestCase

{
    protected $testClass = 'Ibrows\LoggableBundle\Entity\Log';
    protected $testClassParent = 'Ibrows\LoggableBundle\Entity\LogParent';
    protected $testClassMany = 'Ibrows\LoggableBundle\Entity\LogMany2Many';


    protected $container = null;
    /**
     * @var Registry
     */
    protected $doctrine = null;
    /**
     * @var Changer
     */
    protected $changeservice = null;
    /**
     * @var Logger
     */
    protected $logservice = null;

    /**
     * @var EntityManager
     */
    protected $em = null;

    /**
     * @var LogRepository
     */
    protected $logrepo = null;
    /**
     * @var LogParentRepository
     */
    protected $logrepoParent = null;
    /**
     * @var LogMany2ManyRepository
     */
    protected $logrepoMany = null;

    protected function setUp()
    {
        parent::setUp();
        $client = static::createClient();
        $this->container = $client->getContainer();
        $this->doctrine = $this->container->get('doctrine');
        $this->changeservice = $this->container->get('ibrows_loggable.changer');
        $this->logservice = $this->container->get('ibrows_loggable.logger');
        $this->em = $this->doctrine->getManager();
        $this->logrepo = $this->doctrine->getManagerForClass($this->testClass)->getRepository($this->testClass);
        $this->logrepoParent = $this->doctrine->getManagerForClass($this->testClass)->getRepository($this->testClassParent);
        $this->logrepoMany = $this->doctrine->getManagerForClass($this->testClass)->getRepository($this->testClassMany);
        $this->cleanDB();
    }

    protected function tearDown()
    {
        $this->cleanDB();
        parent::tearDown();
    }

    private function cleanDB()
    {
        $this->logrepo->getLoggableListener()->setEnabled(false);
        $this->doctrine->getConnection()->executeQuery('SET foreign_key_checks = 0');
        $this->doctrine->getManager()->getFilters()->disable('softdeleteable');
        foreach ($this->logrepo->findAll() as $result) {
            $this->doctrine->getManager()->remove($result);
        }
        $this->doctrine->getManager()->flush();
        $fixturesClasses = array(
            get_class(new Comment()),
            get_class(new RelatedArticle()),
            get_class(new User()),
            get_class(new Article()),
        );
        foreach($fixturesClasses as $fixturesClass){
            $manager = $this->doctrine->getManagerForClass($fixturesClass);
            $repo = $manager->getRepository($fixturesClass);
            foreach ($repo->findAll() as $result) {
                $manager->remove($result);
            }
            $manager->flush();
            $manager->clear();
        }
        //make twice to be sure ???
        foreach($fixturesClasses as $fixturesClass){
            $manager = $this->doctrine->getManagerForClass($fixturesClass);
            $repo = $manager->getRepository($fixturesClass);
            foreach ($repo->findAll() as $result) {
                $manager->remove($result);
            }
            $manager->flush();
            $manager->clear();
        }
        $this->doctrine->getManager()->getFilters()->enable('softdeleteable');
        $this->doctrine->getConnection()->executeQuery('SET foreign_key_checks = 1');
        $this->logrepo->getLoggableListener()->setEnabled(true);
    }


}
