<?php

/**
 * Award endpoint.
 * 
 * This class represents the Award endpoint, facilitating all CRUD operation
 * (creating, updating, retrieving and deletion of award records). The class
 * supports access to five HTTP methods, including GET, POST, PATCH, DELETE and
 * OPTIONS.
 * 
 * Each method requires API key authentication.
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
     * Returns all awards in the database.
     * 
     * This method handles GET requests and requires API key authentication.
     * No parameters are expected.
     *
     * @throws PDOException If there is a database error.
     * @throws ClientError If any parameters are provided.
     */
    protected function get(): void
    {   
        $this->require_key();

        $db = $this->database;      
        $sql_query = "SELECT * FROM award";

        $query_params = $this->request->get_query_parameters();
        
        // Check there are no parameters
        $this->validate_query_params($query_params, []);
        
        $data = $db->execute_SQL($sql_query);
        
        $this->set_status_code(200);
        $this->set_data($data);        
    }

    /**
     * Creates a new award.
     * 
     * This method handles POST requests and creates a new record of an award,
     * requiring API key authentication. The award name must be unique.
     * 
     * @throws PDOException If there is a database error.
     * @throws ClientError If the award name is not unique.
     */
    protected function post(): void
    {
        $this->require_key();
        $db = $this->database;
        $sql_insert_query = "INSERT INTO award (name) VALUES (:name)";

        $request_body = $this->request->get_body_parameters();

        $sql_params = $this->validate_body_params($request_body, array("name"));
        
        // Check if the name is unique
        $this->is_unique_award_name($request_body["name"]);

        $db->execute_SQL($sql_insert_query, $sql_params);        
        $this->set_status_code(201);    
    }

    /**
     * Updates the name of an existing award.
     * 
     * This method handles PATCH requests and modifies the name of an existing
     * award, requiring API key authentication. The award_id must exist and the
     * new name must be unique.
     * 
     * @throws PDOException If there is a database error.
     * @throws ClientError If the award_id doesn't exist or the new name is not
     * unique.
     */
    protected function patch(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_update_query = "UPDATE award SET name = :name WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();

        $sql_params = $this->validate_body_params($request_body, array("award_id", "name")); 
        
        // Check if the award_id exists        
        $this->award_exists($request_body["award_id"]);

        // Check if the name is unique
        $this->is_unique_award_name($request_body["name"]);

        $db->execute_SQL($sql_update_query, $sql_params);        
        $this->set_status_code(200);       
                          
    }

    /**
     * Deletes an existing award.
     * 
     * This method handles DELETE request and removes an existing award from
     * the database, consequently taking away those awards from pieces of
     * content in which they were received. API authentication is required and
     * the award_id provided must exist in the database.
     * 
     * @throws PDOException If there is a database error.
     * @throws ClientError If the award_id doesn't exist.
     */
    protected function delete(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_delete_foreign_key = "DELETE FROM content_has_award WHERE award = :award_id";
        $sql_delete_query = "DELETE FROM award WHERE id = :award_id";

        $request_body = $this->request->get_body_parameters();       
        
        $sql_param = $this->validate_body_params($request_body, array("award_id"));

        // Check if the award_id exists
        $this->award_exists($request_body["award_id"]);

        $db->execute_SQL($sql_delete_foreign_key, $sql_param);
        $db->execute_SQL($sql_delete_query, $sql_param);        
        $this->set_status_code(204);
             
    }

    /**
     * @inheritdoc
     * This method additionally ensures the award_id parameter is numeric.
     * 
     * @throws ClientError If:
     * - Required parameters are missing.
     * - Any unexpected parameter(s) are present.
     * - The award_id parameter is not numeric.
     */
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

    /**
     * Verifies if an award name is unique (doesn't already exist in the
     * database).
     * 
     * This method, and subsequents ones like it, provide a more readable
     * approach rather than creating an over generalised method that generates
     * dynamic queries.
     * 
     * @param string $award_name The name to check.
     * 
     * @throws ClientError If the award name is not unique.
     * 
     * @return bool Returns true if the name award name is unique, otherwise an
     * exception is thrown. 
     */
    private function is_unique_award_name($award_name): bool 
    {
        $sql_query = "SELECT name FROM award WHERE name = :name";

        if (count($this->database->execute_SQL($sql_query, ["name" => $award_name])) > 0) {
            throw new ClientError("'$award_name' is not a unique name.", 409);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Verifies if an award exists.
     * 
     * @param $award_id The award_id to check.
     * 
     * @throws ClientError If the award_id doesn't exist.
     * 
     * @return bool Returns true if the award exists, otherwise an exception
     * is thrown.
     */
    private function award_exists($award_id): bool
    {
        $sql_query = "SELECT id FROM award WHERE id = :award_id";

        if (count($this->database->execute_SQL($sql_query, ["award_id" => $award_id])) === 0) {
            throw new ClientError("award_id '$award_id' does not exist.", 404);
            return false;
        } else {            
            return true;
        }
    }


    /** Specifies the allowed HTTP methods for this endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
    }    
}
