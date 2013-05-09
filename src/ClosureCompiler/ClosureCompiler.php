<?php

namespace Devize\ClosureCompiler;

/**
 * PHP-wrapper to create an interface between Google's Closure Compiler and your PHP scripts
 *
 * Usage: set basedirs for your files, set names for the source files and resulting target file, and compile.
 *
 *      $compiler = new ClosureCompiler;
 *      $compiler->setSourceBaseDir('path/to/javascript-src/');
 *      $compiler->setTargetBaseDir('path/to/javascript/');
 *
 *      $compiler->addSourceFile('functions.js');
 *      $compiler->addSourceFile('library.js');
 *
 *          to add multiple files:
 *
 *      $compiler->setSourceFiles(array('one.js', 'two.js', 'three.js'));
 *
 *
 *      $compiler->setTargetFile('minified.js');
 *      $compiler->compile();
 *
 * @author Peter Breuls <breuls@devize.nl>
 */
class ClosureCompiler
{

    protected $compilerJar = 'compiler-latest/compiler.jar';
    
    protected $config = array(
        'sourceBaseDir' => '',
        'targetBaseDir' => '',
        'sourceFileNames' => array(),
        'targetFileName' => 'compiled.js',
    );

    public function __construct()
    {
        $this->compilerJar = realpath(__DIR__ . '/../../' . $this->compilerJar);
    }

    public function getBinary()
    {
        return 'java -jar ' . $this->compilerJar;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setSourceBaseDir($path = '')
    {
        if (file_exists($path)) {
            $this->config['sourceBaseDir'] = rtrim($path, '/') . '/';
        } elseif (empty($path)) {
            $this->config['sourceBaseDir'] = '';
        } else {
            throw new CompilerException("The path '{$path}' does not seem to exist.");
        }
    }

    public function setTargetBaseDir($path = '')
    {
        if (file_exists($path)) {
            $this->config['targetBaseDir'] = rtrim($path, '/') . '/';
        } elseif (empty($path)) {
            $this->config['targetBaseDir'] = '';
        } else {
            throw new CompilerException("The path '{$path}' does not seem to exist.");
        }
    }

    public function clearSourceFiles()
    {
        $this->config['sourceFileNames'] = array();
    }

    public function addSourceFile($file)
    {
        $path = $this->config['sourceBaseDir'] . $file;
        if (in_array($path, $this->config['sourceFileNames'])) {
            return;
        }
        if (file_exists($path)) {
            $this->config['sourceFileNames'][] = $path;
        } else {
            throw new CompilerException("The path '{$path}' does not seem to exist.");
        }
    }

    public function setSourceFiles(array $files, $reset = true)
    {
        if ($reset === true) {
            $this->clearSourceFiles();
        }
        foreach ($files as $file) {
            $this->addSourceFile($file);
        }
    }

    public function removeSourceFile($file)
    {
        $path = $this->config['sourceBaseDir'] . $file;
        $index = array_search($path, $this->config['sourceFileNames']);
        if ($index !== false) {
            unset($this->config['sourceFileNames'][$index]);
        }
    }

    public function setTargetFile($file)
    {
        $path = $this->config['targetBaseDir'] . $file;
        if (file_exists($path)) {
            $this->config['targetFileName'] = $path;
        } else {
            throw new CompilerException("The path '{$path}' does not seem to exist.");
        }
    }

    public function compile()
    {
        # check for possible overwrite of source files
        if (in_array($this->config['targetFileName'], $this->config['sourceFileNames'])) {
            throw new CompilerException("The target file '{$this->config['targetFileName']}' is one of the source files. A compile would cause undesired effects.");
        }

        # check for path
        if (basename($this->config['targetFileName']) === $this->config['targetFileName'] and !empty($this->config['targetBaseDir'])) {
            $this->config['targetFileName'] = $this->config['targetBaseDir'] . $this->config['targetFileName'];
        }

        $command = $this->getBinary();
        foreach ($this->config['sourceFileNames'] as $file) {
            $command .= " --js={$file}";
        }

        $command .= " --js_output_file={$this->config['targetFileName']} 2>&1";

        $return = '';
        $output = array();
        exec($command, $output, $return);
        return $return;
    }

}

