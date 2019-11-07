<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Website;

class AddWebsiteCommand extends Command
{
    protected static $defaultName = 'app:website:add';
    
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a website to the list of managed websites.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the website')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Domain of the website')
            ->addOption('redirectTo', null, InputOption::VALUE_OPTIONAL, 'Where should this adress redirect to ?')
            ->addOption('mail', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Emails of peoples to alert in case of multiple failure.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $name = $input->getOption('name');
        $domain = $input->getOption('domain');
        $redirectTo = $input->getOption('redirectTo');
        $mailingList = $input->getOption('mail');

        if ($name && $domain) {
            $io->note(sprintf('Creating website with name %s and domain %s', $name, $domain));
            $newWebsite = new Website();
            $newWebsite->setName($name);
            $newWebsite->setDomain($domain);
            $newWebsite->setRedirectTo($redirectTo);
            $newWebsite->setMailingList($mailingList);
            $this->em->persist($newWebsite);
            $this->em->flush();
        } else {
            $io->warning('Name and domain are required. Pass help to have a complete description of the command.');
            return 1;
        }

        $io->success('Website added successfully.');

        return 0;
    }
}
