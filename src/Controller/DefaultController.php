<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('default_home');
    }






























    #[Route('/home', name: 'default_home', methods: ['GET'])]
    public function home(EntityManagerInterface $entityManager): Response
    {
        $produits = $entityManager->getRepository(Produit::class)->findBy(['deletedAt' => null]);

        return $this->render('default/home.html.twig', [
            'produits' => $produits
        ]);
    }

    #[Route('/profile/voir-mes-infos', name: 'show_profile', methods: ['GET'])]
    public function showProfile(EntityManagerInterface $entityManager): Response
    {
        $commands = $entityManager->getRepository(Commande::class)->findBy(['deletedAt' => null]);

        return $this->render('default/show_profile.html.twig', [
            'commands' => $commands
        ]);
    }
}
