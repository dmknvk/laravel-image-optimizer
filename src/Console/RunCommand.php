<?php

namespace Konovalchuk\LaravelImageOptimizer\Console;

use Illuminate\Console\Command;

class RunCommand extends Command
{
    const AVAILABLE_MIME_TYPES = ['image/png', 'image/jpeg'];
    const PNG_TOOL             = 'optipng';
    const JPEG_TOOL            = 'jpegoptim';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image-optimizer:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run image optimization (requires root privileges)';

    /**
     * Images versions structure
     *
     * @var array
     */
    private $versions;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // check for installed tools
        if($this->checkTools() == false) {
            $this->error('Image optimization tools not installed. Please read installation requirements');
            return;
        }

        // check for sudo
        if($this->checkSudo() == false) {
            $this->error('This script must be run as root to avoid problems with permissions.');
            return;
        }

        // preload saved versions stamps
        $this->loadVersionsData();

        // get dirs from config
        $dirs = config('image-optimizer.dirs');

        // go
        foreach($dirs as $key => $value) {
            // get correct params from config
            $params = $this->parseDirConfigParams($key, $value);

            // no types -> no job
            if(count($params->get('types')) == 0) continue;

            // work
            $this->optimize($params);
        }

        // save versions stamps
        $this->saveVersionsData();

        // thats all :)
        $this->info('All done!');
    }

    /**
     * Parses dirs config row and returns correct structure
     *
     * @param mixed $key   Key of config row
     * @param mixed $value Value of config row
     *
     * @return Collection
     */
    protected function parseDirConfigParams($key, $value)
    {
        $params = [];

        // parse correct params from config row
        if(is_numeric($key)) {
            $params['dir']       = $value;
            $params['types']     = self::AVAILABLE_MIME_TYPES;
            $params['recursive'] = true;
        } else {
            $params['dir']       = $key;
            $params['types']     = (isset($value['types']) && is_array($value['types']))
                                    ? array_intersect($value['types'], self::AVAILABLE_MIME_TYPES)
                                    : self::AVAILABLE_MIME_TYPES;
            $params['recursive'] = (isset($value['recursive']) && is_bool($value['recursive']))
                                    ? $value['recursive']
                                    : true;
        }

        return collect($params);
    }

    /**
     * Optimizes one config row
     *
     * @param Collection $params Structured parameters
     */
    protected function optimize($params)
    {
        $this->info('Search files in folder' . ($params->get('recursive') ? ' (recursively)' : '') . ': ' . $params->get('dir'));

        $files          = $this->findFiles($params->get('dir'), $params->get('recursive'));
        $bar            = $this->output->createProgressBar(count($files));
        $optimizedCount = 0;

        $this->info('Found ' . count($files) . ' files. Start optimizing...');

        foreach($files as $file) {
            // tick the progress bar
            $bar->advance();

            try {
                $type = mime_content_type($file);

                // work only with correct mime types
                if(in_array($type, $params->get('types'))) {
                    // skip not changed file
                    if( ! $this->isFileChanged($file)) {
                        continue;
                    }

                    $isOptimized = false;
                    $chown       = $this->getChown($file);

                    // optimize files by type
                    switch ($type) {
                        case 'image/png':
                            $this->optimizePng($file);
                            $isOptimized = true;
                            break;

                        case 'image/jpeg':
                            $this->optimizeJpeg($file);
                            $isOptimized = true;
                            break;
                    }

                    // save file hash and owner/group
                    if($isOptimized) {
                        $this->saveVersionHash($file);
                        $this->setChown($file, $chown);
                        $optimizedCount++;
                    }
                }
            } catch(\Exception $e) {
                // something went wrong
                $this->error('Error in file: `' . $file . '`. Message: ' . $e->getMessage());
            }
        }

        // set progress bar to 100%
        $bar->finish();

        $this->info("\n" . 'Optimized ' . $optimizedCount . ' images.' . "\n");
    }

    /**
     * Optimizes png file
     *
     * @param string $file File path to optimize
     */
    protected function optimizePng($file)
    {
        shell_exec(self::PNG_TOOL . ' -o7 -silent "' . $file. '"');
    }

    /**
     * Optimizes jpeg file
     *
     * @param string $file File path to optimize
     */
    protected function optimizeJpeg($file)
    {
        shell_exec(self::JPEG_TOOL . ' --strip-all "' . $file. '"');
    }

    /**
     * Gets stats of file
     *
     * @param string $file File path
     *
     * @return array
     */
    protected function getChown($file)
    {
        return stat($file);
    }

    /**
     * Sets file owner and group
     *
     * @param string $file File path
     * @param array $chown File stats
     *
     * @return array
     */
    protected function setChown($file, $chown)
    {
        return chown($file, $chown['uid']) && chgrp($file, $chown['gid']);
    }

    /**
     * Checks file hash
     *
     * @param string $file File path
     *
     * @return boolean
     */
    protected function isFileChanged($file)
    {
        $md5 = md5_file($file);

        if( ! isset($this->versions[$file]) || $this->versions[$file] != $md5) {
            return true;
        }

        return false;
    }

    /**
     * Saves file hash
     *
     * @param string $file File path
     *
     * @return boolean
     */
    protected function saveVersionHash($file)
    {
        $this->versions[$file] = md5_file($file);
    }

    /**
     * Find all files in directory (with recursive)
     *
     * @param string  $dir Directory path
     * @param boolean $isRecursive Is recursive search
     *
     * @return boolean
     */
    protected function findFiles($dir, $isRecursive)
    {
        $dirStream = opendir($dir);
        $files     = [];

        while(($file = readdir($dirStream)) !== false) {
            if($file != "." && $file != "..") {
                $path = $dir . '/' . $file;

                if(is_file($path)) {
                    $files[] = $path;
                }

                if($isRecursive && is_dir($path)) {
                    $files = array_merge($files, $this->findFiles($path, $isRecursive));
                }
            }
        }

        closedir($dirStream);

        return $files;
    }

    /**
     * Checks needed optimize tools
     *
     * @return boolean
     */
    protected function checkTools()
    {
        return $this->commandExist(self::PNG_TOOL) && $this->commandExist(self::JPEG_TOOL);
    }

    /**
     * Checks is script running via root
     *
     * @return boolean
     */
    protected function checkSudo()
    {
        return posix_getuid() == 0;
    }

    /**
     * Checks cmd in system
     *
     * @return boolean
     */
    protected function commandExist($cmd) {
        return (empty(shell_exec("which $cmd")) ? false : true);
    }

    /**
     * Gets versions file path
     *
     * @return boolean
     */
    private function getVersionsFilePath()
    {
        $dir  = storage_path() . '/image-optimizer';
        $file = $dir . '/rev-manifest.json';

        if( ! is_dir($dir)) {
            mkdir($dir);
        }

        return $file;
    }

    /**
     * Preload versions file
     */
    private function loadVersionsData()
    {
        $file = $this->getVersionsFilePath();

        try {
            $this->versions = json_decode(file_get_contents($file), true);
        } catch(\Exception $e) {
            $this->versions = [];
            $this->saveVersionsData();
        }
    }

    /**
     * Save versions file
     */
    private function saveVersionsData()
    {
        file_put_contents($this->getVersionsFilePath(), json_encode($this->versions));
    }
}
