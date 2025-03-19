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

    /**
     * Validate and verify whether the API key provided by the client matches
     * the one in the env.php file.
     * 
     * @uses ApiKey::validate_api_key() Used to authorise access to this method.

     * @throws ClientError
     * - If the Authorization Header is not found
     * - The Header type is invalid
     * - Or the Authorization key is invalid. 
     */
    protected function require_key(): void
    {
        $this->api_key->validate_api_key($this->request->get_all_headers());
    }

    /**
     * Validates that all query parameters provided are expected.
     * 
     * @param Array $query_params The query parameters from the HTTP request.
     * @param Array $valid_param An associative array of allowed parameter
     * names as keys.
     * 
     * @throws ClientError If an unexpected parameter is present.
     * 
     * @return bool Returns true if all query parameters are valid.
     */
    protected function validate_query_params(array $query_params, $valid_params): bool 
    {   
        foreach ($query_params as $param_name=>$param_value) {
            if (!array_key_exists($param_name, $valid_params)) {
                throw new ClientError("'$param_name' is an Unknown Parameter.", 422);
                return false;
            }
        }          

        return true;
    }

    /**
     * Creates and returns an SQL query with dynamic filtering.
     * 
     * This method processes query parameters provided in the request to
     * construct SQL filters, adding necessary JOIN statements as well as
     * conditions for filtering and pagination. It is designed for the Authors
     * and Content endpoints as they share common parameters (`content_id`,
     * `author_id`, `search` and `page`)
     * 
     * @param array $query_params An associative array of parameters sent in
     * the HTTP request.
     * @param array $valid_params An associative array of valid parameters
     * mapped to their corresponding SQL filter.
     * @param array $required_joins [optional] An associative array mapping
     * parameters to their SQL JOIN conditions.
     * @param string $required_groupings [optional] A string containing a SQL
     * grouping condition to remove duplicates.
     * 
     * @return array An array containing both:
     * - string `$sql_query` The SQL query with added conditions.
     * - array<string, mixed> `$sql_params` An associative array containing the
     * parameters to be binded to the SQL query.
     */
    protected function set_universal_params(array $query_params, array $valid_params, array $required_joins=[], string $required_grouping=""): array
    {
        /** @var string The SQL query with the parameters added. */
        $sql_query = "";

        /**
         * @var array<string, mixed> An associative array of the parameter to
         * be binded to the SQL query 
         */
        $sql_params = [];

        /** @var array<int, string> Each individual filter to add to the query. */
        $sql_filter = [];

        // Check for any unexpected parameters.
        $this->validate_query_params($query_params, $valid_params);

        // Add any joins that are needed.
        foreach ($required_joins as $param_name=>$join_condition) {
            if (isset($query_params[$param_name])) {
                $sql_query .= $join_condition;
            }
        }

        // Handle the content_id parameter.
        if (isset($query_params["content_id"])) {
            if (!is_numeric($query_params["content_id"])){
                throw new ClientError("content_id. Expected a number.", 422);
            }            
            array_push($sql_filter, $valid_params["content_id"]);
            $sql_params["content_id"] = $query_params["content_id"];
        }

        // Handle the author_id parameter.
        if (isset($query_params["author_id"])) {
            if (!is_numeric($query_params["author_id"])) {
                throw new ClientError("author_id. Expected a number.", 422);
            }
            array_push($sql_filter, $valid_params["author_id"]);
            $sql_params["author_id"] = $query_params["author_id"];
        }

        // Handle the search parameter.
        if (isset($query_params["search"])) {
            array_push($sql_filter, $valid_params["search"]);
            $sql_params["search"] = "%". $query_params["search"]. "%";
        }

        // Create the SQL query with the added conditions.
        if (count($sql_filter) > 0) {
            $msg_to_append = " WHERE ";
            foreach ($sql_filter as $filter) {
                $msg_to_append .= $filter . " AND";
            }
            
            // Append the filter without the trailing "AND".
            $sql_query .= substr($msg_to_append, 0, -3);            
        }

        // Add any grouping to remove duplicates.
        if ($required_grouping != "") {
            $sql_query .= $required_grouping;
        }

        // Handle the page parameter.
        if (isset($query_params["page"])) {
            $offset = ($query_params["page"] - 1) * 10;
            $sql_query .= " LIMIT 10 OFFSET :offset";
            $sql_params["offset"] = $offset;
        }
        
        return [$sql_query, $sql_params];
    }

    protected function attribute_exists(Database $db, string $table, string $attribute, mixed $value): bool
    {
        $sql_query = "SELECT $attribute FROM $table WHERE $attribute = :$attribute";
        $result = $db->execute_SQL($sql_query, ["$attribute" => $value]);
        
        if (empty($result)) {
            return false;
        } else {
            return true;
        }

    }

    protected function validate_body_params(array $request_body, array $required_params): array
    {        
        if ($request_body === null) {
            throw new ClientError("No data provided", 400);
        }
        
        $sql_params = [];

        // Check all parameters have been provided in the request body. If not, a ClientError exception will be thrown.
        foreach ($required_params as $required_param) {
            if (!array_key_exists($required_param, $request_body)) {
                throw new ClientError("$required_param is required", 400);
            } else {
                $sql_params[$required_param] = $request_body[$required_param]; 
            }
        }

        // Check if any unexpected parameters have been passed in the response body.
        foreach ($request_body as $param_name=>$param_value) {
            if (!in_array($param_name, $required_params)) {
                throw new ClientError("Unexpected Parameter: $param_name", 400); 
            }
        }
        
        return $sql_params;
    }
}