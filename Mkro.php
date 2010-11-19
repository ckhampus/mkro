<?php
/**
 * Mkro
 *  
 * @package Mkro
 * @version //autogen//
 * @copyright Copyright (C) 2010 Cristian Hampus. All rights reserved.
 * @author  Cristian Hampus
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class Mkro {

    /*
    private static $instance = NULL;
    
    private function __construct() {}
    
    /**
     * Initialize the framework. 
     * 
     * @return void
     *
    private static function init() {
        if(self::$instance === NULL) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    final private function __clone() {}
    */
    
    private static $config;
    private static $get_vars;
    private static $post_vars;
    private static $put_vars;
    private static $route_success;
    private static $dump;
    
    private static $initialized = FALSE;
    
    /**
     * Initializes the framework with default values.
     * 
     * @return void
     */
    private static function init() {
        if (self::$initialized) {
            return FALSE;
        }
    
        self::$route_success = FALSE;
        
        self::$config = array(
            'cache_dir' => ''
           ,'views_dir' => ''
        );
        
        self::$dump = array();
        self::$initialized = TRUE;
        
        return TRUE;
    }
    
    /**
     * Resets the framework and sets everything to default. 
     * 
     * @return void
     */
    private static function reset() {
        self::$initialized = FALSE;
        self::init();
    }
    
    /**
     * Set and get different configuration
     * settings that are used by the framework. 
     * 
     * @param mixed $setting 
     * @param mixed $value 
     * @return void
     */
    public static function config($setting, $value = NULL) {
        self::init();
    
        if (!array_key_exists($setting, self::$config)) {
            return FALSE;
        }
        
        if ($value !== NULL) {
            self::$config[$setting] = $value;
            return TRUE;
        }
        else {
            return self::$config[$setting];
        }
        
        return FALSE;
    }
    
    /**
     * Associate a callback function 
     * with a HTTP method and a request URI. 
     * 
     * @param mixed $route 
     * @param mixed $callback 
     * @return void
     */
    public static function route($route, $callback) {
        self::init();
        
        if (self::$route_success) {
            return FALSE;
        }
        
        // Split the http method from the actual route.
        $route = explode(' ', $route);
        
        // Check if there are multiple callback 
        // defined and split them then into an array.
        if (is_string($callback) && strpos($callback, '|')) {
            $callback = explode('|', $callback);
        }
        
        // Check if the requested http method is the
        // same as the method specified in the route.        
        if ($_SERVER['REQUEST_METHOD'] == $route[0]) {
            self::sanitizeData($route[0]);
            
            if (self::dispatch($route[1], $callback)) {
                self::$route_success = TRUE;
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Returns the GET data for the specified name. 
     * 
     * @param mixed $name 
     * @return void
     */
    public static function get($name = NULL) {
        if ($name === NULL) {
            return self::$get_vars;
        }
        
        if (array_key_exists($name, self::$get_vars)) {
            return self::$get_vars[$name];
        }
        
        return NULL;
    }
    
    /**
     * Returns the POST data for the specified name. 
     * 
     * @param mixed $name 
     * @return void
     */
    public static function post($name = NULL) {
        if ($name === NULL) {
            return self::$post_vars;
        }
        
        if (array_key_exists($name, self::$post_vars)) {
            return self::$post_vars[$name];
        }
        
        return NULL;
    }
    
    /**
     * Render a view file. 
     * 
     * @param mixed $view 
     * @param mixed $data 
     * @return void
     */
    public static function render($view, $data = NULL) {
        self::$dump['view'] = $view;
        self::$dump['data'] = $data;
        unset($view, $data);
    
        if (strpos(self::$dump['view'], '.php') === FALSE) {
            self::$dump['view'] .= '.php';
        }
        
        if (!empty(self::$config['views_dir'])) {
        
            if (substr(self::$config['views_dir'], -1) !== '/') {
                self::$config['views_dir'] .= '/';
            }
        }
        
        if (is_array(self::$dump['data'])) {
            extract(self::$dump['data']);
        }
        
        include(self::$config['views_dir'].self::$dump['view']);
    }
    
    /**
     * Handles the URI request.
     * 
     * @param string $route 
     * @param  $callback 
     * @return void
     */
    private static function dispatch($route, $callback) {
        $regexp = array(
            '/:[a-zA-Z_][a-zA-Z0-9_]*/' => '[\w]+',
            '/\*/' => '.+'
        );
        
        $base = $_SERVER['SCRIPT_NAME'];
        $uri = $_SERVER['REQUEST_URI'];
                
        // If the requested uri doesn't contain
        // index.php then remove it from the base uri aswell.
        if (strstr($uri, 'index.php') === FALSE) {
            $base = str_replace('/index.php', '', $base);
        }
        
        // If the base uri and the requested uri are
        // the same add a forwardslash to the requested uri.
        if ($base === $uri) {
            $uri .= '/';
        }
        
        // Make the path a forwardslash if the path is empty.
        $parsed_url = parse_url(substr($uri, strlen($base)));      
        if (!isset($parsed_url['path'])) {
            $parsed_url['path'] = '/';
        }
        
        // Get the values for the arguments by computing
        // the difference between the route and the actual uri.
        $args = array_diff(explode('/', $parsed_url['path']), explode('/', $route));
        
        // Escape the forwardslashes.
        $route = str_replace('/', '\/', $route);
        
        // Convert wild-cards to regular expressions.
        foreach ($regexp as $key => $value) {
            $route = preg_replace($key, $value, $route);
        }
        
        // Check if the requested uri matches the specified route.
        if (preg_match('/^'.$route.'$/', $parsed_url['path'])) {            
            if (is_array($callback)) {
                foreach($callback as $cb) {
                    if (is_callable($cb)) {
                        call_user_func_array($cb, $args);
                    }
                }
                return TRUE;
            }
            
            if (is_callable($callback)) {
                call_user_func_array($callback, $args);
                
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Sanitizes the GET and POST data. 
     * 
     * @param array $method 
     * @return void
     */
    private static function sanitizeData($method) {
        $data = $_GET;
        if (!empty($data)) {        
            foreach ($data as $key => $value) {
                self::$get_vars[$key] = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
        }
        
        $data = $_POST;
        if (!empty($data)) {        
            foreach ($data as $key => $value) {
                self::$post_vars[$key] = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
        }
        
        parse_str(file_get_contents('php://input'), $put_vars_vars);
        $data = $put_vars_vars;
        if (!empty($data)) {        
            foreach ($data as $key => $value) {
                self::$put_vars[$key] = $value;
            }
        }
    }

    /**
     * This section contains methods that
     * are related to the database and ORM. 
     */
    
    private static $pdo;

    /**
     * Initialize database connection.
     *
     * Initializes a database connection
     * with the specified parameters.
     *
     * @param string $username
     * @param string $password
     * @param string $hostname
     * @param string $database
     * @param string $driver
     * @return void
     */
    public static function database($username, $password, $hostname, $database, $driver = 'mysql') {        
        try {
            $pdo = &self::$pdo;
            
            switch($driver) {
                case 'mysql':
                    $conn_string = sprintf('mysql:host=%s;dbname=%s', $hostname, $database);
                    break;
                case 'pgsql':
                    $conn_string = sprintf('pgsql:host=%s;dbname=%s', $hostname, $database);
                    break;
                case 'sqlite':
                    $conn_string = sprintf('sqlite:%s', $database);
                    break;
                default:
                    $conn_string = false;
                    break;
            }
            
            $pdo = new PDO($conn_string, $username, $password);
            unset($pdo);
        }
        catch (PDOException $e) {
            throw new MkroException($e);
        }
    }
    
    /**
     * Checks what type of query is performed. 
     * 
     * @param mixed $sql 
     * @return string
     */
    private static function queryType($sql) {
        $sql = strtolower($sql);
    
        if(strstr($sql, ' ', TRUE) == 'select') {
            return 'SELECT';
        }
        else if(strstr($sql, ' ', TRUE) == 'update') {
            return 'UPDATE';
        }
        else if(strstr($sql, ' ', TRUE) == 'insert') {
            return 'INSERT';
        }
        else if(strstr($sql, ' ', TRUE) == 'delete') {
            return 'DELETE';
        }
        else {
            return NULL;
        }
    }
    
    /**
     * Executes an SQL query and if 
     * available returns the result. 
     * 
     * @param string $sql The SQL query string
     * @param array $values 
     * @param string $class 
     * @return mixed
     */
    public static function query($sql, $values = NULL, $class = 'stdClass') {
        $pdo = &self::$pdo;
        $type = self::queryType($sql);
        $result = NULL;

        try {
            // Use prepared statements if value array is set.
            if(isset($values)) {
                if(!is_array($values)) {
                    throw new InvalidArgumentException('\'$values\' is not of type Array.');
                }
                
                if(empty($values)) {
                    throw new MkroException(new Exception('\'$values\' is empty.'));
                }
                
                $stm = $pdo->prepare($sql);
                
                // Check if statement execution was successful.
                if(!$stm->execute($values)) {
                    $err = $stm->errorInfo();
                
                    throw new MkroException(new Exception($err[2], (int)$err[0]));
                }
            }
            else {
                $stm = $pdo->prepare($sql);
                
                // check if statement execution was successful.
                if(!$stm->execute()) {
                    $err = $stm->errorInfo();
                
                    throw new MkroException(new Exception($err[2], (int)$err[0]));
                }
            }
            
            // Unset the PDO object reference.
            unset($pdo);
            
            if($type === 'SELECT') {
                $stm->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
                $result = $stm->fetchAll();
            }
            else if($type === 'UPDATE') {
                $result = TRUE;
            }
            else if($type === 'INSERT') {
                $result = TRUE;
            }
            else if($type === 'DELETE') {
                $result = TRUE;
            }
            
            if(is_array($result) && !empty($result)) {
                if(count($result) === 1) {
                    return $result[0];
                }
                
                return $result;
            }
            
            return NULL;
        }
        catch (PDOException $e) {
            throw new MkroException($e);
        }
    }
}

/**
 * MkroModel 
 * 
 * @package Mkro
 * @version //autogen//
 * @copyright Copyright (C) 2010 Cristian Hampus. All rights reserved.
 * @author  Critian Hampus
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class MkroModel {
    private $table;
    private $columns = array('id' => '');
    
    /**
     * Initialize model for the specified table.
     * 
     * @param string $table 
     * @return void
     */
    public function __construct($table) {
        $this->table = $table;
    }
    
    public function find($id) {
        
    }
    
    public function __call($method, $params) {
        if (substr($method, 0, 6) === 'findBy') {
        
        }
    }
    
    public function __get($name) {
        if(isset($this->columns[$name])) {
            return $this->columns[$name];
        }
    }

    public function __isset($name) {
        return isset($this->columns[$name]);
    }

    public function __set($name, $value) {
        $this->columns[$name] = $value;
    }
}

/**
 * MkroException 
 * 
 * @package Mkro
 * @version //autogen//
 * @copyright Copyright (C) 2010 Cristian Hampus. All rights reserved.
 * @author  Cristian Hampus
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class MkroException extends PDOException {
    public function __construct($e) {
        parent::__construct();
        
        $this->code = $e->getCode(); 
        $this->message = $e->getMessage(); 
        
        if(strstr($e->getMessage(), 'SQLSTATE[')) { 
            preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches); 
            $this->code = ($matches[1] == 'HT000' ? $matches[2] : $matches[1]); 
            $this->message = $matches[3]; 
        } 
    }
}

/**
 * Helper functions
 *
 * This section contains helper function
 * to make some stuff easier to deal with.
 */

/**
 * Turns a camel cased string and 
 * turns it into underscored string.
 * 
 * @param string $string 
 * @access public
 * @return string
 */
function underscore($string) {
    $string = preg_replace('/([A-Z]+(?=$|[A-Z][a-z])|[A-Z]?[a-z]+)/', '_$0', $string);
    $string = trim($string, '_');
    return $string;
}
