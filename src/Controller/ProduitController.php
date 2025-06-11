<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Service\PurchaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/acheter', name: 'app_produit_acheter', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function acheter(Produit $produit, PurchaseService $purchaseService, Request $request): Response
    {
        if ($this->isCsrfTokenValid('acheter'.$produit->getId(), $request->request->get('_token'))) {
            try {
                $purchaseService->purchaseProduct($this->getUser(), $produit);
                $this->addFlash('success', 'Produit acheté avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
    }
}
