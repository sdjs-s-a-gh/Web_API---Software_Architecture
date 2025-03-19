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
        $this->validate_query_params($query_params, []);
        
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
        
        $sql_params = [];

        $sql_params = $this->validate_body_params($request_body, array("name"));
        
        // Check if the name is unique
        if ($this->attribute_exists($db, "award", "name", $request_body["name"]) === true) {
            throw new ClientError($request_body["name"] . " is not a unique name.", 409);
        } else {
            $db->execute_SQL($sql_insert_query, $sql_params);        
            $this->set_status_code(201);
        }       
    }

    protected function patch(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_update_query = "UPDATE award SET name = :name WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();

        $sql_params = $this->validate_body_params($request_body, array("award_id", "name")); 
        
        // Check if the award_id exists        
        if ($this->attribute_exists($db, "award", "id", $request_body["award_id"]) === false) {
            throw new ClientError("award_id " . $request_body["award_id"] . " does not exist.", 404);
        }
        
        // Check if the name is unique
        if ($this->attribute_exists($db, "award", "name", $request_body["name"]) === true) {
            throw new ClientError($request_body["name"] . " is not a unique name.", 409);
        }        

        $db->execute_SQL($sql_update_query, $sql_params);        
        $this->set_status_code(200); 
                          
    }

    protected function delete(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_delete_query = "DELETE FROM award WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();       
        
        $sql_param = $this->validate_body_params($request_body, array("award_id"));

        // Check if the award_id exists
        if ($this->attribute_exists($db, "award", "id", $request_body["award_id"]) === false) {
            throw new ClientError("award_id " . $request_body["award_id"] . " does not exist.", 404);
        } else {
            $db->execute_SQL($sql_delete_query, $sql_param);        
            $this->set_status_code(204);
        }        
    }

    protected function validate_body_params(array $request_body, array $required_params): array
    {
        $sql_params = parent::validate_body_params($request_body, $required_params);
        
        if (isset($request_body["award_id"]) and !is_numeric($request_body["award_id"])) {
            throw new ClientError("award_id. Expected a number.", 422);                
        } elseif (isset($request_body["award_id"])) {
            $sql_params["award_id"] = $request_body["award_id"]; 
        }

        return $sql_params;
    }

    private function is_unique_award_name($name): bool 
    {
        $db = $this->database;
        $sql_query = "SELECT name FROM award WHERE name = :name";
        $sql_query_param["name"] = $name;

        return count($db->execute_SQL($sql_query, $sql_query_param)) === 0;
    }

    /** Sets the allowed HTTP methods for this (the Content) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
    }    
}
