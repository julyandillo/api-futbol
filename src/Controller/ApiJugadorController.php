<?php

namespace App\Controller;

use App\Entity\Jugador;
use App\Repository\JugadorRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/jugadores', name: 'api_jugador_')]
#[Tag(name: 'Jugadores')]
class ApiJugadorController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;
    use ResponseBuilder;

    public function __construct(private readonly JugadorRepository $jugadorRepository)
    {
    }

    /**
     * Obtiene los detalles de un jugador
     */
    #[Route('/{id}', name: 'detalles', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del jugador', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada con éxito',
        content: new OA\JsonContent(ref: new Model(type: Jugador::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Entidad no encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[OA\Response(
        response: 500,
        description: 'Error al procesar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    public function index(?Jugador $jugador, NormalizerInterface $normalizer): JsonResponse
    {
        if (!$jugador) {
            return $this->buildNotFoundResponse('Jugador no encontrado');
        }

        try {
            return $this->json($normalizer->normalize($jugador, null, [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]));

        } catch (ExceptionInterface $exception) {
            return $this->buildExceptionResponse($exception);
        }
    }

    /**
     * Crea un nuevo jugador
     */
    #[Route(methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada con éxito',
        content: new OA\JsonContent(ref: '#/components/schemas/Created')
    )]
    #[OA\Response(
        response: 400,
        description: 'No se puede realizar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/400')
    )]
    #[OA\Response(
        response: 500,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    #[OA\RequestBody(content: new Model(type: Jugador::class, groups: ['create']))]
    public function createAction(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            if (!$this->peticionConParametrosObligatorios(Jugador::getArrayConCamposObligatorios(), $request)) {
                return $this->buildResponseWithMissingMandatoryParams();
            }

            $jugador = $serializer->deserialize($request->getContent(), Jugador::class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);

            $this->jugadorRepository->save($jugador, true);

            return $this->json([
                'code' => 200,
                'id' => $jugador->getId(),
            ]);

        } catch (PartialDenormalizationException $exception) {
            return $this->buildPartialDenormalizationExceptionResponse($exception);

        } catch (\JsonException $exception) {
            return $this->buildExceptionResponse($exception);
        }
    }

    /**
     * Modifica un jugador
     */
    #[Route('/{id}', name: 'modificar', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del jugador', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Actualización completada correctamente',
        content: new OA\JsonContent(ref: '#/components/schemas/OK'),
    )]
    #[OA\Response(
        response: 404,
        description: 'Jugador no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[OA\Response(
        response: 500,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    #[OA\RequestBody(content: new Model(type: Jugador::class, groups: ['create']))]
    public function updateAction(?Jugador $jugador, Request $request, SerializerInterface $serializer): JsonResponse
    {
        if (!$jugador) {
            return $this->buildNotFoundResponse('Jugador no encontrado');
        }

        try {
            $serializer->deserialize($request->getContent(), Jugador::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $jugador,
                //DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
            $this->jugadorRepository->save($jugador, true);

            return $this->json([
                'msg' => 'Jugador modificado correctamente',
            ]);

        } catch (NotNormalizableValueException $exception) {
            return $this->buildResponseWithErrorMessage($exception->getMessage());
        }
    }

    /**
     * Elimina un jugador
     */
    #[Route('/{id}', name: 'eliminar', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del jugador para eliminar', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Jugador eliminado correctamente',
        content: new OA\JsonContent(ref: '#/components/schemas/OK')
    )]
    #[OA\Response(
        response: 404,
        description: 'Jugador no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    public function deleteAction(?Jugador $jugador): JsonResponse
    {
        if (!$jugador) {
            return $this->buildNotFoundResponse('Jugador no encontrado');
        }

        $this->jugadorRepository->remove($jugador, true);

        return $this->json([
            'msg' => 'Jugador eliminado correctamente',
        ]);
    }
}
