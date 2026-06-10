<?php

namespace App\Command;

use App\Entity\Website;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsPeriodicTask(frequency: 'PT1M', arguments: ['--all'])]
#[AsCommand(
    name: 'app:website:ping',
    description: 'Ping one or more websites included in database.',
)]
class WebsitePingCommand extends Command
{
    public function __construct(
        private readonly WebsiteRepository      $websiteRepository,
        private readonly HttpClientInterface    $client,
        private readonly MailerInterface        $mailer,
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'websites',
            InputArgument::IS_ARRAY,
            'List of websites to ping.'
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

        return Command::SUCCESS;
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
            if (!isset($date) || new \Datetime('now') > $date) {
                $email = new TemplatedEmail()
                    ->from('webmaster@nicolasmylle.fr')
                    ->to(...$website->getMailingList())
                    ->priority(Email::PRIORITY_HIGH)
                    ->subject('Alert status for website : ' . $website->getName())
                    ->htmlTemplate('email/error.html.twig')
                    ->context([
                        'website' => $website
                    ]);

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
            $responses[$website->getDomain()] = $this->client->request('GET', $website->getDomain(), [
                'user_data' => $website,
            ]);
        }

        foreach ($responses as $package => $response) {
            try {
                /** @var Website $actualWebsite */
                $actualWebsite = $response->getInfo('user_data');

                $io->text(sprintf('Website %s answered', $actualWebsite->getName()));
                $actualWebsite->setStatus($response->getStatusCode());
                if (($actualWebsite->getRedirectTo() === $response->getInfo('redirect_url')) || ($actualWebsite->getRedirectTo() === $response->getInfo('url'))) {
                    $actualWebsite->setRedirectionOk(true);
                } else {
                    $actualWebsite->setRedirectionOk(false);
                }
                $actualWebsite->setResponseTime($response->getInfo('total_time'));
                $this->sendAlert($actualWebsite);
            } catch (TransportExceptionInterface $e) {
                $io->text(sprintf('Website %s did not answer.', $actualWebsite->getName()));
                $actualWebsite->setStatus(-1);
                $actualWebsite->setResponseTime(null);
                $actualWebsite->setRedirectionOk(false);
                $this->sendAlert($actualWebsite);
            }
        }
        $this->em->flush();
    }

    private function logWebsites(array $websitesToPing, Table $table): void
    {
        $table->setHeaders(['Name', 'Domain', 'Status code', 'Response time', 'Consecutive fails'])->setRows(
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
                $website->getConsecutiveFailAmount(),
            ];
        }

        return $rowsToLog;
    }
}
