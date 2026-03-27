<?php

/**
 * AwardManagement endpoint.
 * 
 * This class represents the AwardManagement endpoint, handling the giving
 * (creating) and removal (deleting) of awards for pieces of content. The class
 * support access to three methods, including POST, DELETE and OPTIONS.
 * 
 * @author Scott Berston
 */
class AwardManagement extends Endpoint
{
    private Database $database;

    /** 
     * Constructor for the AwardManagement endpoint.
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
     * Gives an award to a piece of content.
     * 
     * This method handles POST requests and creates a new record of an
     * content-award relationship. API authentication is required.
     * 
     * @throws ClientError If:
     * - The content_id does not exist.
     * - The award_id does not exist.
     * - The content already has an award assigned.
     */
    protected function post(): void
    {
        $this->require_key();
        $db = $this->database;
        $sql_insert_query = "INSERT INTO content_has_award (content, award) VALUES (:content_id, :award_id)";

        $request_body = $this->request->get_body_parameters();

        $sql_params = $this->validate_body_params($request_body, array("content_id", "award_id"));

        // Check if the content exists
        $this->content_exists($request_body["content_id"]);

        // Check if the award exists
        $this->award_exists($request_body["award_id"]);

        // Check if the content already has an award
        if ($this->content_has_award($request_body["content_id"])) {
            throw new ClientError("content_id '". $request_body["content_id"] . "' currently already has an award.", 409);
        }      

        $db->execute_SQL($sql_insert_query, $sql_params);        
        $this->set_status_code(201);
             
    }

    /**
     * Removes an award from a piece of content.
     * 
     * This method handles DELETE requests and removes an existing
     * content-award relationship. API key authentication is required.
     * 
     * @throws ClientError If the content_id does not exist or the content has
     * no award to remove.
     */
    protected function delete(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_delete_query = "DELETE FROM content_has_award WHERE content = :content_id";

        $request_body = $this->request->get_body_parameters();

        $sql_param = $this->validate_body_params($request_body, array("content_id"));

        // Check if the content exists
        $this->content_exists($request_body["content_id"]);

        // Check if the content has an award to remove
        if ($this->content_has_award($request_body["content_id"]) === false) {            
            throw new ClientError("content_id '".$request_body["content_id"]."' has no award to remove.", 404);
        }
   
        $db->execute_SQL($sql_delete_query, $sql_param);        
        $this->set_status_code(204);               
    }

    /**
     * @inheritdoc Additionally ensures that all parameters are numeric.
     * 
     * @throws ClientError If:
     * - Required parameters are missing.
     * - Any unexpected parameter(s) are present.
     * - Any parameter is not numeric.
     */
    protected function validate_body_params(array $request_body, array $required_params): array
    {        
        $sql_params = parent::validate_body_params($request_body, $required_params);

        foreach ($required_params as $required_param) {
            if (!is_numeric($request_body[$required_param])) {
                throw new ClientError("$required_param. Expected a number.", 422); 
            } else {
                $sql_params[$required_param] = $request_body[$required_param]; 
            }
        }

        return $sql_params;
    }

    /**
     * Verifies if a piece of content exists in the database.
     * 
     * @param int $content_id The content_id to check.
     *  
     * @throws ClientError If the content_id does not exist.
     * 
     * @return bool Returns true if the content exists, otherwise an exception
     * is thrown.
     */
    private function content_exists($content_id): bool
    {
        $sql_query = "SELECT id FROM content WHERE id = :content_id";

        if (count($this->database->execute_SQL($sql_query, ["content_id" => $content_id])) === 0) {
            throw new ClientError("content_id '$content_id' does not exist.", 404);
            return false;
        } else {            
            return true;
        }
    }

    /** 
     * Verifies if a piece of content already has an award assigned.
     * 
     * @param int $content_id The content_id to check.
     * 
     * @return bool Returns true if the content has an award.
     */
    private function content_has_award($content_id): bool
    {
        $sql_query = "SELECT content FROM content_has_award WHERE content = :content_id";

        if (count($this->database->execute_SQL($sql_query, ["content_id" => $content_id])) === 0) {
            return false;
        } else {            
            return true;
        }
    }

    /**
     * Verifies if an award exists in the database.
     * 
     * @param int $award_id The award_id to check.
     * 
     * @throws ClientError If the award_id does not exist.
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
        $this->set_headers("Access-Control-Allow-Methods", "POST, DELETE, OPTIONS");
    }    
}
