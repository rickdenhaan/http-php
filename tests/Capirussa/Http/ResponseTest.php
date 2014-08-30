<?php
require_once(dirname(__FILE__) . '/../../init.php');

use Capirussa\Http\Response;

/**
 * Tests Capirussa\Http\Response
 *
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructWithoutParameters()
    {
        /** @noinspection PhpParamsInspection (this is intentional) */
        new Response();
    }

    public function testConstructWithEmptyResponse()
    {
        $response = new Response(null);

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(0, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(0, $response->getRawHeaders());

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('', $response->getRawBody());
    }

    public function testConstructWithStatusCode()
    {
        $response = new Response(
            'HTTP/1.1 200 OK'
        );

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(0, $response->getRawHeaders());

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('', $response->getRawBody());
    }

    public function testConstructWithHeaders()
    {
        $response = new Response(
            'HTTP/1.1 200 OK' . "\r\n" .
            'Connection: Close' . "\r\n" .
            'Content-Type: text/html'
        );

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(2, $response->getRawHeaders());
        $this->assertArrayHasKey('Connection', $response->getRawHeaders());
        $this->assertArrayHasKey('Content-Type', $response->getRawHeaders());
        $this->assertEquals('Close', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Connection'));
        $this->assertEquals('text/html', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Content-Type'));

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('', $response->getRawBody());
    }

    public function testConstructWithInvalidHeaders()
    {
        $response = new Response(
            'HTTP/1.1 200 OK' . "\r\n" .
            'Connection: Close' . "\r\n" .
            "\0" . "\r\n" . // invalid because empty (true blank lines (\r\n\r\n) indicate boundary between headers and body)
            'Content-type: text/html; charset=utf-8' . "\r\n" .
            'Content-Length 4096' // invalid because the semicolon is missing
        );

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(2, $response->getRawHeaders());
        $this->assertArrayHasKey('Connection', $response->getRawHeaders());
        $this->assertArrayHasKey('Content-type', $response->getRawHeaders());
        $this->assertEquals('Close', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Connection'));
        $this->assertEquals('text/html; charset=utf-8', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Content-type'));

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('', $response->getRawBody());
    }

    public function testConstructWithDoubleHeaders()
    {
        $response = new Response(
            'HTTP/1.1 100 Continue' . "\r\n" .
            "\r\n" .
            'HTTP/1.1 200 OK' . "\r\n" .
            'Content-Type: text/html' . "\r\n" .
            "\r\n" .
            'This should be the body'
        );

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(1, $response->getRawHeaders());
        $this->assertArrayHasKey('Content-Type', $response->getRawHeaders());
        $this->assertEquals('text/html', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Content-Type'));

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('This should be the body', $response->getRawBody());
    }

    public function testConstructWithBody()
    {
        $response = new Response(
            'HTTP/1.1 200 OK' . "\r\n" .
            'Connection: Close' . "\r\n" .
            'Content-Type: text/html' . "\r\n" .
            "\r\n" .
            '<!DOCTYPE html><html><head>Test</head><body>Test Body</body></html>'
        );

        $this->assertNotNull($response->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotNull($response->getRawHeaders());
        $this->assertInternalType('array', $response->getRawHeaders());
        $this->assertCount(2, $response->getRawHeaders());
        $this->assertArrayHasKey('Connection', $response->getRawHeaders());
        $this->assertArrayHasKey('Content-Type', $response->getRawHeaders());
        $this->assertEquals('Close', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Connection'));
        $this->assertEquals('text/html', $this->getObjectAttribute((object)$response->getRawHeaders(), 'Content-Type'));

        $this->assertNotNull($response->getRawBody());
        $this->assertInternalType('string', $response->getRawBody());
        $this->assertEquals('<!DOCTYPE html><html><head>Test</head><body>Test Body</body></html>', $response->getRawBody());
    }

    public function testParseBodyWithHtmlBody()
    {
        $request = new \Capirussa\Http\Request('http://www.example.com');
        $response = $request->send();

        $this->assertNotNull($response->getParsedBody());
        $this->assertInstanceOf('DOMDocument', $response->getParsedBody());

        $domDocument = $response->getParsedBody();
        /* @type $domDocument \DOMDocument */

        $this->assertEquals('html', $domDocument->doctype->name);

        $htmlDocument = $domDocument->getElementsByTagName('html');
        $this->assertEquals(1, $htmlDocument->length);

        $htmlHead = $htmlDocument->item(0)->getElementsByTagName('head');
        $this->assertEquals(1, $htmlHead->length);

        $headTitle = $htmlHead->item(0)->getElementsByTagName('title');
        $this->assertEquals(1, $headTitle->length);
        $this->assertEquals('Example Domain', $headTitle->item(0)->textContent);

        $headMeta = $htmlHead->item(0)->getElementsByTagName('meta');
        $this->assertEquals(3, $headMeta->length);
        $this->assertEquals('utf-8', $headMeta->item(0)->getAttribute('charset'));
        $this->assertEquals('Content-type', $headMeta->item(1)->getAttribute('http-equiv'));
        $this->assertEquals('text/html; charset=utf-8', $headMeta->item(1)->getAttribute('content'));
        $this->assertEquals('viewport', $headMeta->item(2)->getAttribute('name'));
        $this->assertEquals('width=device-width, initial-scale=1', $headMeta->item(2)->getAttribute('content'));

        $headStyle = $htmlHead->item(0)->getElementsByTagName('style');
        $this->assertEquals(1, $headStyle->length);
        $this->assertEquals(651, strlen($headStyle->item(0)->textContent));

        $htmlBody = $htmlDocument->item(0)->getElementsByTagName('body');
        $this->assertEquals(1, $htmlBody->length);

        $bodyDiv = $htmlBody->item(0)->getElementsByTagName('div');
        $this->assertEquals(1, $bodyDiv->length);

        $bodyH1 = $bodyDiv->item(0)->getElementsByTagName('h1');
        $this->assertEquals(1, $bodyH1->length);
        $this->assertEquals('Example Domain', $bodyH1->item(0)->textContent);

        $bodyP = $bodyDiv->item(0)->getElementsByTagName('p');
        $this->assertEquals(2, $bodyP->length);
        $this->assertEquals('This domain is established to be used for illustrative examples in documents. You may use this' . "\n    " . 'domain in examples without prior coordination or asking for permission.', $bodyP->item(0)->textContent);

        $bodyA = $bodyP->item(1)->getElementsByTagName('a');
        $this->assertEquals(1, $bodyA->length);
        $this->assertEquals('More information...', $bodyA->item(0)->textContent);
        $this->assertEquals('http://www.iana.org/domains/example', $bodyA->item(0)->getAttribute('href'));
    }
}