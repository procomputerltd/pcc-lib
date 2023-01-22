<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Nov 18, 2018, 9:11:55 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer 
*/
namespace Procomputer\Pcclib\Tests;

use Procomputer\Pcclib\Error;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\PhpErrorHandler;

class TestFileSystem extends TestCommon {

    /**
     * The name of this test class.
     * @var string
     */
    public $name = 'Filesystem';
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description = 'Pcc Filesystem class tests';
    
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace = 'Procomputer\Pcclib\FileSystem';
    
    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    /**
     * Tests Filesystem class static methods.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array Returns an array of TestResult objects.
     */
    public function exec($values = null, $options = null) {
      
        $lcOptions = (null === $options) ? [] : array_change_key_case((array)$options);
        $sysTempDir = sys_get_temp_dir();
        if(isset($lcOptions['tempdir'])) {
            $tempDir = $lcOptions['tempdir'];
            if(! is_string($tempDir) || ! is_dir($tempDir)) {
                $msg = "Cannot execute Filesystem tests: the 'tempdir' option that specifies" 
                    . " the temporary directory to use for testing the Filesystem class is not a directory." 
                    . " Please specify a safe existing directory in the 'tempdir' option";
                if(is_dir($sysTempDir)) {
                    $msg .= " or omit the 'tempdir' option and the system's temporary directory will be used";
                }
                throw new Procomputer\Pcclib\Tests\Exception\RuntimeException($msg);
            }
        }
        elseif(! is_dir($sysTempDir)) {
            $msg = "Cannot execute Filesystem tests: the directory returned by sys_get_temp_dir() is not a directory." 
                . " Please specify a safe existing directory in the 'tempdir' option";
            throw new Procomputer\Pcclib\Tests\Exception\RuntimeException($msg);
        }
        else {
            $tempDir = $sysTempDir;
        }
        
        $tempDir .= DIRECTORY_SEPARATOR . 'pcclib_tests_' . md5(__CLASS__ . 'H1mJ-09Tg!5');
        if(! file_exists($tempDir)) {
            if(! mkdir($tempDir) || ! is_dir($tempDir)) {
                $msg = "Cannot execute Filesystem tests: cannot create the temporary directory for testing." 
                    . " Please specify a safe existing directory in the 'tempdir' option";
                throw new Procomputer\Pcclib\Tests\Exception\RuntimeException($msg);
            }
        }
            
        $tempDir = preg_replace('~[ \\t/]*$~', '', str_replace('\\', '/', $tempDir)) . '/';
        
        $tempFile = $tempDir . 'temp_file.txt';
        $copyFileDest = $tempDir . 'dest_file.txt';
        $uniqueFile = $tempDir . 'unique_file.txt';
        $bogusFile = $tempDir . 'bogus_file.txt';
        $newFileName = $tempDir . 'new_filename.txt';
        $bogusDir = $tempDir . 'bogus_dir';
        $testTempDir = $tempDir . 'temp_dir';
        $anotherDir = $tempDir . 'another_dir';
        $outputFile = $tempDir . 'outfile.txt';
        $readWriteLockedFile = $tempDir . 'locked_file.txt';
        
        $imageFile = __DIR__ . '\procomputer.png';
        
        if(!file_exists($readWriteLockedFile)) {
            file_put_contents($readWriteLockedFile, str_repeat(chr(0), 255));
            // 0600 Read and write for owner, nothing for everybody else
            chmod($readWriteLockedFile, 0600);
        }
        if(file_exists($bogusFile)) {
            chmod($bogusFile, 0777);
            unlink($bogusFile);
        }
        if(! file_exists($uniqueFile)) {
            file_put_contents($uniqueFile, 'This file used for testing PSS\'s getUniqueFilename() method');
            chmod($uniqueFile, 0777);
        }
        if(file_exists($bogusDir)) {
            chmod($bogusDir, 0777);
            rmdir($bogusDir);
        }
        if(file_exists($anotherDir)) {
            chmod($anotherDir, 0777);
            rmdir($anotherDir);
        }
        if(! file_exists($testTempDir)) {
            mkdir($testTempDir);
        }
        $infile = fopen($readWriteLockedFile, 'rb');
        $outfile = fopen($outputFile, 'wb');
        
        $tempFiles = [
            $anotherDir,
            $bogusDir,
            $testTempDir,
            $outputFile,
            $copyFileDest,
            $readWriteLockedFile,
            $tempFile,
            $newFileName,
            $uniqueFile
            ];
        
        $methods = [
            'filePutContents' => [
                'params' => [
                    ['the quick brown fox jumps over the lazy dog', $tempFile], 
                    [$this, $tempFile]
                ],
            ],
            'deleteFile' => [
                'params' => [
                    [$tempFile],
                    [$readWriteLockedFile]
                ],
            ],
            'renameFile' => [
                'params' => [
                    [$bogusFile, $newFileName],
                    [$readWriteLockedFile, $newFileName],
                    [$tempFile, $newFileName]
                ],
            ],
            'copyFile' => [
                // copyFile($source, $dest, $overwrite = false)
                'params' => [
                    [$readWriteLockedFile, $copyFileDest, true]
                ],
            ],
            'canLock' => [
                // canLock($file, $operation = LOCK_EX | LOCK_NB)
                'params' => [
                    [$readWriteLockedFile, LOCK_EX | LOCK_NB],
                    [$bogusFile, LOCK_EX | LOCK_NB]
                ],
            ],
            'createDirectory' => [
                // createDirectory($directory, $mode = 0x1ff, $recursive = false)
                'params' => [
                    [$testTempDir, 0x1ff, false],
                    ['', 'asd', false]
                ],
            ],
            'createTempFile' => [
                // createTempFile($directory = null, $filePrefix = "pcc", $keep = false, $fileMode = null)
                'params' => [[$testTempDir, "tst", false, $fileMode = 0755]
                ],
            ],
            'makeDir' => [
                // makeDir($directory, $perm = 0x1ff)
                'params' => [
                    [$anotherDir, 0x1ff],
                    [$testTempDir, 0x1ff],
                ],
            ],
            'backupFileToTemp' => [
                // backupFileToTemp($file, $filePrefix = "")
                'params' => [
                    [$readWriteLockedFile, "pcc"]
                ],
            ],
            'getUniqueFilename' => [
                // getUniqueFilename($pathname, $prefix = 'copy_of_', $maxAttempts = 0x100000)
                'params' => [
                    [$uniqueFile, 'test_copy_of_', 3],
                    [$uniqueFile, 'test_copy_of_', 3],
                    [$uniqueFile, 'test_copy_of_', 3],
                    [$uniqueFile, 'test_copy_of_', 3],
                    [$uniqueFile, 'test_copy_of_', 3],
                ],
            ],
            'getRealPath' => [
                // getRealPath($path, $delimiter = "/")
                'params' => [['C:\inetpub\framework\..\..\inetpub\framework\module\Scripter\src\Service\Tests\TestService.php', "/"]
                ],
            ],
            'streamCopyToStream' => [
                // streamCopyToStream($infile, $outfile, $maxFileSize = -1)
                'params' => [
                    [$infile, $outfile, -1],
                    [$infile, $outfile, 1],
                    [$infile, $outfile, 9999.9],
                    [$infile, $outfile, 9999]
                ],
            ],
            'joinPath' => [
                'params' => [
                    ['/', 'path1', 'path2', 'path3']
                ],
            ],
            'getFileMimeType' => [
                'params' => [
                    [$uniqueFile],
                    [$imageFile],
                    [$bogusFile]
                ],
            ],
            'getFileExtensionDescription' => [
                'params' => [
                    ['png'],
                    ['.txt'],
                    ['.txt.html'],
                ],
            ],
            'fileExtDot' => [
                'params' => [
                    ['txt', false],
                    ['..txt', true],
                    ['txt.xls', false],
                ],
            ],
            'removePathSlash' => [
                'params' => [
                    ['c:/'],
                    ['/SDaasd/'],
                    [$this]
                ],
            ],
            'removeLeadingSlashes' => [
                'params' => [
                    ['c:/'],
                    ['/SDaasd/'],
                    [$this]
                ],
            ],
            'addPathSlash' => [
                'params' => [
                    ['c:/'],
                    ['/SDaasd/'],
                    [$this, null]
                ],
            ]
        ];

        $classPath = $this->_namespace;
        
        $phpErrHandler = new PhpErrorHandler();
        foreach($methods as $method => $properties) {
            foreach($properties['params'] as $params) {
                
                switch($method) {
                case 'streamCopyToStream':
                    rewind($infile);
                    break;
                }
                
                FileSystem::throwErrors(false);
                $this->_callStatic($classPath, $method, $params, $phpErrHandler, false);
                
                switch($method) {
                case 'createTempFile':
                case 'backupFileToTemp':
                    if(null !== $this->_lastCallStaticResult) {
                        $tempFiles[] = $this->_lastCallStaticResult;
                    }
                    break;
                case 'getUniqueFilename':
                    if(null !== $this->_lastCallStaticResult) {
                        $tempFiles[] = $file = $this->_lastCallStaticResult;
                        file_put_contents($file, 'This file used for testing PSS\'s getUniqueFilename() method');                    }
                    break;
                }                

                switch($method) {
                case 'streamCopyToStream':
                    rewind($infile);
                    break;
                }
                
                FileSystem::throwErrors(true);
                $results = $error = null;
                try {
                    $this->_callStatic($classPath, $method, $params, $phpErrHandler, true);
                    switch($method) {
                    case 'createTempFile':
                    case 'backupFileToTemp':
                        if(null !== $this->_lastCallStaticResult) {
                            $tempFiles[] = $this->_lastCallStaticResult;
                        }
                        break;
                    case 'getUniqueFilename':
                        if(null !== $this->_lastCallStaticResult) {
                            $tempFiles[] = $file = $this->_lastCallStaticResult;
                            file_put_contents($file, 'This file used for testing PSS\'s getUniqueFilename() method');                    }
                        break;
                    }
                } catch (\Throwable $ex) {
                    $error = new Error($ex);
                } catch (\Exception $ex) {  // @TODO clean up once PHP 7 requirement is enforced
                    $error = new Error($ex);
                }
                if(null !== $error) {
                    // $throwError = FileSystem::throwErrors() ? "TRUE" : "FALSE";
                    // $results = "FileSystem::throwErrors = {$throwError}: an exception was thrown";
                    $this->_testResults[] = new TestResult($classPath, $method, $params, false, $results, null, $error, true, true);
                }

                if(file_exists($bogusFile)) {
                    $break = 1;
                }
            }
        }
        
        fclose($infile);
        fclose($outfile);

        foreach(array_reverse($tempFiles) as $file) {
            if(file_exists($file)) {
                chmod($file, 0777);
                if(is_dir($file)) {
                    rmdir($file);
                }
                else {
                    unlink($file);
                }
            }
        }
        
        return $this->_testResults;
    }
}
