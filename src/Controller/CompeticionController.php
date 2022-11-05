<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Form\CompeticionType;
use App\Repository\CompeticionRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CompeticionController extends AbstractController
{
    public function __construct(private readonly CompeticionRepository $competicionRepository)
    {}

    #[Route('/competiciones', name: 'competiciones')]
    public function competiciones(): Response
    {
        return $this->render('admin/competiciones.html.twig', [
            'competiciones' => $this->competicionRepository->findAll()
        ]);
    }

    #[Route('/competicion', name: 'competicion_nueva', methods: ['GET'])]
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

    #[Route('/competicion', name: 'competicion_eliminar', methods: ['DELETE'])]
    public function eliminarCompeticion(Request $request): JsonResponse
    {
        $competicion = $this->competicionRepository->find($request->request->get('competicion'));

        if ($competicion) {
            $this->competicionRepository->remove($competicion, true);

            return $this->json([
                'code' => 200,
                'msg' => 'Competición eliminada correctamente',
            ]);
        }

        return $this->json([
            'code' => 500,
            'msg' => 'La competeción que se intenta eliminar no existe',
        ]);
    }

    #[Route('/api/competiciones', name: 'api_competiciones', methods: ['GET'])]
    public function listaCompeticiones(): JsonResponse
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Competicion $competicion) use ($normalizer) {
            return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
        }, $this->competicionRepository->findAll()));
    }
}