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
     * @throws ClientError If any of the parameters are invalid.
     */
    protected function get(): void
    {   
        $db = $this->database;        
        $sql_query = "SELECT author.id, author.name FROM author";

        $query_params = $this->request->get_query_parameters();
        $sql_params = [];        
        $sql_filter = [];
        
        // data
        if (count($query_params) === 0) {
            throw new ClientError("No parameters provided.", 400);
        }

        $valid_params = ["author_id", "content_id", "search", "page"];

        // unexpected key
        $this->validate_params($query_params, $valid_params);

        // content_id
        if (isset($query_params["content_id"])) {
            if (!is_numeric($query_params["content_id"])){
                throw new ClientError("content_id. Expected a number.", 422);
            }
            $sql_query.= " JOIN content_has_author ON author.id = content_has_author.author";               
            array_push($sql_filter, " content_has_author.content = :content_id");
            $sql_params["content_id"] = $query_params["content_id"];
        }

        // author_id
        if (isset($query_params["author_id"])) {
            if (!is_numeric($query_params["author_id"])) {
                throw new ClientError("author_id. Expected a number.", 422);
            }
            array_push($sql_filter, " author.id = :author_id");
            $sql_params["author_id"] = $query_params["author_id"];
        }

        // search
        if (isset($query_params["search"])) {
            array_push($sql_filter, " author.name LIKE :search");
            $sql_params["search"] = "%". $query_params["search"]. "%";
        }

        // handle filters
        if (count($sql_filter) > 0) {
            $msg_to_append = " WHERE ";
            foreach ($sql_filter as $filter) {
                $msg_to_append .= $filter . " AND";
            }
            
            // Append the filter without the trailing AND.
            $sql_query .= substr($msg_to_append, 0, -3);            
        }

        //page
        if (isset($query_params["page"])) {
            $offset = ($query_params["page"] - 1) * 10;
            $sql_query .= " LIMIT 10 OFFSET :offset";
            $sql_params["offset"] = $offset;
        }
        
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

    /**
     * 
     * @param Array $valid_param An associative array of correct keys to check against.
     */
    protected function validate_params(array $params, $valid_params): bool 
    {   
        foreach ($params as $param_name=>$param_value)
            // Check if the parameter is expected
            if (!in_array($param_name, $valid_params)) {
                throw new ClientError("'$param_name' is an Unknown Parameter.", 422);
                return false;
            }

        return true;
    }
}
