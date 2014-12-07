<?php
namespace Capirussa\Http;

/**
 * Every Request that is sent returns a Response object. After parsing the raw response into headers and a body, this
 * class tries to parse the response body based on the Content-Type header, if one exists.
 *
 * @package Capirussa\Http
 */
class Response
{
    /**
     * This property contains the 3-digit HTTP status code for this response.
     *
     * @type int
     */
    protected $statusCode;

    /**
     * This property contains an array of HTTP headers received with this response.
     *
     * @type string[]
     */
    protected $rawHeaders;

    /**
     * This property contains the raw HTTP body received with this response.
     *
     * @type string
     */
    protected $rawBody;

    /**
     * This property contains the parsed HTTP body, if the body could be parsed (`null` otherwise).
     *
     * @type mixed
     */
    protected $parsedBody;

    /**
     * The constructor expects one argument: the raw HTTP response as a string, including the HTTP headers and
     * response body. It parses this string into the HTTP status code, and splits the headers and body into separate
     * properties which can be retrieved using the following methods.
     *
     * <code>
     * $response = new Response($rawHttpRequestResult);
     * </code>
     *
     * @param string $apiResponse
     */
    public function __construct($apiResponse)
    {
        $this->statusCode = 0;
        $this->rawHeaders = array();
        $this->rawBody    = '';

        // parse the API response into sections
        $responseSections = explode("\r\n\r\n", $apiResponse);

        // the first section contains the headers
        $headerSection = array_shift($responseSections);
        $this->rawBody = implode("\r\n\r\n", $responseSections);

        $headerLines = explode("\r\n", $headerSection);
        foreach ($headerLines as $responseLine) {
            if (empty($responseLine)) {
                continue;
            }

            if (strtoupper(substr($responseLine, 0, 5)) == 'HTTP/') {
                $this->statusCode = substr($responseLine, 9, 3);

                if (in_array($this->statusCode, array(100, 301, 302, 303))) {
                    $this->__construct($this->rawBody);

                    return;
                }
            } else {
                $header = explode(':', $responseLine, 2);

                // sanity check
                if (!isset($header[1])) {
                    continue;
                }

                $this->rawHeaders[trim($header[0])] = trim($header[1]);
            }
        }

        // try to parse the body
        $this->parseBody();
    }

    /**
     * This method is used to retrieve the 3-digit HTTP status code (e.g. 200, 401) for the request.
     *
     * <code>
     * $statusCode = $response->getStatusCode();
     * </code>
     *
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * This method is used to retrieve an array of HTTP headers returned in the response. This can be useful if you
     * want to validate the content type before doing any manual parsing of the body, for example, or to check caching
     * settings if you want to implement your own caching mechanism.
     *
     * <code>
     * $headers = $response->getRawHeaders();
     * </code>
     *
     * @return string[]|null
     */
    public function getRawHeaders()
    {
        return $this->rawHeaders;
    }

    /**
     * This method is used to retrieve the raw body returned in the response. In most cases this is an HTML document,
     * but for some requests this can be a plain text file or nothing at all.
     *
     * <code>
     * $rawBody = $response->getRawBody();
     * </code>
     *
     * @return string|null
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }

    /**
     * This method is used to retrieve the parsed body, if the body could be parsed based on the response's content-type
     * header.
     *
     * <code>
     * $parsedBody = $response->getParsedBody();
     * </code>
     *
     * @return string|null
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * This method attempts to parse the raw body based on the Content-Type header (if one exists).
     *
     * <code>
     * $this->parseBody();
     * $parsedBody = $response->getParsedBody();
     * </code>
     *
     * @return void
     */
    protected function parseBody()
    {
        // make sure the body is not empty
        if (strlen(trim($this->getRawBody())) == 0) {
            return;
        }

        $contentType = null;

        // check whether a Content-Type header exists
        $headers = $this->getRawHeaders();
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        } else {
            // check for a case-insensitive match
            foreach ($headers as $header => $value) {
                if (strtolower($header) == 'content-type') {
                    $contentType = $value;
                    break;
                }
            }
        }

        if ($contentType === null) {
            return;
        }

        // check whether the content-type contains a ; separator
        if (strpos($contentType, ';') > -1) {
            $contentType = substr($contentType, 0, strpos($contentType, ';'));
        }

        switch (strtolower($contentType)) {
            case 'text/html':
                libxml_use_internal_errors(true);

                $this->parsedBody = new \DOMDocument();
                $this->parsedBody->loadHTML($this->getRawBody());

                break;
        }
    }
}