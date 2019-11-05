<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Website;

class WebsiteRemoveCommand extends Command
{
    protected static $defaultName = 'app:website:remove';
    
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove a website to the list of managed websites.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the website')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $name = $input->getOption('name');

        if ($name) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf('Do you want to remove the website %s ? (y/n) ', $name), false);

            if (!$helper->ask($input, $output, $question)) {
                $io->error($name . ' was not removed. Exiting.');
                return 1;
            }
            
            $website = $this->em->getRepository(Website::class)->findOneByName($name);

            $this->em->remove($website);
            $this->em->flush();
        } else {
            $io->warning('Name is required. Pass help to have a complete description of the command.');
            return 1;
        }

        $io->success('Website removed successfully.');

        return 0;
    }
}
