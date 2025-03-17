<?php

/**
 * Award endpoint.
 * 
 * This class represents the Award endpoint, supporting access to five HTTP
 * methods: GET, POST, PATCH, DELETE and OPTIONS.
 * 
 * @author Scott Berston
 */
class Award extends Endpoint
{
    private Database $database;

    /** 
     * Constructor for the Award endpoint.
     * 
     * @param Database $database An instance of the Database class to allow for
     * database interactions.
     */
    public function __construct(Request $request, Database $database, ApiKey $api_key)
    {
        $this->database = $database;
        parent::__construct($request, $api_key);
    }

    /**
     * Queries the database for content (types of papers) based on many
     * parameters passed in the URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * content by passing in a potential of four different query parameters in
     * the URL. These are identical to those in the Authors endpoint.
     *
     * @throws PDOException If there is a database query error.
     * @throws ClientError If no parameters are provided or if they are
     * invalid.
     */
    protected function get(): void
    {   
        $this->require_key();
        $db = $this->database;      
        $sql_query = "SELECT *
        FROM award";

        $query_params = $this->request->get_query_parameters();
        
        // Check there are no parameters
        $this->validate_params($query_params, []);
        
        $data = $db->execute_SQL($sql_query);
        
        $this->set_status_code(200);
        $this->set_data($data);        
    }

    protected function post(): void
    {
        $this->require_key();
        $db = $this->database;
        $sql_insert_query = "INSERT INTO award (name) VALUES (:name)";

        $request_body = $this->request->get_body_parameters();

        if ($request_body === null) {
            throw new ClientError("No data provided", 400);
        }
        
        $sql_params = [];

        // Check the "name" parameter is provided.
        if (isset($request_body["name"])) {
            $sql_params["name"] = $request_body["name"];
        } else {
            throw new ClientError("Parameter 'name' is required.", 400);
        }

        // Check if any unexpected parameters have been passed in the response body.
        foreach ($request_body as $param_name=>$param_value) {
            if (!in_array($param_name, array("name"))) {
                throw new ClientError("Unexpected Parameter: $param_name", 400); 
            }
        }
        
        // Check if the name is unique        
        if ($this->is_unique_award_name($request_body["name"]) === false) {
            throw new ClientError( $request_body["name"] . " is not a unique name.", 400);
        } else {
            $db->execute_SQL($sql_insert_query, $sql_params);        
            $this->set_status_code(201);
        }       
    }

    protected function is_unique_award_name($name): bool 
    {
        $db = $this->database;
        $sql_query = "SELECT name FROM award WHERE name = :name";
        $sql_query_param["name"] = $name;

        return count($db->execute_SQL($sql_query, $sql_query_param)) === 0;
    }


    protected function patch(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_update_query = "UPDATE award SET name = :name WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();

        if ($request_body === null) {
            throw new ClientError("No data provided", 400);
        }
        
        $sql_params = $this->validate_body_params($request_body, array("award_id", "name"));        
        
        // Check if the name is unique        
        if ($this->is_unique_award_name($request_body["name"]) === false) {
            throw new ClientError($request_body["name"] . " is not a unique name.", 400);
        } else {
            $db->execute_SQL($sql_update_query, $sql_params);        
            $this->set_status_code(200);
        }       
    }

    protected function delete(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_delete_query = "DELETE FROM award WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();

        if ($request_body === null) {
            throw new ClientError("No data provided", 400);
        }
        
        $sql_param = $this->validate_body_params($request_body, array("award_id"));

        // Check if the award_id exists
        $sql_query = "SELECT id FROM award WHERE id = :award_id ";

        if (count($db->execute_SQL($sql_query, $sql_param)) === 0) {
            throw new ClientError("award_id " . $request_body["award_id"] . " does not exist.", 400);
        } else {
            $db->execute_SQL($sql_delete_query, $sql_param);        
            $this->set_status_code(202);
        }        
    }

    private function validate_body_params(array $request_body, array $required_params): array
    {
        $sql_params = [];

        // Check all parameters have been provided in the request body. If not, a ClientError exception will be thrown.
        foreach ($required_params as $required_param) {
            if (!array_key_exists($required_param, $request_body)) {
                throw new ClientError("$required_param is required", 400);
            } elseif ($required_param === "award_id" and !is_numeric($request_body["award_id"])) {
                throw new ClientError("award_id. Expected a number.", 422);                
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

    /** Sets the allowed HTTP methods for this (the Content) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, OPTIONS");
    }    
}
