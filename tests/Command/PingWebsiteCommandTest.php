<?php

namespace App\Tests\Command;

use App\Command\PingWebsiteCommand;
use App\Entity\Website;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\MailerInterface;

class PingWebsiteCommandTest extends KernelTestCase
{
    private $application;

    public function testCommandCanHandleErrors(): void
    {
        $responseMock = new MockResponse([], ['http_code' => 500]);
        $curlHttpClientMock = new MockHttpClient([$responseMock]);
        $website = new Website();
        $website->setDomain('http://google.fr')->setName('poney');
        $websiteRepositoryMock = $this->createMock(WebsiteRepository::class);
        $websiteRepositoryMock->method('findAll')->willReturn([$website]);
        $command = new PingWebsiteCommand(
            $this->createMock(EntityManager::class), $websiteRepositoryMock, $curlHttpClientMock, $this->getMockForAbstractClass(MailerInterface::class)
        );
        $this->application->add($command);
        $command = $this->application->find(PingWebsiteCommand::getDefaultName());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--all' => true,
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertContains('poney | http://google.fr | 500', $output);
    }

    public function testCommandMustHaveAWebsiteSpecified(): void
    {
        $command = $this->application->find(PingWebsiteCommand::getDefaultName());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertContains('[ERROR] You should submit at least one website or use option --all', $output);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = static::createKernel();
        $this->application = new Application($kernel);
    }
}
