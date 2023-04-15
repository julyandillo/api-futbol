<?php

namespace App\Controller;

use App\Entity\Estadio;
use App\Repository\EstadioRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use OpenApi\Attributes\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/estadio', name: 'api_estadio_')]
#[Tag(name: 'Estadios')]
class ApiEstadioController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly EstadioRepository $estadioRepository)
    {
    }

    #[Route('/todos', name: 'listar', methods: ['GET'])]
    public function index(): Response
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Estadio $estadio) use ($normalizer) {
                return $normalizer->normalize($estadio, null, ['groups' => 'lista']);
            }, $this->estadioRepository->findAll())
        );
    }

    #[Route('/{idEstadio}', methods: ['GET'])]
    public function detalles(int $idEstadio, NormalizerInterface $normalizer): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
            ], 264);
        }

        return $this->json($normalizer->normalize($estadio));
    }

    #[Route(name: 'nuevo', methods: ['POST'])]
    public function nuevo(Request $request, SerializerInterface $serializer): Response
    {
        $camposObligatorios = ['nombre', 'ciudad', 'capacidad'];
        if (!$this->peticionConParametrosObligatorios($camposObligatorios, $request)) {
            return $this->json([
                'msg' => sprintf('Faltan campos obligatorios para crear el estadio: [%s]',
                    implode(', ', $this->getParametrosObligatoriosFaltantes()))
            ], 400);
        }

        $this->parseaContenidoPeticionJson($request);

        $estadio = $this->estadioRepository->findOneBy([
            'nombre' => $this->contenidoPeticion['nombre'],
        ]);

        if ($estadio) {
            return $this->json([
                'msg' => sprintf('Ya existe un estadio con el nombre \'%s\'', $this->contenidoPeticion['nombre']),
            ], 502);
        }

        /** @var Estadio $estadio */
        $estadio = $serializer->deserialize($request->getContent(), Estadio::class, 'json');
        $this->estadioRepository->save($estadio, true);

        return $this->json([
            'msg' => 'Estadio creado correctamente',
            'id' => $estadio->getId(),
        ]);
    }

    #[Route('/{idEstadio}', methods: ['PATCH'])]
    public function edita(int $idEstadio, Request $request, SerializerInterface $serializer): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
            ], 264);
        }

        $serializer->deserialize($request->getContent(), Estadio::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $estadio]);
        $this->estadioRepository->save($estadio, true);

        return $this->json([
            'msg' => 'Estadio actualizado correctamente',
        ]);
    }

    #[Route('/{idEstadio}', methods: ['DELETE'])]
    public function elimina(int $idEstadio): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
            ], 264);
        }

        $this->estadioRepository->remove($estadio, true);

        return $this->json([
            'msg' => 'Estadio eliminado correctamente',
        ]);
    }
}
