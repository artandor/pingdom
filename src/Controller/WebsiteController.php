<?php

namespace App\Controller;

use App\Repository\WebsiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class WebsiteController extends AbstractController
{
    public function __construct(private WebsiteRepository $websiteRepository)
    {
    }

    #[Route('/', name: 'website')]
    public function index()
    {
        $websites = $this->websiteRepository->findBy([], ["name" => "ASC"]);
        return $this->render('website/index.html.twig', [
            'websites' => $websites,
        ]);
    }
}
