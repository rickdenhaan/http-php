<?php
require_once(dirname(__FILE__) . '/../../../init.php');

use Capirussa\Http;

class MockRequest extends Http\Request
{
    /**
     * Overrides the real request method to simulate a predefined response
     *
     * @return Http\Response
     */
    public function send()
    {
        // build the request URL
        $requestUrl = $this->buildRequestUrl();

        // read the mock file contents
        switch ($requestUrl) {
            default:
                $simulatedResponse = $this->loadMockResponse('mock_example_com.txt');
                break;
        }

        // the response should contain \r\n line endings, but Git sometimes screws that up
        if (!strpos($simulatedResponse, "\r\n")) {
            $simulatedResponse = str_replace(array("\r", "\n"), "\r\n", $simulatedResponse);
        }

        $this->response = new Http\Response($simulatedResponse);

        return $this->response;
    }

    private function loadMockResponse($filename)
    {
        return file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename);
    }
}