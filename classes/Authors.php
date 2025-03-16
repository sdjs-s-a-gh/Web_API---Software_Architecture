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
     * Queries the database for actors whose first OR last name contains the
     * parameter passed in the URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * actors by passing a 'search' query parameter in the URL. A valid API key
     * must be provided for this method to execute.
     * 
     * @todo change this @uses ApiKey::validate_api_key() Used to authorise access to this method.
     * 
     * @throws PDOException If there is a database query error.
     * @throws ClientError If the API key provided by the client is incorrect.
     */
    protected function get(): void
    {   
        $db = $this->database;        
        $sql_query = "SELECT author.id, author.name FROM author";

        $query_params = $this->request->get_query_parameters();
        $sql_params = [];
        $uses_filter = false;
        $sql_filter = "";

        /** 
         * if query_params = null or count = 0
         * throw exception
         * else
            * foreach query param
                * if not in valid filters keys
                    * throw exception
                * else
                    * query append value of filter key
         * end if
         * 
         * foreach value in filter array
         *  append AND "value of filter key"

         * 
         */

        // if author id is set

        // if content id is set

        // if search is set

        // if page is set

        // if array key not in valid filters

        // if nothing no filters have been applied in the request.

        foreach ($query_params as $param_name=>$param_value) {
            $sql_filter = "";

            switch ($param_name)
            {
                case "page":
                    if (!is_numeric($param_value)) {
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }
                    break;
                case "author_id":
                    if (!is_numeric($param_value)) {
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }
                    $sql_filter = " author.id = :author_id";
                    $sql_params["author_id"] = $param_value;
                    break;
                case "content_id":
                    if (!is_numeric($param_value)){
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }
                    $sql_query.= " JOIN content_has_author ON author.id = content_has_author.author";               
                    $sql_filter = " content_has_author.content = :content_id";
                    $sql_params["content_id"] = $param_value;
                    break;
                case "search":
                    $sql_filter = " author.name LIKE :search";
                    $sql_params["search"] = "%". $param_value. "%";
                    break;
                default:
                    throw new ClientError("'$param_name' is an Unknown Parameter.", 422);
            }

            // Check whether the query should append either a WHERE or an AND clause.
            if (!str_contains($sql_query, "WHERE") AND $sql_filter !== "") {
                $sql_query.= " WHERE";
            } elseif ($sql_filter !== "") {
                $sql_query.= " AND";
            }

            // Append the new condition for the filter to the query 
            $sql_query .= $sql_filter;
        }
        
        // The offset must be handled last
        if (isset($query_params["page"])) {
            $offset = ($query_params["page"] - 1) * 10;
            $sql_query .= " LIMIT 10 OFFSET :offset";
            $sql_params["offset"] = $offset;
        }

        $sql_filter = [];
        
        if (count($query_params) === 0) {
            throw new ClientError("No parameters provided.", 400);
        }

        foreach ($query_params as $param_name=>$param_value) {

            if ($param_name === "search") {
                $this->validate_params($param_name, $query_params);
            } else {
                $this->validate_params($param_name, $query_params, true);
            }

            array_push($sql_filter, $param_name);

            $sql_params[$param_name] = $param_value;

        }           
        

        $sql_query .= "WHERE " ;

        // data is set
        

        // if a key is unexpected / keys are all expected

        // if author_id is set
         // is numeric
          // add condition to filter array

        // if content_id is set
            // is numeric
            // append the join to the sql query immediately

        // if search is set
         // add condition to filter array


        // append conditions to query


        // if page is set
            // is numeric
            // append page    
        
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
            if (!is_numeric($param_value)){
                throw new ClientError("$param_name. Expected a number.", 422);
            }
            $sql_query.= " JOIN content_has_author ON author.id = content_has_author.author";               
            array_push($sql_filter, " content_has_author.content = :content_id");
            $sql_params["content_id"] = $param_value;
        }

        // author_id

        if (isset($query_params["author_id"])) {
            if (!is_numeric($param_value)) {
                throw new ClientError("$param_name. Expected a number.", 422);
            }
            array_push($sql_filter, " author.id = :author_id");
            $sql_params["author_id"] = $param_value;
        }

        // search


        //page

        

        echo json_encode($sql_query);
        echo json_encode($sql_params);
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
