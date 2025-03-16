<?php

/**
 * Authors endpoint.
 * 
 * This class represents the Content endpoint, supporting access to two HTTP
 * methods: GET and OPTIONS.
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
     * Queries the database for content (types of papers) based on many
     * parameters passed in the URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * content by passing in a potential of four different query parameters in
     * the URL. These are identical to those in the Authors endpoint.
     * 
     * Supported Parameters:
     * - `author_id`: Filters the list of content where the author_id is
     * present.
     * - `content_id`: Returns the content corresponding to the content_id.
     * - `search`: Performs a search in the content_title and content_abstract
     * attribute fields.
     * - `page`: Implements pagination, offsetting the returned content by a
     * given number.
     * 
     * @throws PDOException If there is a database query error.
     * @throws ClientError If no parameters are provided or if they are
     * invalid.
     */
    protected function get(): void
    {   
        $db = $this->database;        
        $sql_query = "SELECT content.id, content.title, content.abstract, content.doi_link, content.preview_video, type.name as content_type, award.name as award_name
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

    /** Sets the allowed HTTP methods for this (the Content) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, OPTIONS");
    }    
}
