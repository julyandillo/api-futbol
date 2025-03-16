<?php

namespace App\Controller;

use App\Entity\Jugador;
use App\Repository\JugadorRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use OpenApi\Attributes\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/jugadores', name: 'api_jugador_')]
#[Tag(name: 'Jugadores')]
class ApiJugadorController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(private readonly JugadorRepository $jugadorRepository)
    {
    }

    #[Route('/{id}', name: 'detalles', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function index(int $id, NormalizerInterface $normalizer): JsonResponse
    {
        $jugador = $this->jugadorRepository->find($id);
        if (!$jugador) {
            return $this->json([
                'msg' => 'No existe ningún jugador con el id ' . $id,
            ], 264);
        }

        try {
            return $this->json($normalizer->normalize($jugador, null, [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]));

        } catch (ExceptionInterface $exception) {
            return $this->json([
                'msg' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route(methods: ['POST'])]
    public function guanuevoJugador(Request $request, SerializerInterface $serializer): JsonResponse
    {
        if (!$this->peticionConParametrosObligatorios(Jugador::getArrayConCamposObligatorios(), $request)) {
            return $this->buildResponseWithMissingMandatoryParams();
        }

        try {
            $jugador = $serializer->deserialize($request->getContent(), Jugador::class, 'json');
            $this->jugadorRepository->save($jugador, true);

        } catch (NotNormalizableValueException $exception) {
            return $this->json([
                'msg' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'msg' => 'Jugador creado correctamente',
            'id' => $jugador->getId(),
        ]);
    }

    #[Route('/{id}', name: 'modificar', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    public function modificaJugador(int $id, Request $request, SerializerInterface $serializer): JsonResponse
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
                'msg' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'msg' => 'Jugador modificado correctamente',
        ]);
    }

    #[Route('/{id}', name: 'eliminar', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function eliminaJugador(int $id): JsonResponse
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
