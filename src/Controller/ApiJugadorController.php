<?php

namespace App\Controller;

use App\Entity\Jugador;
use App\Repository\JugadorRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/jugador', name: 'api_jugador_')]
class ApiJugadorController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly JugadorRepository $jugadorRepository)
    {
    }

    #[Route('/{id}', name: 'detalles', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function index(int $id, NormalizerInterface $normalizer): Response
    {
        $jugador = $this->jugadorRepository->find($id);
        if (!$jugador) {
            return $this->json([
                'msg' => 'No existe ningún jugador con el id ' . $id,
            ], 264);
        }

        return $this->json($normalizer->normalize($jugador, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]));
    }

    #[Route(methods: ['POST'])]
    public function guarda(Request $request, SerializerInterface $serializer): Response
    {
        if (!$this->peticionConParametrosObligatorios(['apodo', 'nombre', 'posicion'], $request)) {
            return $this->json([
                'msg' => sprintf('Faltan campos obligatorios para realizar la petición: [%s]',
                    $this->stringConParametrosFaltantes())
            ], 400);
        }

        try {
            $jugador = $serializer->deserialize($request->getContent(), Jugador::class, 'json');
            $this->jugadorRepository->save($jugador, true);
        } catch (NotNormalizableValueException $exception) {
            return $this->json([
                'code' => 400,
                'msg' => $exception->getMessage(),
            ]);
        }

        return $this->json([
            'msg' => 'Jugador creado correctamente',
            'id' => $jugador->getId(),
        ]);
    }

    #[Route('/{id}', name: 'modificar', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    public function modifica(int $id, Request $request, SerializerInterface $serializer): Response
    {
        $jugador = $this->jugadorRepository->find($id);
        if (!$jugador) {
            return $this->json([
                'msg' => 'No existe ningún jugador con el id ' . $id,
            ], 264);
        }

        try {
            $serializer->deserialize($request->getContent(), Jugador::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $jugador,
                //DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
            $this->jugadorRepository->save($jugador, true);

        } catch (NotNormalizableValueException $exception) {
            return $this->json([
                'code' => 400,
                'msg' => $exception->getMessage(),
            ]);
        }

        return $this->json([
            'msg' => 'Jugador modificado correctamente',
        ]);
    }

    #[Route('/{id}', name: 'eliminar', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function elimina(int $id): Response
    {
        $jugador = $this->jugadorRepository->find($id);
        if (!$jugador) {
            return $this->json([
                'msg' => 'No existe ningún jugador con el id ' . $id,
            ], 264);
        }

        $this->jugadorRepository->remove($jugador, true);

        return $this->json([
            'msg' => 'Jugador eliminado correctamente',
        ]);
    }
}
