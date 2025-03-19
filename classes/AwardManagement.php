<?php

/**
 * AwardManagement endpoint.
 * 
 * This class represents the AwardManagement endpoint, supporting access to three HTTP
 * methods: POST, DELETE and OPTIONS.
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

    protected function post(): void
    {
        $this->require_key();
        $db = $this->database;
        $sql_insert_query = "INSERT INTO content_has_award (content, award) VALUES (:content_id, :award_id)";

        $request_body = $this->request->get_body_parameters();

        $sql_params = $this->validate_body_params($request_body, array("content_id", "award_id"));

        // Check if the content exists
        $sql_query = "SELECT id FROM content WHERE id = :content_id";
        $query_param["content_id"] = $request_body["content_id"];

        if (count($db->execute_SQL($sql_query, $query_param)) === 0) {
            throw new ClientError("content_id ". $request_body["content_id"] . " does not exist.", 404);
        }

        // Check if the content already has an award
        $sql_query = "SELECT content FROM content_has_award WHERE content = :content_id";

        if (count($db->execute_SQL($sql_query, $query_param)) > 0) {
            throw new ClientError("content_id ". $request_body["content_id"] . " currently already has an award.", 409);
        }

        $query_param = [];

        // Check if the award exists
        $sql_query = "SELECT id FROM award WHERE id = :award_id";
        $query_param["award_id"] = $request_body["award_id"];

        if (count($db->execute_SQL($sql_query, $query_param)) === 0) {
            throw new ClientError("award_id ". $request_body["award_id"] . " does not exist.", 404);
        }

        $db->execute_SQL($sql_insert_query, $sql_params);        
        $this->set_status_code(201);
             
    }

    protected function delete(): void
    {
        $this->require_key();

        $db = $this->database;
        $sql_delete_query = "DELETE FROM content_has_award WHERE content = :content_id";

        $request_body = $this->request->get_body_parameters();

        $sql_param = $this->validate_body_params($request_body, array("content_id"));

        // Check if the content has an award to remove
        $sql_query = "SELECT content FROM content_has_award WHERE content = :content_id";

        
        if (count($db->execute_SQL($sql_query, $sql_param)) === 0) {
            throw new ClientError("content_id " . $request_body["content_id"] . " has no award to remove.", 404);
        } else {
            $db->execute_SQL($sql_delete_query, $sql_param);        
            $this->set_status_code(204);
        }        
    }

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

    /** Sets the allowed HTTP methods for this (the Content) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "POST, DELETE, OPTIONS");
    }    
}
