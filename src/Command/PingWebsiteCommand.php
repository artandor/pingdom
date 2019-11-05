<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Website;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\CurlHttpClient;

class PingWebsiteCommand extends Command
{
    protected static $defaultName = 'app:website:ping';

    private $em;
    private $websiteRepository;
    private $client;

    public function __construct(EntityManagerInterface $em, WebsiteRepository $websiteRepository)
    {
        parent::__construct();
        $this->em = $em;
        $this->websiteRepository = $websiteRepository;
        $this->client = new CurlHttpClient();
    }

    protected function configure()
    {
        $this->setDescription('Ping one or more websites included in database.')->addArgument(
                'websites',
                InputArgument::IS_ARRAY,
                'Argument description'
            )->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Ping all websites to refresh status. This override websites argument.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('all')) {
            $io->title('Requesting all websites in database.');
            $websitesToPing = $this->websiteRepository->findAll();
        } else {
            $targettedWebsites = $input->getArgument('websites');
            $websitesToPing = $this->websiteRepository->findBy(
                ['name' => $targettedWebsites]
            );
        }

        $this->pingWebsites($websitesToPing, $io);

        $io->title('New status');
        $this->logWebsites($websitesToPing, $table);

        return 0;
    }

    private function pingWebsites(array $websitesToPing, SymfonyStyle $io): void
    {
        $responses = [];
        foreach ($websitesToPing as $website) {
            $responses[] = $this->client->request('GET', $website->getDomain(), ['user_data' => $website]);
        }
        foreach ($this->client->stream($responses) as $response => $chunk) {
            if ($chunk->isFirst()) {
                /** @var Website $actualWebsite */
                $actualWebsite = $response->getInfo('user_data');
                $io->text(sprintf('Website %s answered', $actualWebsite->getName()));
                $actualWebsite->setStatus($response->getInfo('http_code'));
                $actualWebsite->setResponseTime($response->getInfo('total_time'));
            }
        }
        $this->em->flush();
    }

    private function logWebsites(array $websitesToPing, Table $table): void
    {
        $table
            ->setHeaders(['Name','Domain','Status code', 'Response time'])
            ->setRows($this->getLogRows($websitesToPing))
        ;
        $table->render();
    }

    private function getLogRows(array $websitesToPing): array
    {
        $rowsToLog = [];
        /** @var Website $website */
        foreach ($websitesToPing as $website) {
            $rowsToLog[] = [
                $website->getName(),
                $website->getDomain(),
                $website->getStatus(),
                $website->getResponseTime(),
            ];
        }
        return $rowsToLog;
    }
}
