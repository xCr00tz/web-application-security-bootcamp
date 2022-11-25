<?php

namespace Concrete\Core\Console\Command;

use Concrete\Core\Config\DirectFileSaver;
use Concrete\Core\Config\FileLoader;
use Concrete\Core\Config\FileSaver;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Console\Command;
use Exception;
use Illuminate\Filesystem\Filesystem;

class ConfigCommand extends Command
{

    protected $description = 'Set or get configuration parameters.';

    protected $signature = 'c5:config 
        {action : Either "get" or "set"} 
        {item : The config item EG: "concrete.debug.detail"} 
        {value? : The value to set}
        {--g|generated-overrides : Save to generated overrides}';

    /** @var Repository */
    protected $repository;

    protected function configure()
    {
        $this
            ->addEnvOption()
            ->setHelp(<<<EOT
When setting values that may be evaluated as boolean (true/false), null or numbers, but you want to store them as strings, you can enclose those values in single or double quotes.
For instance, with
concrete5 %command.name% set concrete.test_item 1
The new configuration item will have a numeric value of 1. If you want to save the string "1" you have to write
concrete5 %command.name% set concrete.test_item '1'

More info at http://documentation.concrete5.org/developers/appendix/cli-commands#c5-config
EOT
        );
    }

    public function handle(Repository $config, Filesystem $filesystem)
    {
        $repository = $this->getRepository($config, $filesystem);

        $item = $this->argument('item');
        switch ($this->argument('action')) {
            case 'get':
                $this->doGetAction($repository, $item);
                break;

            case 'set':
                $this->doSetAction($repository, $item);
                break;

            default:
                $this->output->error('Invalid action specified, please specify either "set" or "get"');
                break;
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws Exception
     */
    protected function serialize($value)
    {
        $jsonOptions = JSON_PRETTY_PRINT;
        if (defined('JSON_UNESCAPED_SLASHES')) {
            $jsonOptions |= JSON_UNESCAPED_SLASHES;
        }
        $type = gettype($value);
        $result = null;
        switch ($type) {
            case 'array':
                $result = json_encode($value, $jsonOptions);
                break;

            case 'boolean':
                $result = $value ? 'true' : 'false';
                break;

            case 'NULL':
                $result = 'null';
                break;

            case 'integer':
            case 'double':
                $result = (string)$value;
                break;

            case 'string':
                $enquote = false;
                switch ($value) {
                    case 'true':
                    case 'false':
                    case 'null':
                        $enquote = true;
                        break;

                    default:
                        if (preg_match('/^-?\d+(\.\d*)?$/', $value)) {
                            $enquote = true;
                        }
                        break;
                }
                $result = $enquote ? "\"$value\"" : $value;
                break;
        }
        if (!isset($result)) {
            throw new Exception("Unable to represent variable of type '$type'");
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function unserialize($value)
    {
        $result = json_decode($value, true);
        if (is_null($result) && trim(strtolower($value)) !== 'null') {
            return (string)$value;
        }

        return $result;
    }

    /**
     * Complete a requested get action
     *
     * @param $repository
     * @param $item
     */
    private function doGetAction($repository, $item)
    {
        $this->output->writeln($this->serialize($repository->get($item)));
    }

    /**
     * Complete a requested set action
     *
     * @param Repository $repository
     * @param string $item
     */
    private function doSetAction(Repository $repository, $item)
    {
        if (!$this->hasArgument('value')) {
            $this->output->error('A value must be provided when using the "set" action.');
        }

        $value = $this->argument('value');
        $repository->save($item, $this->unserialize($value));
    }

    /**
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @return \Concrete\Core\Config\Repository\Repository
     */
    private function getRepository(Repository $config, Filesystem $filesystem)
    {
        $default_environment = $config->getEnvironment();

        $environment = $this->option('env') ?: $default_environment;

        $file_loader = new FileLoader($filesystem);
        if ($this->option('generated-overrides')) {
            $file_saver = new FileSaver($filesystem);
        } else {
            $file_saver = new DirectFileSaver($filesystem, $environment);
        }

        return new Repository($file_loader, $file_saver, $environment);
    }
}
