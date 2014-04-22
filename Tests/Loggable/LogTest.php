<?php

namespace Ibrows\LoggableBundle\Tests\Loggable;

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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogTest extends AbstractTest
{

    public function testCreateMany2Many()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $user = new User();
        $user->setName('Username');
        $user->addArticle($art0);
        $this->em->persist($user);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(3, $logs);
        foreach ($logs as $log) {
            if ($log instanceof LogMany2Many) {
                $this->assertEquals($log->getInverseClass(), get_class($art0));
                $this->assertEquals($log->getInverseId(), $art0->getId());
                $this->assertEquals($log->getObjectClass(), get_class($user));
                $this->assertEquals($log->getObjectId(), $user->getId());
            } else if ($log->getObjectClass() == get_class($user)) {
                $data = $log->getData();
                $this->assertEquals($log->getObjectId(), $user->getId());
                $this->assertArrayHasKey('name', $data);
                $this->assertEquals('Username', $data['name']);
            } else {
                if ($log->getObjectClass() == get_class($art0)) {
                    $data = $log->getData();
                    $this->assertEquals($log->getObjectId(), $art0->getId());
                    $this->assertArrayHasKey('title', $data);
                    $this->assertEquals('Title', $data['title']);
                }
            }
        }

    }

    public function testEditMany2Many()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $art1 = new Article();
        $art1->setTitle('Title1');
        $art2 = new Article();
        $art2->setTitle('Title2');
        $user = new User();
        $user->setName('Username');
        $user1 = new User();
        $user1->setName('Username2');
        $user2 = new User();
        $user2->setName('Username3');
        $this->em->persist($user);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->persist($art0);
        $this->em->persist($art1);
        $this->em->persist($art2);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(6, $logs);
        $user->addArticle($art0);
        $user->addArticle($art1);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(8, $logs);
        $logs = $this->logrepo->getLogsByObject($user);
        $this->assertCount(3, $logs);
        $manylog = array_pop($logs);
        $this->assertInstanceOf(get_class(new LogMany2Many()), $manylog);
        $this->assertEquals($manylog->getInverseClass(), get_class($art1));
        $this->assertEquals($manylog->getInverseId(), $art1->getId());
        $this->assertEquals($manylog->getObjectClass(), get_class($user));
        $this->assertEquals($manylog->getObjectId(), $user->getId());
        $manylog = array_pop($logs);
        $this->assertInstanceOf(get_class(new LogMany2Many()), $manylog);
        $this->assertEquals($manylog->getInverseClass(), get_class($art0));
        $this->assertEquals($manylog->getInverseId(), $art0->getId());
        $this->assertEquals($manylog->getObjectClass(), get_class($user));
        $this->assertEquals($manylog->getObjectId(), $user->getId());


        $logs = $this->logrepoMany->getLogsByObject($art1);
        $this->assertCount(1, $logs);
        $manylog = array_pop($logs);
        $this->assertInstanceOf(get_class(new LogMany2Many()), $manylog, true, true);
        $this->assertEquals($manylog->getInverseClass(), get_class($art1));
        $this->assertEquals($manylog->getInverseId(), $art1->getId());
        $this->assertEquals($manylog->getObjectClass(), get_class($user));
        $this->assertEquals($manylog->getObjectId(), $user->getId());

        $logs = $this->logrepoMany->getLogsByObject($art0);
        $this->assertCount(1, $logs);
        $manylog = array_pop($logs);
        $this->assertInstanceOf(get_class(new LogMany2Many()), $manylog, true, true);
        $this->assertEquals($manylog->getInverseClass(), get_class($art0));
        $this->assertEquals($manylog->getInverseId(), $art0->getId());
        $this->assertEquals($manylog->getObjectClass(), get_class($user));
        $this->assertEquals($manylog->getObjectId(), $user->getId());


    }

    public function testCreate()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(1, $logs);
        $log = array_shift($logs);
        $data = $log->getData();
        $this->assertEquals($log->getObjectId(), $art0->getId());
        $this->assertEquals($log->getObjectClass(), get_class($art0));
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('Title', $data['title']);
    }

    public function testEdit()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(1, $logs);
        $art0->setTitle('Title2');
        $this->em->persist($art0);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(2, $logs);
        $log = array_pop($logs);
        $data = $log->getData();
        $dataold = $log->getOldData();
        $this->assertEquals($log->getObjectId(), $art0->getId());
        $this->assertEquals($log->getObjectClass(), get_class($art0));
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('Title2', $data['title']);
        $this->assertArrayHasKey('title', $dataold);
        $this->assertEquals('Title', $dataold['title']);

    }
    public function testHistoricalView()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $testsize = 10;
        for($i=1;$i<$testsize;$i++){
            $logs = $this->logrepo->findAll();
            $this->assertCount($i, $logs);
            $art0->setTitle('Title'.$i);
            $this->em->persist($art0);
            $this->em->flush();
            sleep(1);
        }
        $logs = $this->logrepo->findBy(array(),array('version'=>'ASC'));
        $this->assertCount($testsize, $logs);
        $number = $testsize - (int)($testsize/2);
        $log = $logs[$number];
        $artHistorical = $this->logrepo->historicalView($art0,$log->getLoggedAt());
        $this->assertEquals('Title'.(--$number), $artHistorical->getTitle());
    }

    public function testRemove()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $artid = $art0->getId();
        $logs = $this->logrepo->findAll();
        $this->assertCount(1, $logs);
        $this->em->remove($art0);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(2, $logs);
        $log = array_pop($logs);
        $data = $log->getData();
        $dataold = $log->getOldData();
        $this->assertEquals($log->getObjectId(), $artid);
        $this->assertEquals($log->getObjectClass(), get_class($art0));
        $this->assertEquals($log->getAction(), LoggableListener::ACTION_REMOVE);
        $this->assertNull($data);
        $this->assertNull($dataold);
    }

    public function testCreateMany2One()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $comment = new Comment();
        $art1 = new RelatedArticle();
        $art1->setTitle('Title');
        $art1->setContent('Content');
        $comment->setArticle($art1);
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $this->em->persist($comment);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(3, $logs);
        foreach ($logs as $log) {
            if ($log instanceof LogParent) {
                $this->assertEquals($log->getChildClass(), get_class($comment));
                $this->assertEquals($log->getChildId(), $comment->getId());
                $this->assertEquals($log->getObjectClass(), get_class($art1));
                $this->assertEquals($log->getObjectId(), $art1->getId());

            } else if ($log->getObjectClass() == get_class($art1)) {
                $data = $log->getData();
                $this->assertEquals($log->getObjectId(), $art1->getId());
                $this->assertArrayHasKey('title', $data);
                $this->assertArrayHasKey('content', $data);
                $this->assertEquals('Title', $data['title']);
                $this->assertEquals('Content', $data['content']);
            } else if ($log->getObjectClass() == get_class($comment)) {
                $data = $log->getData();
                $this->assertEquals($log->getObjectId(), $comment->getId());
                $this->assertArrayHasKey('subject', $data);
                $this->assertArrayHasKey('message', $data);
                $this->assertEquals('Subject', $data['subject']);
                $this->assertEquals('Message', $data['message']);
            }
        }
    }

    public function testEditMany2One()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art1 = new RelatedArticle();
        $art1->setTitle('Title');
        $art1->setContent('Content');
        $art2 = new RelatedArticle();
        $art2->setTitle('Title2');
        $art2->setContent('Content2');
        $comment = new Comment();
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $comment2 = new Comment();
        $comment2->setSubject('Subject2');
        $comment2->setMessage('Message2');
        $this->em->persist($art1);
        $this->em->persist($art2);
        $this->em->persist($comment);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->assertCount(4, $this->logrepo->findAll());
        $this->assertCount(1, $this->logrepo->getLogsByObject($art1));
        $comment->setArticle($art1);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(6, $logs);
        $logs = $this->logrepo->getLogsByObject($art1);
        $this->assertCount(2, $logs);
        $comment->setArticle(null);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(8, $logs);
        $logs = $this->logrepo->getLogsByObject($art1);
        $this->assertCount(3, $logs);
        $parentlog = array_pop($logs);
        $this->assertEquals($parentlog->getAction(), LoggableListener::ACTION_REMOVE);
        $this->assertEquals($parentlog->getChildClass(), get_class($comment));
        $this->assertEquals($parentlog->getChildId(), $comment->getId());
        $this->assertEquals($parentlog->getObjectClass(), get_class($art1));
        $this->assertEquals($parentlog->getObjectId(), $art1->getId());

    }

    public function testRevert()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $logs = $this->logrepo->findAll();
        $this->assertCount(1, $logs);
        $art0->setTitle('newTitle');
        $this->em->persist($art0);
        $this->em->flush();
        $this->em->clear();
        $logs = $this->logrepo->findAll();
        $this->assertCount(2, $logs);
        $log = array_pop($logs);
        $this->logrepo->revertTo($log);
        $artclass = get_class($art0);
        $artid = $art0->getId();
        $art0 = $this->em->find($artclass, $artid);
        $this->assertEquals('Title', $art0->getTitle());

        $this->em->remove($art0);
        $this->em->flush();
        $art0 = $this->em->find($artclass, $artid);
        $this->assertNull($art0);
        $logs = $this->logrepo->findAll();
        $this->assertCount(2, $logs);
        $log = array_pop($logs);
        $this->assertEquals($log->getAction(), LoggableListener::ACTION_REMOVE);
        $this->logrepo->revertTo($log);
        $art0 = $this->em->find($artclass, $artid);
        $this->assertNotNull($art0);


    }


}
