<?php

namespace Ibrows\LoggableBundle\Tests\Loggable;

use Doctrine\ORM\Query;
use Ibrows\LoggableBundle\Command\ChangeCommand;
use Ibrows\LoggableBundle\Repository\ChangeSetRepository;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\Article;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\Comment;
use Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity\RelatedArticle;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTest extends AbstractTest
{

    protected $changeClass = 'Ibrows\LoggableBundle\Entity\ChangeSet';

    /**
     * @var ChangeSetRepository
     */
    protected $changeRepo = null;

    public function testDontEditDirect()
    {
        $this->assertCount(0, $this->logrepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $this->assertCount(1, $this->logrepo->findAll());
        $this->assertEquals('Title', $art0->getTitle());
        $art0->setTitle('Title New');
        $art0->setScheduledChangeDate(new \DateTime("+ 100 days"));
        $this->em->persist($art0);
        $this->em->flush();
        $this->assertCount(1, $this->logrepo->findAll());
        $this->assertEquals('Title', $art0->getTitle());
    }

    public function testAddChangeSet()
    {
        $this->assertCount(0, $this->changeRepo->findAll());
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $this->assertCount(1, $this->logrepo->findAll());
        $this->assertEquals('Title', $art0->getTitle());
        $art0->setTitle('Title New');
        $art0->setScheduledChangeDate(new \DateTime("+ 1 second"));
        $this->em->persist($art0);
        $this->em->flush();
        $changes = $this->changeRepo->findAll();
        $this->assertCount(1, $changes);
        $change = array_pop($changes);
        $data = $change->getData();
        $dataold = $change->getOldData();
        $this->assertEquals($change->getObjectId(), $art0->getId());
        $this->assertEquals($change->getObjectClass(), get_class($art0));
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('Title New', $data['title']);
        $this->assertArrayNotHasKey('scheduledChangeDate', $data);
        $this->assertArrayHasKey('title', $dataold);
        $this->assertEquals('Title', $dataold['title']);
    }

    public function testApplyChangeSet()
    {
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $art0->setTitle('Title New');
        $art0->setScheduledChangeDate(new \DateTime("+ 1 second"));
        $this->em->persist($art0);
        $this->em->flush();
        $changes = $this->changeRepo->findAll();
        $this->assertCount(1, $changes);
        $change = array_pop($changes);
        $return = $this->changeRepo->applyChangeSet($change);
        $this->assertFalse($return);
        $this->assertEquals('Title', $art0->getTitle());
        sleep(1);
        $return = $this->changeRepo->applyChangeSet($change);
        $this->assertNotEquals(false, $return);
        $this->assertEquals('Title New', $return->getTitle());
    }

    public function testDontEditDirectMany2One()
    {
        $comment = new Comment();
        $art1 = new RelatedArticle();
        $art1->setTitle('Title');
        $art1->setContent('Content');
        $art2 = new RelatedArticle();
        $art2->setTitle('Title2');
        $art2->setContent('Content2');
        $comment->setArticle($art1);
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $this->em->persist($comment);
        $this->em->persist($art1);
        $this->em->persist($art2);
        $this->em->flush();
        $comment->setArticle($art2);
        $comment->setScheduledChangeDate(new \DateTime("+ 100 day"));
        $this->em->persist($comment);
        $this->em->flush();
        $this->assertEquals($art1, $comment->getArticle());
    }

    /* dont work, should it ?
    public function testDontEditDirectMany2Many()
    {
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
        $user->addArticle($art0);
        $user->addArticle($art1);
        $user->setScheduledChangeDate(new \DateTime("+ 100 day"));
        $this->em->persist($user);
        $this->em->flush();
        $this->assertCount(0, $user->getArticles());
    }*/

    public function testApplyChangeSetMany2One()
    {
        $comment = new Comment();
        $art1 = new RelatedArticle();
        $art1->setTitle('Title');
        $art1->setContent('Content');
        $art2 = new RelatedArticle();
        $art2->setTitle('Title2');
        $art2->setContent('Content2');
        $comment->setArticle($art1);
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $this->em->persist($comment);
        $this->em->persist($art1);
        $this->em->persist($art2);
        $this->em->flush();
        $comment->setArticle($art2);
        $comment->setScheduledChangeDate(new \DateTime("+ 1 second"));
        $this->em->persist($comment);
        $this->em->flush();
        $changes = $this->changeRepo->findAll();
        $this->assertCount(1, $changes);
        $change = array_pop($changes);
        $return = $this->changeRepo->applyChangeSet($change);
        $this->assertFalse($return);
        $this->assertEquals('Title', $comment->getArticle()->getTitle());
        sleep(1);
        $return = $this->changeRepo->applyChangeSet($change);
        $this->assertNotEquals(false, $return);
        $this->assertEquals('Title2', $return->getArticle()->getTitle());
    }


    public function testApplyChangeSetOne2Many()
    {
        $this->logrepo->getLoggableListener()->setUsername('testUser');
        $art1 = new RelatedArticle();
        $art1->setTitle('Title');
        $art1->setContent('Content');
        $comment = new Comment();
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $art1->addComment($comment);
        $this->em->persist($comment);
        $this->em->persist($art1);
        $this->em->flush();
        $comment = new Comment();
        $comment->setSubject('Subject');
        $comment->setMessage('Message');
        $comment->setScheduledChangeDate(new \DateTime("+ 1 second"));
        $art1->addComment($comment);
        $this->em->persist($comment);
        $this->assertCount(2, $art1->getComments());
        $this->em->flush();
        $this->assertCount(1, $art1->getComments());
        $this->em->clear();

        $art1 = $this->em->find(get_class($art1),$art1->getId());
        $this->assertCount(1, $art1->getComments());
        sleep(1);
        $this->changeservice->applyChanges();
        $this->assertEquals(1,$this->changeservice->getProcessed());
        $this->assertCount(2, $art1->getComments());
        $this->em->flush();
        $this->em->clear();
        $art1 = $this->em->find(get_class($art1),$art1->getId());
        $this->assertCount(2, $art1->getComments());
        $comment = $art1->getComments()->first();
        $this->assertEquals('Subject',$comment->getSubject());
        $this->assertEquals('Message',$comment->getMessage());
    }


    public function testApplyCommandWithUserName()
    {
        $this->logrepo->getLoggableListener()->setUsername('testUser');
        $art0 = new Article();
        $art0->setTitle('Title');
        $this->em->persist($art0);
        $this->em->flush();
        $art0->setTitle('Title New');
        $art0->setScheduledChangeDate(new \DateTime("+ 1 second"));
        $this->em->persist($art0);
        $this->em->flush();
        $changes = $this->changeRepo->findAll();
        $this->assertCount(1, $changes);


        $application = new Application(self::$kernel);
        $application->add(new ChangeCommand());
        $command = $application->find('ibrows:loggable:change');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), array('verbosity' => 2));

        $this->assertRegExp('/Processed 0 changes/', $commandTester->getDisplay());
        sleep(1);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), array('verbosity' => 2));
        $this->assertRegExp('/Processed 1 changes/', $commandTester->getDisplay());

        $logs = $this->logrepo->getLogsByObject($art0);
        $scheduledlog = array_pop($logs);
        $this->assertEquals('testUser', $scheduledlog->getUserName());


    }

    protected function setUp()
    {
        parent::setUp();
        $this->changeRepo = $this->doctrine->getManagerForClass($this->changeClass)->getRepository($this->changeClass);
    }


    protected function tearDown()
    {
        parent::tearDown();
        foreach ($this->changeRepo->findAll() as $result) {
            $this->doctrine->getManager()->remove($result);
        }
        $this->doctrine->getManager()->flush();
    }
}
