<?php declare(strict_types=1);

namespace DOvereem\A2SP\VehicleInfoApiClient;

use DOvereem\A2SP\VehicleInfoApiClient\Exception\InvalidRequestException;
use DOvereem\A2SP\VehicleInfoApiClient\Exception\UnexpectedApiResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use JsonException;

class VehicleInfoApiClient
{
    private $apiUrl = 'https://voertuiginfo.cartalk.nl/2.1/Rest/Transaction/lp';

    private $apiUsername;
    private $apiPassword;

    public function __construct(
        string $apiUsername,
        string $apiPassword
    ) {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    protected function createGuzzleClient(): Client
    {
        return new Client([
            'connect_timeout' => 1,
            'timeout' => 3,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
    }

    protected function processApiResponse(Response $response): array
    {
        try {
            $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnexpectedApiResponseException("API endpoint did not return valid JSON. Received: " . $response->getBody()->getContents());
        }

        if (!isset($result['status']['succeeded'])) {
            throw new UnexpectedApiResponseException("API endpoint did not return an expected result. Status information is missing. Received: " . $response->getBody()->getContents());
        }

        return $result;
    }

    protected function normalizeLicensePlateNumber(string $licensePlateNumber): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($licensePlateNumber));
    }

    public function getVehicleInformationByLicensePlateNumber(string $licensePlateNumber): array
    {
        $licensePlateNumber = $this->normalizeLicensePlateNumber($licensePlateNumber);

        $response = $this->createGuzzleClient()->post(
            $this->apiUrl,
            [
                'form_params' => [
                    'user' => $this->apiUsername,
                    'password' => $this->apiPassword,
                    'parameters[kenteken]' => $licensePlateNumber,
                    'reference' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
                ]
            ]
        );

        $result = $this->processApiResponse($response);

        if (!$result['status']['succeeded'] || empty($result['responseData'] || empty($result['responseData'][0]['data'][0]))) {
            $a2spStatusCode = $result['status']['message']['code'] ?? null;
            $a2spStatusMessage = $result['status']['message']['message'] ?? null;

            if ($a2spStatusCode === 3901) { // Warning because no results have been found
                return [];
            }

            if ($a2spStatusCode && $a2spStatusMessage) {
                throw new InvalidRequestException("Failed to retrieve vehicle information. Server returned error: {$a2spStatusCode}: {$a2spStatusMessage}");
            }

            throw new InvalidRequestException("Failed to retrieve vehicle information. Server returned: " . $response->getBody()->getContents());
        }



        return $result['responseData'][0]['data'][0];


    }



}
