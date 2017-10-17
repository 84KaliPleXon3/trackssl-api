<?php
// Routes

use Punkstar\Ssl\Reader;
use Punkstar\Ssl\Validator\CommonNameValidator;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response) {

    $documentation = [
        "type" => "documentation",
        "attributes" => [
            "href" => "https://github.com/punkstar/trackssl-api/blob/master/README.md"
        ]
    ];

    return $response->withJson([
        "data" => [$documentation],
        "errors" => []
    ]);
});

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
        $commonNameValidator = new CommonNameValidator($sslCert);

        $certificate = [
            "type" => "certificate",
            "id" => $domain,
            "attributes" => [
                "requestedAt" => (new DateTime())->format('r'),
                "name" => $sslCert->certName(),
                "validFrom" => $sslCert->validFrom()->format('r'),
                "validTo" => $sslCert->validTo()->format('r'),
                "subject" => $sslCert->subject(),
                "issuer" => $sslCert->issuer(),
                "sans" => $sslCert->sans(),
                "cert" => $sslCert->toString(),
                "validCommonName" => $commonNameValidator->isValid($domain)
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