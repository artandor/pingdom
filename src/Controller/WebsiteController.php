<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Website;

class WebsiteController extends AbstractController
{
    /**
     * @Route("/", name="website")
     */
    public function index()
    {
        $websites = $this->getDoctrine()
        ->getRepository(Website::class)->findAll();
        return $this->render('website/index.html.twig', [
            'websites' => $websites,
        ]);
    }
}
