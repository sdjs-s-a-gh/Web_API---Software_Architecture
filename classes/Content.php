<?php

/**
 * Content endpoint.
 * 
 * This class represents the Content endpoint, handling requests for retrieving
 * information about each piece of content presented at the conference. The
 * class supports access to two HTTP methods, including GET and OPTIONS.
 * 
 * GET requests require API key authentication.
 * 
 * @author Scott Berston
 */
class Content extends Endpoint
{
    private Database $database;

    /** 
     * Constructor for the Content endpoint.
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
     * Queries the database for pieces of content based on parameters passed in
     * the URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * content by passing in a potential of four different query parameters in
     * the URL. These parameters are identical to those in the Authors endpoint.
     * Requires API key authentication.
     * 
     * Supported Parameters:
     * - `author_id`: Filters the list of content to only those where the
     * author_id is present.
     * - `content_id`: Returns the piece of content corresponding to the
     * content_id.
     * - `search`: Filters pieces of content to only those where their title
     * and abstract feature this search condition.
     * - `page`: Implements pagination, offsetting the returned pieces of
     * content by a given number.
     * 
     * @throws PDOException If there is a database error.
     * @throws ClientError If no parameters are provided or if they are
     * invalid.
     */
    protected function get(): void
    {   
        $this->require_key();

        $db = $this->database;        
        $sql_query = "SELECT content.id, content.title, content.abstract, content.doi_link, content.preview_video, type.name as type, award.name as award
        FROM content
        JOIN type ON content.type = type.id
        LEFT JOIN content_has_award ON content.id = content_has_award.content
        LEFT JOIN award ON content_has_award.award = award.id
        JOIN content_has_author on content.id = content_has_author.content";

        $query_params = $this->request->get_query_parameters();
        
        $valid_params = [
            "author_id" => " content_has_author.author = :author_id",
            "content_id" => " content.id = :content_id",
            "search" => " content.title LIKE :search AND content.abstract LIKE :search",
            "page" => " LIMIT 10 OFFSET :offset"
        ];

        $required_grouping = " GROUP BY content.id";
        [$query_to_append, $sql_params] = $this->set_universal_params($query_params, $valid_params, [], $required_grouping);

        $sql_query .= $query_to_append;
        
        $data = $db->execute_SQL($sql_query, $sql_params);
        
        $this->set_status_code(200);
        $this->set_data($data);        
    }

    /** Specifies the allowed HTTP methods for this endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, OPTIONS");
    }    
}
