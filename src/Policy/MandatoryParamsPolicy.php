<?php

namespace App\Policy;

use App\Exception\APIMissingMandatoryParamsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MandatoryParamsPolicy implements RequestPolicyInterface
{
    private array $missingMandatoryParams;

    public function __construct(private readonly TranslatorInterface $translator)
    {
        $this->missingMandatoryParams = [];
    }

    /**
     * @throws APIMissingMandatoryParamsException
     */
    public function apply(Request $request, array $options): void
    {
        try {
            $requestParams = $request->getContentTypeFormat() === 'json'
                ? array_keys(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR))
                : $request->request->keys();

            $this->missingMandatoryParams = array_diff($options[ApiPolicy::MANDATORY_PARAMS], $requestParams);

            if (!empty($this->missingMandatoryParams)) {
                throw new APIMissingMandatoryParamsException(
                    $this->translator->trans(
                        'generic.missing_params',
                        ['%params%' => $this->getMissingMandatoryParamsAsString()],
                        'messages'
                    ), Response::HTTP_BAD_REQUEST);
            }

        } catch (\JsonException $exception) {
            throw new APIMissingMandatoryParamsException($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getMissingMandatoryParamsAsString(string $separator = ','): string
    {
        return implode($separator, $this->missingMandatoryParams);
    }
}