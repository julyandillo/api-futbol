<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ParamsCheckerTrait
{
    private array $missingMandatoryParams = [];

    /**
     * @throws \JsonException
     */
    public function peticionConParametrosObligatorios(array $mandatoryParams, Request $request): bool
    {
        $requestParams = $request->getContentTypeFormat() === 'json'
            ? array_keys(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR))
            : $request->request->keys();

        $this->missingMandatoryParams = array_diff($mandatoryParams, $requestParams);

        return empty($this->missingMandatoryParams);
    }

    public function getMissingMandatoryParams(): array
    {
        return $this->missingMandatoryParams;
    }

    public function getMissingMandatoryParamsAsString(string $separator = ','): string
    {
        return implode($separator, $this->missingMandatoryParams);
    }

    public function buildResponseWithMissingMandatoryParams(?string $format = null): JsonResponse
    {
        return new JsonResponse([
            'msg' => sprintf($format ?? 'No se puede realizar la petición, faltan parámetros obligatorios: [%s]',
                $this->getMissingMandatoryParamsAsString())
        ], Response::HTTP_BAD_REQUEST);
    }
}