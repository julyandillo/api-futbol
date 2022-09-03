<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Form\CompeticionType;
use App\Repository\CompeticionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private ObjectManager $entityManager;

    public function __construct(ManagerRegistry $doctrine, private CompeticionRepository $competicionRepository)
    {
        $this->entityManager = $doctrine->getManager();
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', []);
    }

    #[Route('/competiciones', name: 'competiciones')]
    public function competiciones(): Response
    {
        return $this->render('admin/competiciones.html.twig', [
            'competiciones' => $this->competicionRepository->findAll()
        ]);
    }

    #[Route('/competicion', name: 'competicion_nueva')]
    public function nuevaCompeticion(Request $request): Response
    {
        return $this->manejaFormularioCompeticion(new Competicion(), $request);

    }

    private function manejaFormularioCompeticion(Competicion $competicion, Request $request): Response
    {
        $form = $this->createForm(CompeticionType::class, $competicion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competicion = $form->getData();
            $this->competicionRepository->add($competicion, true);

            return $this->redirectToRoute('competiciones');
        }

        return $this->renderForm('admin/formCompeticion.html.twig', ['form' => $form]);
    }

    #[Route('/competicion/{idCompeticion}', name: 'competicion_editar')]
    public function editarCompeticion(int $idCompeticion, Request $request): Response
    {
        $competicion = $this->competicionRepository->find($idCompeticion);

        if (!$competicion) {
            return $this->redirectToRoute('app_not_found');
        }

        return $this->manejaFormularioCompeticion($competicion, $request);
    }

    #[Route('/competicion/eliminar/{$id}', name: 'competicion_eliminar')]
    public function eliminarCompeticion(int $id): Response
    {
        $competicion = $this->competicionRepository->find($id);

        if ($competicion) {
            $this->competicionRepository->remove($competicion, true);
        }

        return $this->redirectToRoute('competiciones');
    }
}
