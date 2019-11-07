<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Website;
use App\Repository\WebsiteRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PingWebsiteCommand extends Command
{
    protected static $defaultName = 'app:website:ping';

    private $em;
    private $websiteRepository;
    private $client;
    private $mailer;

    public function __construct(
        EntityManagerInterface $em,
        WebsiteRepository $websiteRepository,
        HttpClientInterface $curlHttpClient,
        MailerInterface $mailer
    ) {
        parent::__construct();
        $this->em = $em;
        $this->websiteRepository = $websiteRepository;
        $this->client = $curlHttpClient;
        $this->mailer = $mailer;
    }

    protected function configure(): void
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
        } elseif (!empty($input->getArgument('websites'))) {
            $targettedWebsites = $input->getArgument('websites');
            $websitesToPing = $this->websiteRepository->findBy(
                ['name' => $targettedWebsites]
            );
        } else {
            $io->error('You should submit at least one website or use option --all');

            return 0;
        }
        $this->pingWebsites($websitesToPing, $io);
        $io->title('New status');
        $this->logWebsites($websitesToPing, $table);

        return 0;
    }

    private function sendAlert(Website $website): ?Website {
        if(!$website->getMailingList()) {
            return $website;
        }

        if($website->getLastAlertSent()) {
            $date = clone $website->getLastAlertSent();
            $date->add(new \DateInterval('PT24H'));
        }

        if($website->getConsecutiveFailAmount() > 3) {
            if (!isset($date) || new \Datetime("now") > $date) {
                dump("on passe dans l'envoi de mail");
                $email = (new Email())
                    ->from('millt.nico@gmail.com')
                    ->to(...$website->getMailingList())
                    ->priority(Email::PRIORITY_HIGH)
                    ->subject('Alert status for website : ' . $website->getName())
                    ->html(sprintf('<p>The website %s encountered an error. Status Code %s</p>', $website->getName(), $website->getStatus()));

                $website->setLastAlertSent(new \Datetime('now'));
                $this->mailer->send($email);
            }
        }
        return $website;
    }

    private function pingWebsites(array $websitesToPing, SymfonyStyle $io): void
    {
        $responses = [];
        /** @var Website $website */
        foreach ($websitesToPing as $website) {
            $responses[] = $this->client->request('GET', $website->getDomain(), ['user_data' => $website]);
        }
        foreach ($this->client->stream($responses) as $response => $chunk) {
            /** @var Website $actualWebsite */
            $actualWebsite = $response->getInfo('user_data');
            if ($chunk->isFirst()) {
                $io->text(sprintf('Website %s answered', $actualWebsite->getName()));
                if (($responseStatusCode = $response->getStatusCode())) {
                    $website->getLastOkStatus(new DateTimeImmutable());
                }
                if ($website->getRedirectTo() === $response->getInfo('redirect_url')) {
                    $website->setRedirectionOk(true);
                } else {
                    $website->setRedirectionOk(false);
                }
                $actualWebsite->setStatus($responseStatusCode);
                $actualWebsite->setResponseTime($response->getInfo('total_time'));
                $this->sendAlert($actualWebsite);
            }
        }
    }

    private function logWebsites(array $websitesToPing, Table $table): void
    {
        $table->setHeaders(['Name', 'Domain', 'Status code', 'Response time'])->setRows(
            $this->getLogRows($websitesToPing)
        );
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
