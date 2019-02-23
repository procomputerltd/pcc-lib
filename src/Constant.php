<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : Constants used by PCC Library classes.
*/
namespace Procomputer\Pcclib;

/**
 * Constants used by PCC Library classes.
 */
class Constant {
    /**
     * Error constants.
     */
    const E_UNSPECIFIED = -1; // unknown or undocumented error
    const E_PARAMETER_INVALID = 0x021A; // invalid function parameter
    const E_TYPE_MISMATCH = 0x021B; // function parameter type mismatch
    const E_PARAMETER_EMPTY = 0x021C; // empty function parameter - parameter required
    const E_FILE_OPEN = 0x021D; // an error occurred opening the file
    const E_FILE_CLOSE = 0x021E; // an error occurred closing the file or socket
    const E_FILE_READ = 0x021F; // an error occurred reading the open file or socket
    const E_FILE_WRITE = 0x0220; // an error occurred writing to the open file or socket
    const E_FILE_RENAME = 0x0221; // an error occurred renaming a file
    const E_FILE_DELETE = 0x0222; // an error occurred deleting a file
    const E_FILE_NOT_FOUND = 0x0223; // the file is not found
    const E_PATH_NOT_FOUND = 0x0224; // the path is not found
    const E_BAD_SOURCE_RESOURCE = 0x0225; // bad source file resource handle
    const E_BAD_DEST_RESOURCE = 0x0226; // bad destination file resource handle
    const E_FILE_TOO_LARGE = 0x0227; // file size too large
    const E_FILE_MKTEMP = 0x0228; // cannot create temporary file
    const E_FILE_COPY = 0x0229; // an error occurred copying a file
    const E_FILE_EXISTS = 0x022A; // the file exists; cannot overwrite
    const E_CONNECTION_TERMINATED = 0x022B; // the connection terminated
    const E_CREATE_UNIQUE = 0x022C; // cannot create unique filename in the specified path
    const E_FILE_SEEK = 0x022D; // cannot seek file to specified position
    const E_HEADERS_SENT = 0x022E; // HTTP headers are already sent
    const E_ACCESS_DENIED = 0x022F; // Access denied.
    const E_DIRECTORY_CREATE = 0x0230; // an error occurred creating directory.
    const E_FILE_MODE = 0x0231; // an error occurred setting file mode with 'chmod()'

    const E_NO_NUMBER_PARSED = 0x0240; // no number was parsed - use method 'parseNumber(\$number)' or specify a number in the constructor.
    const E_PARSENUMBER_EMPTY = 0x0241; // the parameter that specifies the number is empty.
    const E_PARSENUMBER_SYNTAX = 0x0242; // the parameter that specifies the number is not a number, invalid syntax.
    const E_PARSENUMBER_NAN = 0x0243; // the parameter that specifies the number is not-a-number (NAN).
    const E_PARSENUMBER_INF = 0x0244; // the parameter that specifies the number is not a number, infinite (INF).
    
    /**
     * Text strings constants
     */
    const T_PARAMETER_INVALID = "invalid '%s' parameter '%s'"; // @var string
    const T_PARAMETER_EMPTY = "empty '%s' function parameter '%s' - parameter required"; // @var string
    const T_PARAMETER_TYPE_MISMATCH = "parameter type mismatch '%s' for function parameter '%s'"; // @var string
    const T_HEADERS_SENT = "HTTP headers and subsequent data may already have been output"; // @var string

    // Specific file function error formatting strings.
    const T_FILE_NOT_FOUND = "file not found '%s': %s"; // @var string
    const T_FILE_READ = "an error occurred reading the open file or socket: %s"; // @var string
    const T_FILE_WRITE = "an error occurred writing to the open file or socket: %s"; // @var string
    const T_FILE_SEEK = "cannot seek file to position '%s': %s"; // @var string
    const T_ERR_RENAME = "cannot rename file '%s' to '%s': %s"; // @var string
    const T_ERR_DELETE = "cannot delete file '%s': %s"; // @var string
    const T_ERR_MKTEMP = "cannot create temporary file in path '%s': %s"; // @var string
    const T_CANNOT_BACKUP = "cannot backup file '%s': %s"; // @var string
    const T_BAD_RESOURCE = "invalid file resource handle parameter '%s': '%s'"; // @var string
    const T_CANNOT_OPEN_INPUT_FILE = "cannot open input file '%s': %s"; // @var string
    const T_CANNOT_OPEN_OUTPUT_FILE = "cannot open output file '%s': %s"; // @var string
    const T_FILE_CLOSE = "cannot close file '%s': %s"; // @var string
    const T_FILE_TOO_LARGE = "the size of the file exceeds the specified maximum of '%s'"; // @var string
    const T_CREATE_UNIQUE_MAXED = "cannot create unique filename after %s attempts in the specified path '%s'"; // @var string
    
    const T_NO_NUMBER_PARSED = "no number was parsed - use method 'parseNumber(\$number)' or specify a number in the constructor."; // @var string
    
}