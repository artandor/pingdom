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

class PingWebsiteCommand extends Command
{
    protected static $defaultName = 'app:website:ping';
    
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Ping one or more websites included in database.')
            ->addArgument('websites', InputArgument::IS_ARRAY, 'Argument description')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Ping all websites to refresh status.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targettedWebsites = $input->getArgument('websites');
        
        $websiteRepo = $this->em->getRepository(Website::class);
        
        
        
        $websitesToPing = $websiteRepo->findBy(
            ['name' => $targettedWebsites],
        );

        
        
        if ($websitesToPing) {
            foreach($websitesToPing as $website) {
                $io->note(sprintf('Pinging %s', $website));
                $website = $this->callWebsite($website);
                $this->em->persist($website);
                $this->em->flush();
                $output->writeln('');
                $io->success('Status updated.');
            }
        } else if ($input->getOption('all')) {
            $io->note('Requesting all websites in database.');
            foreach($websiteRepo->findAll() as $website) {
                $io->note(sprintf('Pinging %s', $website));
                $website = $this->callWebsite($website);
                $this->em->persist($website);
                $this->em->flush();
                $output->writeln('');
                $io->success('Status updated.');
            }
        } else {
            $existingWebsites = $websiteRepo->findAll();
            $output->writeln([
                'Existing websites',
                '============',
            ]);
            foreach($existingWebsites as $existingWebsite) {
                $output->writeln($existingWebsite);
            }
            $output->writeln('============');
            $io->warning('You must specify website(s) to ping or ask for all of them through -a / --all.');
            return 1;
        }

        //$io->success('Successfully requested all websites.');

        return 0;
    }
    
    private function callWebsite(Website $website) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $website->getDomain());
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        if(!curl_errno($ch))
        {
            $info = curl_getinfo($ch);
            echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
            $website->setStatus($info['http_code']);
            $website->setResponseTime($info['total_time']);
        }
        curl_close($ch);
        return $website;
    }
}
