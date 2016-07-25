<?php
// Routes

use Punkstar\Ssl\Reader;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/certificate/{domain}', function (Request $request, Response $response) {
    $domain = $request->getAttribute('domain');

    $responseCode = 200;
    $responseWrapper = [
        "data" => [],
        "errors" => []
    ];

    try {
        $sslReader = new Reader();
        $sslCert = $sslReader->readFromUrl(sprintf("https://%s", $domain));

        $certificate = [
            "type" => "certificate",
            "id" => $domain,
            "attributes" => [
                "name" => $sslCert->certName(),
                "validFrom" => $sslCert->validFrom()->format('r'),
                "validTo" => $sslCert->validTo()->format('r'),
                "subject" => $sslCert->subject(),
                "issuer" => $sslCert->issuer(),
                "sans" => $sslCert->sans(),
                "cert" => $sslCert->toString()
            ]
        ];

        $responseCode = 200;
        $responseWrapper['data'][] = $certificate;
    } catch (\Punkstar\Ssl\Exception $e) {
        $responseWrapper['errors'][] = [
            'code' => $e->getCode(),
            'title' => $e->getMessage()
        ];
    } catch (Exception $e) {
        $responseCode = 500;
        $responseWrapper['errors'][] = [
            'code' => $e->getCode(),
            'title' => $e->getMessage()
        ];
    }

    return $response->withJson($responseWrapper, $responseCode);
});