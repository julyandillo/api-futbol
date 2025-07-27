<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

trait ResponseBuilder
{
    public function buildResponseWithErrorMessage(string $message, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
        ], $code);
    }

    public function buildNotFoundResponse(string $message = 'Entidad no encontrada'): JsonResponse
    {
        return $this->buildResponseWithErrorMessage($message, Response::HTTP_NOT_FOUND);
    }

    public function buildExceptionResponse(\Exception $exception, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $exception->getMessage(),
        ], $code);
    }

    public function buildPartialDenormalizationExceptionResponse(\Exception|PartialDenormalizationException $e): JsonResponse
    {
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

        $messages = array_map(static fn(ConstraintViolation $violation) => implode("", $violation->getParameters()), $violations->getIterator()->getArrayCopy());

        return $this->buildResponseWithErrorMessage(implode("\n", $messages));
    }
}