<?php

/** 
 * Autoloads class files dynamically.
 * 
 * This subroutine automatically loads class files from the "classes/"
 * directory, thus removing the need to manually import each and every class
 * file.
 * 
 * @param string $class The name of the class being loaded.
 * 
 * @throws Exception If the class name does not match the name of the script
 * that contains it. This exception is caught by the general exception handler.
 * 
 * @author Scott Berston
 */
spl_autoload_register(function ($class): void
{
    $file = "classes/" . $class . ".php";

    if (file_exists($file)) {
        require $file;
    } else {
        throw new Exception("Error: Class file for $class not found!", 500);
    }
});
