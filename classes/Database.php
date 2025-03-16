<?php
 
/**
 * Database class.
 * 
 * This class is used to store details about the database and execute SQL
 * statements.
 * 
 * @author Scott Berston
 */
class Database 
{
    private $database_file; 
    private $db_connection;
 
    /** 
     * Constructs a new Database instance.
     * 
     * This constructor sets the path to the database and establishes a
     * connection.
     * 
     * @param string $file_path The path to the database file.
     */
    public function __construct(string $file_path)
    {
       $this->set_database_file_path($file_path);
       $this->set_db_connection();
    }

    /** 
     * Returns the path to the database file.
     * 
     * @return string $database_file_path The path to the database file.
     */
    public function get_database_file_path(): string
    {
       return $this->database_file;
    }

    /** 
     * Returns the connection for the database.
     * 
     * @return PDO $db_connection The connection for the database.
     */
    public function get_conn(): PDO
    {
       return $this->db_connection;
    }

    /** 
     * Sets the path to the database file.
     * 
     * @param string $filename The path to the database file.
     */
    private function set_database_file_path(string $filename): void
    {
      $this->database_file = $filename;
    }

    /** 
     * Sets the database connection.
     *  
     * A subroutine that sets the db_connection property, establishing a
     * connection between PHP and a database server represented using a PDO
     * object. If an error is caused when establishing a connection, a
     * PDOException will be thrown.
     * 
     * @throws PDOException If there is an error connecting to the database.
     */
    private function set_db_connection(): void
    {
       $this->db_connection = new PDO('sqlite:'.$this->database_file);
       $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /** 
     * Executes an input SQL statement with an optional array of parameters,
     * returning the result as an array. 
     * 
     * @param string $sql_query A valid SQL statement.
     * @param array<string, mixed> $params [optional] An associative array
     * containing the parameters to be substituted into the SQL statement.
     * 
     * @return array The result of an SQL query. This may return an empty array
     * if the query does not find any matches or if the SQL statement does not
     * return data, such as when Creating, Updating or Deleting an instance.
     */
    public function execute_SQL($sql_query, $params=[]): array
    {
       $db_connection = $this->db_connection;
      
       // Prepare the SQL query
       $sql_statement = $db_connection->prepare($sql_query);

       // Execute the query
       $sql_statement->execute($params);

       return $sql_statement->fetchAll(PDO::FETCH_ASSOC);
    }
}