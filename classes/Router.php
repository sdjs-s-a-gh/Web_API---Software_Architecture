<?php

/**
 * Handles routing a HTTP request to a specific Endpoint.
 * 
 * This class determines what endpoint should handle an incoming HTTP request
 * based on the target endpoint that has been extracted from the URL.
 * 
 * @author Scott Berston
 */
class Router 
{
    /** @var Database The database instance set in the constructor. */
    private Database $database;

    /** @var ApiKey The API key verifier set in the constructor. */
    private ApiKey $api_key;

    /** 
     * Constructs a new Router instance.
     * 
     * This constructor immediately sets the Database and API key to be passed
     * into the endpoints.
     * 
     * @param Database $database The database instance for passing to
     * endpoints.
     * @param ApiKey $api_key The API key verifier used for authorisation.
     */
    public function __construct(Database $database, ApiKey $api_key)
    {
        $this->database = $database;
        $this->api_key = $api_key;
    }

    /**
     * Routes an incoming request to the corresponding endpoint.
     * 
     * This method handles routing a target endpoint from the incoming request
     * to its corresponding endpoint class. Should the endpoint class not
     * exist, a 404 "Not Found" ClientError exception is thrown.
     * 
     * @param $request An instance of the incoming HTTP request. 
     * 
     * @throws ClientError 
     * - If the endpoint does not exist, a 404 "Not Found"
     * exception is thrown. 
     * - Otherwise, client errors may be thrown in the endpoints should
     * there be something occur like an invalid query parameter.
     * 
     */
    public function route(Request $request): void 
    {
        $target_endpoint = $request->get_endpoint_name();
        
        try {
            $endpoint = match ($target_endpoint) {
                "authors" => new Authors($request, $this->database, $this->api_key),
                "developer" => new Developer($request, $this->api_key),
                "content" => new Content($request, $this->database, $this->api_key),
                default => throw new ClientError($target_endpoint, 404)
            };

            $data = $endpoint->get_data();
            $status_code = $endpoint->get_status_code();
            $headers = $endpoint->get_http_headers();

        } catch (ClientError $e) {
            // Handle client errors raised in the endpoints.
            $data["Error"] = $e->getMessage();
            $status_code = $e->get_status_code();
            $headers = [];
        } 

        // Any other exception is dealt with by the general exception handler.

        // Send the response to the client.
        $response = new Response($status_code, $headers);   
        $response->output_JSON($data);
    }
}
