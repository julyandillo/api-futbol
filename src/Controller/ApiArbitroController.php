<?php

namespace App\Controller;

use App\Entity\Arbitro;
use App\Repository\ArbitroRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Context\Normalizer\DateTimeNormalizerContextBuilder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

#[Route('/api/arbitros', name: 'api_arbitro_')]
#[OA\Tag(name: 'Arbitros')]
final class ApiArbitroController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(
        private readonly ArbitroRepository   $arbitroRepository,
        private readonly SerializerInterface $serializer)
    {
    }

    /**
     * Muestra los detalles de un árbitro
     */
    #[Route('/{id}', name: 'view', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Detalles del árbitro',
        content: new OA\JsonContent(ref: new Model(type: Arbitro::class, groups: ['view']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Petición mal formada',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function index(int $id, NormalizerInterface $normalizer): Response
    {
        $arbitro = $this->arbitroRepository->find($id);

        if (!$arbitro) {
            return $this->json([
                'code' => 264,
                'message' => 'Arbitro not found',
            ]);
        }

        $context = [
            'groups' => ['view'],
        ];

        $contextBuilder = (new DateTimeNormalizerContextBuilder())
            ->withContext($context)
            ->withFormat('Y-m-d');

        return $this->json($normalizer->normalize($arbitro, 'json', $contextBuilder->toArray()));
    }

    /**
     * Crea un nuevo árbitro
     */
    #[Route(name: 'create', methods: ['POST'])]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function create(Request $request): Response
    {
        try {
            if (!$this->peticionConParametrosObligatorios(['name', 'country'], $request)) {
                return $this->buildResponseWithMissingMandatoryParams();
            }

            $this->parseJsonRequest($request);

            if ($this->arbitroRepository->findOneBy(['name' => $this->jsonContent['name']])) {
                return $this->json([
                    'code' => 264,
                    'msg' => 'Ya existe un arbitro con el mismo nombre',
                ]);
            }

            return $this->processRequest(new Arbitro(), $request);

        } catch (\JsonException $e) {
            return $this->json([
                'code' => 500,
                'msg' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    private function processRequest(Arbitro $arbitro, Request $request): Response
    {
        try {
            $context = [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $arbitro,
            ];

            $contextBuilder = (new DateTimeNormalizerContextBuilder())
                ->withContext($context)
                ->withFormat('Y-m-d');

            $arbitro = $this->serializer->deserialize($request->getContent(), Arbitro::class, 'json', $contextBuilder->toArray());

            $this->arbitroRepository->save($arbitro);

            return $this->json(['code' => 200, 'arbitro' => $arbitro->getId()]);

        } catch (PartialDenormalizationException $e) {
            $violations = new ConstraintViolationList();

            /** @var NotNormalizableValueException $exception */
            foreach ($e->getErrors() as $exception) {
                $message = sprintf('The type must be one of "%s" ("%s" given).', implode(', ', $exception->getExpectedTypes()), $exception->getCurrentType());
                $parameters = [];
                if ($exception->canUseMessageForUser()) {
                    $parameters['hint'] = $exception->getMessage();
                }
                $violations->add(new ConstraintViolation($message, '', $parameters, null, $exception->getPath(), null));
            }

            return $this->json([
                'code' => 500,
                'msg' => array_map(static fn(ConstraintViolation $violation) => implode("", $violation->getParameters()), $violations->getIterator()->getArrayCopy()),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Modifica un árbitro
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Arbitro actualizado correctamente',
        content: new OA\JsonContent(
            properties: [new OA\Property('code', type: 'integer', example: 200)],
        ),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function update(int $id, Request $request): Response
    {
        $arbitro = $this->arbitroRepository->find($id);
        if (!$arbitro) {
            return $this->json([
                'code' => 264,
                'message' => 'Arbitro not found',
            ]);
        }

        return $this->processRequest($arbitro, $request);
    }

    /**
     * Elimina un árbitro
     */
    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Arbitro eliminado correctamente',
        content: new OA\JsonContent(
            properties: [new OA\Property('code', type: 'integer', example: 200)]
        ),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function delete(int $id): Response
    {
        $arbitro = $this->arbitroRepository->find($id);
        if (!$arbitro) {
            return $this->json([
                'code' => 264,
                'message' => 'Arbitro not found',
            ]);
        }

        $this->arbitroRepository->remove($arbitro);

        return $this->json(['code' => 200]);
    }
}
