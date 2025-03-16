<?php
 
/**
 * Represents an outgoing HTTP response.
 * 
 * This class encapsulates some key components of a HTTP response, including
 * the status code, headers and the output of JSON data.
 * 
 * @author Scott Berston
 */
class Response
{

    /**
     * Constructs a new Response instance.
     * 
     * This constructor immediately sets the HTTP status code and headers.
     * Outputting the response to the client is handled separately through
     * the output_JSON() method.
     * 
     * @param int $status_code The HTTP status code for the response.
     * @param int $http_headers An associative array of HTTP response headers,
     * where the key is the header name and the value is the header value.
     */
    public function __construct(int $status_code, array $http_headers)
    {
        $this->output_status_code($status_code);
        $this->output_headers($http_headers);
    }

    /**
     * Sets the HTTP status code for the response.
     * 
     * @param $status_code The HTTP status code for the response.
     */
    private function output_status_code($status_code): void
    {
        http_response_code($status_code);
    }

    /** 
     * Sets the response headers.
     * 
     * By default, the "Content-Type", "Content-Language" and
     * "Access-Control-Allow-Origin" headers are set as they are universal for
     * each potential response from an endpoint. Additionally, the CORS header
     * is set to allow requests ONLY from the domain w23027648.nuwebspace.co.uk.
     * 
     * @param array<string, string> $http_headers An associative array of
     * custom response headers.
     */
    private function output_headers($http_headers): void 
    {
        // Default headers.
        header("Content-Type: application/json");
        header("Content-language: en");

        // CORS-related header.
        header("Access-Control-Allow-Origin: w23027648.nuwebspace.co.uk");

        // Add custom headers to the response.
        foreach ($http_headers as $header_name=>$header_value) {
            header("$header_name: $header_value");
        }
    }  

    /** 
     * Outputs the provided data as a JSON response.
     * 
     * @param mixed $data The data to be output as JSON and sent in the
     * request to the client.
     */
    public function output_JSON($data): void
    {
        $json_data = json_encode($data);
        echo $json_data;
    }
}