<?php

namespace App\Tests\Command;

use App\Command\WebsitePingCommand;
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
        $website->setDomain('http://google.fr')->setName('google');

        $websiteRepositoryMock = $this->createMock(WebsiteRepository::class);
        $websiteRepositoryMock->method('findAll')->willReturn([$website]);
        $websiteRepositoryMock->expects($this->once())->method('findAll');

        $command = new WebsitePingCommand(
            $websiteRepositoryMock, $curlHttpClientMock, $this->createStub(MailerInterface::class), $this->createStub(EntityManager::class)
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--all' => true,
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('google | http://google.fr | 500', $output);
    }

    public function testCommandMustHaveAWebsiteSpecified(): void
    {
        $command = $this->application->find('app:website:ping');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] You should submit at least one website or use option --all', $output);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = static::createKernel();
        $this->application = new Application($kernel);
    }
}
