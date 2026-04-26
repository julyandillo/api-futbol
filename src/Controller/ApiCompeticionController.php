<?php

namespace App\Controller;

use App\DTOs\EquipoPlantillaDTO;
use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Entity\EquipoCompeticion;
use App\Entity\Plantilla;
use App\Repository\CompeticionRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/competiciones', name: 'api_competicion_')]
#[OA\Tag(name: 'Competiciones')]
class ApiCompeticionController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(private readonly CompeticionRepository $competicionRepository,
                                private readonly TranslatorInterface $translator,
                                private readonly ResponseBuilder     $responseBuilder)
    {
    }

    /**
     * Muestra los detalles de una competición
     * @param int $idCompeticion
     * @return JsonResponse
     */
    #[Route('/{idCompeticion}', name: 'detalles', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada con éxito',
        content: new Model(type: Competicion::class, groups: ['OA'])
    )]
    #[OA\Response(
        response: 404,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    public function detalles(int $idCompeticion): JsonResponse
    {
        $competicion = $this->competicionRepository->find($idCompeticion);
        if (!$competicion) {
            return $this->responseBuilder->createNotFoundResponse(
                $this->translator->trans('competition.not_found', ['%id%' => $idCompeticion], 'messages'));
        }

        return $this->json($competicion);
    }

    /**
     * Muestra una lista con todas las competiciones disponibles
     */
    #[Route(name: 'ver_todas', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Array de competiciones, id y nombre',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Competicion::class, groups: ['lista']))
        )
    )]
    public function listaCompeticiones(): Response
    {
        try {
            $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()));

            return $this->json([
                'competiciones' => array_map(static function (Competicion $competicion) use ($normalizer) {
                    return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
                }, $this->competicionRepository->findAll()),
            ]);

        } catch (ExceptionInterface $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);
        }
    }

    /**
     * Muestra todos los equipos y plantillas que participan en una competición
     */
    #[Route('/{idCompeticion}/equipos', name: 'equipos', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Array formado por id, nombre y país de cada equipo y el id de plantilla con la que participa en la competición',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Competicion::class, groups: ['lista']))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'No existe la competición solicitada',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    public function listaEquiposCompeticion(int $idCompeticion): JsonResponse
    {
        try {
            $competicion = $this->competicionRepository->find($idCompeticion);
            if (!$competicion) {
                return $this->responseBuilder->createNotFoundResponse(
                    $this->translator->trans('competition.not_found', ['%%id%' => $idCompeticion], 'messages'));
            }

            $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()));

            return $this->json([
                'equipos' => array_map(static function (EquipoPlantillaDTO $equipoPlantilla) use ($normalizer) {
                    $equipoNormalizado = $normalizer->normalize($equipoPlantilla->getEquipo(), null, ['groups' => 'lista']);
                    $equipoNormalizado['plantilla'] = $equipoPlantilla->getPlantilla()->getId();

                    return $equipoNormalizado;
                }, $competicion->getEquiposPlantillas()),
            ]);

        } catch (ExceptionInterface $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);
        }
    }

    /**
     * Agrega equipos y plantillas a una competición.
     *
     * Para agregar un equipo a una competición es necesario crear antes una plantilla
     */
    #[Route('/{idCompeticion}', name: '_agregar_equipos', requirements: ['idCompeticion' => Requirement::POSITIVE_INT], methods: ['POST'])]
    #[OA\Response(
        response: 400,
        description: 'Petición mal formada',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    public function agregaEquipos(int                    $idCompeticion,
                                  Request                $request,
                                  EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$this->checkIfRequestHasMandatoryParams(['equipos'], $request)) {
                return $this->responseBuilder->createErrorResponseWithMessage(
                    $this->translator->trans('generic.400', ['%%params%' => 'equipos'], 'messages'));
            }

            $this->parseJsonRequest($request);

            if (!is_array($this->jsonContent['equipos'])) {
                return $this->responseBuilder->createErrorResponseWithMessage(
                    $this->translator->trans('competition.equipos_array', [], 'messages'), Response::HTTP_BAD_REQUEST);
            }

            $competicion = $this->competicionRepository->find($idCompeticion);
            if (!$competicion) {
                return $this->responseBuilder->createNotFoundResponse(
                    $this->translator->trans('competition.not_found', ['%id%' => $idCompeticion], 'messages')
                );
            }

            $errores = [];
            $posicion = 0;
            $alMenosUnEquipoAsociado = false;
            foreach ($this->jsonContent['equipos'] as $equipoPlantilla) {
                $posicion++;

                if (!array_key_exists('id_equipo', $equipoPlantilla)
                    || !array_key_exists('id_plantilla', $equipoPlantilla)) {
                    $errores[] = [
                        'posicion' => $posicion,
                        'message' => 'Para poder asociar un equipo a la competición también es necesaria una plantilla',
                    ];
                    continue;
                }

                $equipo = $entityManager->getRepository(Equipo::class)->find($equipoPlantilla['id_equipo']);
                if (!$equipo) {
                    $errores[] = [
                        'equipo' => $equipoPlantilla['id_equipo'],
                        'message' => 'No existe ningún equipo con este id',
                    ];
                    continue;
                }

                $plantilla = $entityManager->getRepository(Plantilla::class)->find($equipoPlantilla['id_plantilla']);
                if (!$plantilla) {
                    $errores[] = [
                        'plantilla' => $equipoPlantilla['id_plantilla'],
                        'message' => 'No exste ninguna plantilla con este id',
                    ];
                    continue;
                }

                if ($entityManager->getRepository(EquipoCompeticion::class)->load($equipo, $competicion, $plantilla)) {
                    $errores[] = [
                        'equipo' => $equipoPlantilla['id_equipo'],
                        'error' => 'El equipo ya tiene asociada una plantilla a la competición',
                    ];
                    continue;
                }

                $equipoCompeticion = new EquipoCompeticion();
                $equipoCompeticion
                    ->setCompeticion($competicion)
                    ->setEquipo($equipo)
                    ->setPlantilla($plantilla);

                $entityManager->persist($equipoCompeticion);
                $alMenosUnEquipoAsociado = true;
            }

            $entityManager->flush();

            $response = [
                'status' => Response::HTTP_OK,
                'message' => $alMenosUnEquipoAsociado
                    ? 'Equipos agregados correctamente a la competición'
                    : 'No se ha podido asociar ningún equipo a la competición',
            ];

            if (!empty($errores)) {
                $response['message'] .= ' (con errores)';
                $response['errores'] = $errores;
            }

            return $this->json($response);

        } catch (\JsonException $exception) {
            return $this->responseBuilder->createExceptionResponse($exception);
        }
    }

}
