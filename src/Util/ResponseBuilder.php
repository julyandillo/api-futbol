<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ResponseBuilder
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function createErrorResponseWithMessage(string $message, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse([
            'status' => $code,
            'message' => $message,
            'timestamp' => date(DATE_ATOM),
        ], $code);
    }

    public function createNotFoundResponse(string $message = 'Entity not found'): JsonResponse
    {
        return $this->createErrorResponseWithMessage($message, Response::HTTP_NOT_FOUND);
    }

    public function createExceptionResponse(\Exception $exception, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return $this->createErrorResponseWithMessage($exception->getMessage(), $code);
    }

    public function createMissingMandatoryParamsResponse(array $missingParams): JsonResponse
    {
        return $this->createErrorResponseWithMessage(
            $this->translator->trans('generic.400', ['%params%' => implode(',', $missingParams)], 'messages'),
        );
    }

    public function createPartialDenormalizationExceptionResponse(\Exception|PartialDenormalizationException $e): JsonResponse
    {
        $violations = new ConstraintViolationList();

        /** @var NotNormalizableValueException $exception */
        foreach ($e->getErrors() as $exception) {
            $message = $this->translator->trans(
                'generic.error_types',
                [
                    '%expected%' => implode(', ', $exception->getExpectedTypes()),
                    '%current%' => $exception->getCurrentType(),
                ],
                'messages'
            );

            $parameters = [];
            if ($exception->canUseMessageForUser()) {
                $parameters['hint'] = $exception->getMessage();
            }
            $violations->add(new ConstraintViolation($message, '', $parameters, null, $exception->getPath(), null));
        }

        $messages = array_map(static fn(ConstraintViolation $violation) => implode("", $violation->getParameters()), $violations->getIterator()->getArrayCopy());

        return $this->createErrorResponseWithMessage(implode("\n", $messages), Response::HTTP_BAD_REQUEST);
    }
}