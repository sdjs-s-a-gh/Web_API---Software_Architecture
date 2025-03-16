<?php

/**
 * Represents an incoming HTTP request.
 * 
 * This class encapsulates the key components of an incoming HTTP request,
 * including the request method, target path, query parameters, body parameters
 * and headers.
 * 
 * @author Scott Berston  
 */
class Request
{
    /** 
     * Returns the HTTP method specified in the request.
     * 
     * @return string The request method used (GET, POST, PATCH, PUT, DELETE or
     * OPTIONS).
     */
    public function get_request_method(): string
    {
        return $_SERVER["REQUEST_METHOD"];
    }

    /**
     * Normalises and returns the target endpoint.
     * 
     * This method extracts the requested endpoint from the URL, removing the
     * path as well as any query parameters; converts the endpoint to lowercase
     * and removes any trailing slashes.
     * 
     * @return string  The lowercase target endpoint.
     */
    public function get_endpoint_name(): string 
    {
        $url = parse_url($_SERVER["REQUEST_URI"]);
        $basepath = "/software-architecture/coursework/";
        $endpoint = str_replace($basepath, "", $url["path"]);

        /** 
         * Ensure the endpoint is not case sensitive by normalising the target
         * path to lowercase.
         */ 
        $endpoint = strtolower($endpoint);      

        // Remove any trailing slashes.
        $endpoint = trim($endpoint, "/");
        return $endpoint;
    }
    
    /**
     * Returns any query parameters supplied in the request URL.
     * 
     * @return array<string, mixed> An associative array where the keys are the
     * parameter names and the values are their corresponding value.
     */
    public function get_query_parameters(): array
    {
        return $_GET;
    }

    /** 
     * Returns the request body parameters.
     * 
     * @return array<string, mixed>|null An associative array containing the
     * request body parameters or null should the request body not contain any
     * data.
     */
    public function get_body_parameters(): mixed
    {
        $request_body = file_get_contents("php://input");
        $request_body = json_decode($request_body, true);
        return $request_body;
    }

    /** 
     * Returns all headers sent with the request.
     * 
     * @return array<string, string> An associative array containing all the
     * headers sent within the request. The keys are the header names and the
     * values are their corresponding values.
     */
    public function get_all_headers(): array
    {
        // Get all the headers from the request. This is Apache specific code.
        return getallheaders();
    }
}