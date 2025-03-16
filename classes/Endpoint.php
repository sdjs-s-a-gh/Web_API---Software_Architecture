<?php
 
/**
 * Endpoint abstract Class. 
 * 
 * This class provides a template for all endpoints to inherit, providing them 
 * with essential functionality. It has a constructor that calls the appropriate
 * method to execute based on the HTTP method used in an incoming request. Each
 * subclass should override the appropriate HTTP method(s) to define specific
 * behaviour for that Endpoint.
 * 
 * @author Scott Berston
 */
abstract class Endpoint 
{
    /** @var int The HTTP status code for the response. */
    private int $status_code;

    /**
     * @var array<string, string> An associative array of response headers,
     * where the key is the header name and the value is the header value.
     */
    private array $headers = [];

    /** 
     * Data to be returned in the response.
     * 
     * @var mixed This can be:
     * - `array`: Typically, the result of an SQL retrieval query or an error.
     * - `string`: For SQL statements that do not return an error, like create, 
     * update and delete, a custom string message may be used to inform the 
     * user that a database interaction has happened.
     * - `null`: For OPTIONS requests that do not return any data.
     */
    private mixed $data = null;
    
    /** @var Request The HTTP request instance set in the constructor. */
    protected Request $request;
    
    /**
     * @var ApiKey The API key verifier for authorisation, which is set in the
     * constructor.
     */
    protected ApiKey $api_key;
    
    /** 
     * Constructor for the Endpoint abtract class.
     * 
     * This constructor handles routing the HTTP method from the incoming
     * request to the corresponding Endpoint class method.
     * 
     * @param Request $request An instance of the Request class that represents
     * an incoming HTTP request.
     * @param ApiKey $api_key An instance of the ApiKey class that contains
     * a function to verify the API key for required methods.
     * 
     * @throws ClientError If the client uses an invalid HTTP method. This should
     * not be possible as each HTTP method is covered in this constructor.
     */
    public function __construct(Request $request, ApiKey $api_key)
    {
        $this->request = $request;
        $this->api_key = $api_key;
        
        switch ($this->request->get_request_method()) {
            case 'GET':
                $this->get();
                break;
            case 'POST':
                $this->post();
                break;
            case 'PATCH':
                $this->patch();
                break;
            case 'PUT':
                $this->put();
                break;
            case 'DELETE':
                $this->delete();
                break;
            case 'OPTIONS':
                $this->options();
                break;
            default:
                throw new ClientError(405);
                break;
         }
    }

    /**
     * Sets the HTTP status code to be used in the response.
     * 
     * @param int $status_code The HTTP status code for the response.
     */
    protected function set_status_code(int $status_code): void
    {
        $this->status_code = $status_code;
    }

    /** 
     * Adds a Message Body or CORS related response header.
     * 
     * This method adds a new HTTP header to the response, including both
     * message body headers (such as "Content-Type") and CORS headers
     * (like "Access-Control-Allow-Methods"). The "Content-Type",
     * "Content-Language" and "Access-Control-Allow-Origin" headers are set by
     * default in the Response class and thus do not need to be included here.
     * 
     * @param string $header_name
     * @param string $header_value
     *  
     * @example
     * // Set the allowed HTTP methods.
     * $this->set_headers("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
     */
    protected function set_headers(string $header_name , string $header_value): void
    {
        $this->headers[$header_name] = $header_value;
    }
    
    /** 
     * Sets the response data.
     * 
     * This method assigns data that will be returned in the response,
     * including query results or status messages. Status messages may be
     * returned when an SQL statement does not produce a result, such as for
     * Create, Update and Delete statements.  
     * 
     * @param mixed $data The response data, which is typically a string,
     * array or null but can be any type.
     */
    protected function set_data($data): void
    {
        $this->data = $data;
    }
    
    /**
     * Returns the response data.
     * 
     * @return mixed The response data, which is typically a string, array or
     * null but can be any type.
     */
    public function get_data(): mixed
    {
        return $this->data;
    }

    /** 
     * Returns the HTTP status code.
     * 
     * @return int A HTTP status code set by the method.
     */
    public function get_status_code(): int
    {
        return $this->status_code;
    }
    
    /**
     * Returns an array of all headers to be included in the Response.
     * 
     * @return array<string, string> An associative array of HTTP headers.
     */
    public function get_http_headers(): array
    {
        return $this->headers;
    }


    # <----------------- HTTP Methods ----------------->
    /** By default, none of these methods have any code and consequently are not allowed. */

    /**
     * Default method for GET requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function get(): void
    {
        throw new ClientError("GET", 405);
    }

    /**
     * Default method for POST requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function post(): void
    {
        throw new ClientError("POST", 405);
    }

    /**
     * Default method for PATCH requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function patch(): void
    {
        throw new ClientError("PATCH", 405);
    }

    /**
     * Default method for PUT requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function put(): void
    {
        throw new ClientError("PUT", 405);
    }

    /**
     * Default method for DELETE requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function delete(): void
    {
        throw new ClientError("DELETE", 405);
    }

    /**
     * Default method for OPTIONS requests.
     * 
     * @throws ClientError If the method is not implemented in a subclass. 
     */
    protected function options(): void
    {
        throw new ClientError("OPTIONS", 405);
    }
}