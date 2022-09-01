<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitFormType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class ProduitController extends AbstractController
{
    #[Route('/voir-les-produits', name: 'show_produits', methods: ['GET'])]
    public function showProduits(EntityManagerInterface $entityManager): Response
    {
        # Récupération en BDD de toutes les entités Produit, grâce au Repository.
        $produits = $entityManager->getRepository(Produit::class)->findBy(['deletedAt' => null]);

        return $this->render('admin/produit/show_produits.html.twig', [
            'produits' => $produits
        ]);
    }// end function show()

    #[Route('/voir-les-archives', name: 'show_trash', methods: ['GET'])]
    public function showTrash(EntityManagerInterface $entityManager): Response
    {
        return $this->render('admin/produit/show_trash.html.twig');
    }

    # 1 - Créer un prototype de formulaire en ligne de commande ProduitFormType
    # 2 - Créer une action dans ProduitController pour la création d'un produit
    # 3 - Rendre la vue twig du formulaire.
    # 4 - Créer le fichier Twig de cette vue.
    # 5 - Finir la partie POST dans l'action.

    #[Route('/ajouter-un-produit', name: 'create_produit', methods: ['GET', 'POST'])]
    public function createProduit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $produit = new Produit();

        $form = $this->createForm(ProduitFormType::class, $produit)
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $produit->setCreatedAt(new DateTime);
            $produit->setUpdatedAt(new DateTime);

            # On variabilise le fichier de la photo dans $photo.
            # On obtient un objet de type UploadedFile()
            /** @var UploadedFile $photo */
            $photo = $form->get('photo')->getData();

            if($photo) {
                // Méthode créée par nous-même pour réutiliser du code qu'on répète (create() et update())
                $this->handleFile($produit, $photo, $slugger);
            } // end if $photo

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit est en ligne avec succès !');
            return $this->redirectToRoute('show_produits');
        }// end if $form

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form->createView()
        ]);
    }// end function create()

    #[Route('/modifier-un-produit/{id}', name: 'update_produit', methods: ['GET', 'POST'])]
    public function updateProduit(Produit $produit, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        # Récupération de la photo actuelle
        $originalPhoto = $produit->getPhoto();

        $form = $this->createForm(ProduitFormType::class, $produit, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $produit->setUpdatedAt(new DateTime());
            $photo = $form->get('photo')->getData();

            if($photo) {
                // Méthode créée par nous-même pour réutiliser du code qu'on répète (create() et update())
                $this->handleFile($produit, $photo, $slugger);
            } else {
                $produit->setPhoto($originalPhoto);
            } // end if $photo

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'La modification est réussie avec succès !');
            return $this->redirectToRoute('show_produits');
        } // end if $form

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit
        ]);
    } // end function update()

    #[Route('/archiver-un-produit/{id}', name: 'soft_delete_produit', methods: ['GET'])]
    public function softDeleteProduit(Produit $produit, EntityManagerInterface $entityManager): RedirectResponse
    {
        $produit->setDeletedAt(new DateTime());

        $entityManager->persist($produit);
        $entityManager->flush();

        $this->addFlash('success', 'Le produit a bien été archivé !');
        return $this->redirectToRoute('show_produits');
    }




    ///////////////////////////////////////////////////////////////// PRIVATE FUNCTION /////////////////////////////////////////////////////////////

    private function handleFile(Produit $produit, UploadedFile $photo, SluggerInterface $slugger): void
    {
        # 1 - Déconstruire le nom du fichier
        # a - On récupère l'extension grâce à la méthode guessExtension()
        $extension = '.' . $photo->guessExtension();

        # 2 - Sécuriser le nom et reconstruire le nouveau nom du fichier
        # a - On assainit le nom du fichier pour supprimer les espaces et les accents.
        $safeFilename = $slugger->slug($photo->getClientOriginalName());
//        $safeFilename = $slugger->slug($produit->getTitle());

        # b - On reconstruit le nom du fichier
        # uniqid() est une fonction native de PHP et génère un identifiant unique.
        # Cela évite les possibilités de doublons
        $newFilename = $safeFilename . '_' . uniqid() . $extension;

        # 3 - Déplacer le fichier dans le bon dossier.
        # On utilise un try/catch lorsqu'une méthode "throws" (lance) une Exception (erreur)
        try {
            # On a défini un paramètre dans config/service.yaml qui est le chemin du dossier "uploads"
            # On récupère la valeur avec getParameter() et le nom du param.
            $photo->move($this->getParameter('uploads_dir'), $newFilename);
            # Si tout est bon après le move(), on set le nom de la photo.
            $produit->setPhoto($newFilename);
        }
        catch(FileException $exception) {
            $this->addFlash('warning', 'La photo du produit ne s\'est pas importée avec succès. Veuillez réessayer.');
//            return $this->redirectToRoute('create_produit');
        }
    }// end class handleFile()

}// end class