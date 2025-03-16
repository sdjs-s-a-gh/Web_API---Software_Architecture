<?php

/**
 * Authors endpoint.
 * 
 * This class represents the Authors endpoint, supporting access to two HTTP
 * methods: GET and OPTIONS.
 * 
 * @author Scott Berston
 */
class Authors extends Endpoint
{
    private Database $database;

    /** 
     * Constructor for the Authors endpoint.
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
     * Queries the database for authors based on many parameters passed in the 
     * URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * authors (their name and id) by passing in a potential of four different
     * query parameters in the URL.
     * 
     * Supported Parameters:
     * - `author_id`: Returns an author with the corresponding author_id.
     * - `content_id`: Filter authors to only those who are in a particular
     * content.
     * - `search`: Performs a search in the authors name.
     * - `page`: Implements pagination, offsetting the returned authors by a
     * given number.
     * 
     * @throws PDOException If there is a database query error.
     * @throws ClientError If no parameters are provided or if they are
     * invalid.
     */
    protected function get(): void
    {   
        $db = $this->database;        
        $sql_query = "SELECT author.id, author.name FROM author";

        $query_params = $this->request->get_query_parameters();
        
        $valid_params = [
            "author_id" => " author.id = :author_id",
            "content_id" => " content_has_author.content = :content_id",
            "search" => " author.name LIKE :search",
            "page" => " LIMIT 10 OFFSET :offset"
        ];

        $required_joins = [
            "content_id" => " JOIN content_has_author ON author.id = content_has_author.author"
        ];

        [$query_to_append, $sql_params] = $this->set_universal_params($query_params, $valid_params, $required_joins);

        $sql_query .= $query_to_append;
        
        $data = $db->execute_SQL($sql_query, $sql_params);
        
        $this->set_status_code(200);
        $this->set_data($data);        
    }

    /** Sets the allowed HTTP methods for this (the Authors) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, OPTIONS");
    }    
}
