<?php
namespace Ibrows\LoggableBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\AppKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeCommand extends ContainerAwareCommand
{

    /* (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('ibrows:loggable:change')->setDescription('apply all ready changes');
        $this->addOption('nowdate', null, InputOption::VALUE_OPTIONAL, 'taken as curernt time');
    }

    /* (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $now = new \DateTime($input->getOption('nowdate'));
        $output->writeln("ibrows:loggable:change");
        $output->writeln("-----------");
        $this->applyChanges($output, $now);

    }


    /**
     * @param OutputInterface $output
     * @return array with skipped and ready counts
     */
    protected function applyChanges($output, $now)
    {
        $changer = $this->getContainer()->get('ibrows_loggable.changer');
        $changer->setOutput($output);
        $changer->applyChanges($now);
    }


}
