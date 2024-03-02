<?php /** @noinspection UnknownInspectionInspection */

namespace eftec\routeone;

use eftec\CliOne\CliOne;
use eftec\CliOne\CliOneParam;
use JsonException;
use RuntimeException;

class RouteOneCli
{
    /** @var CliOne */
    public CliOne $cli;
    public const VERSION = "1.1";
    /** @var RouteOne|null */
    public ?RouteOne $route= null;
    public array $paths = [];

    /**
     * The constructor
     * @throws JsonException
     */
    public function __construct(bool $run = true)
    {
        $this->route = new RouteOne();
        $this->cli = CliOne::instance();
        $this->cli->debug=true;
        if (!CliOne::hasMenu()) {
            $this->cli->setErrorType();
            $this->cli->addMenu('mainmenu',
                function($cli) {
                    $cli->upLevel('main menu');
                    $cli->setColor(['byellow'])->showBread();
                }
                , function(CliOne $cli) {
                    $cli->downLevel(2);
                });
        }
        $this->cli->addMenuService('mainmenu', $this);
        $this->cli->addMenuItem('mainmenu', 'router',
            '[{{routerconfigfull}}] Configure the router', 'navigate:routermenu');
        $this->cli->addMenu('routermenu',
            function($cli) {
                $cli->upLevel('router menu');
                $cli->setColor(['byellow'])->showBread();
            }
            , 'footer');
        /**
         * The next comments are used to indicate that the methods indicated are used here.
         * @see RouteOneCli::menurouteroneconfigure
         * @see RouteOneCli::menurouteronehtaccess
         * @see RouteOneCli::menurouteronerouter
         * @see RouteOneCli::menurouteronepaths
         * @see RouteOneCli::menurouteroneload
         * @see RouteOneCli::menurouteronesave
         */
        $this->cli->addMenuItems('routermenu', [
            'configure' => ['[{{routerconfig}}] configure and connect to the database', 'routeroneconfigure'],
            'htaccess' => ['[{{routerconfigfull}}] create the htaccess file', 'routeronehtaccess'],
            'router' => ['[{{routerconfigfull}}] create the PHP router file', 'routeronerouter'],
            'paths' => ['[{{routerconfigpath}}] modify the paths', 'routeronepaths'],
            'load' => [' load the configuration', 'routeroneload'],
            'save' => ['[{{routerconfigpath}}] save the configuration', 'routeronesave'],
        ]);
        //$this->cli->addMenuItem('pdooneconnect');
        $this->cli->setVariable('routerconfig', '<red>pending</red>');
        $this->cli->setVariable('routerconfigpath', '<red>pending</red>');
        $this->cli->setVariable('routerconfigfull', '<red>pending</red>');
        $this->cli->addVariableCallBack('router', function(CliOne $cli) {
            if ($cli->getValue('dev')) {
                $file = true;
                $cli->setVariable('routerconfig', '<green>ok</green>', false);
            } else {
                $file = false;
                $cli->setVariable('routerconfig', '<red>pending</red>', false);
            }
            if (count($this->paths) > 0) {
                $path = true;
                $cli->setVariable('routerconfigpath', '<green>ok</green>', false);
            } else {
                $path = false;
                $cli->setVariable('routerconfigpath', '<red>pending</red>', false);
            }
            if ($file && $path) {
                $cli->setVariable('routerconfigfull', '<green>ok</green>', false);
            } else {
                $cli->setVariable('routerconfigfull', '<red>pending</red>', false);
            }
        });
        $listPHPFiles = $this->getFiles('.', '.config.php');
        $routerFileName = $this->cli->createOrReplaceParam('routerfilename', [], 'longflag')
            ->setRequired(false)
            ->setCurrentAsDefault()
            ->setDescription('select a configuration file to load', 'Select the configuration file to use', [
                    'Example: <dim>"--routerfilename myconfig"</dim>']
                , 'file')
            ->setDefault('')
            ->setInput(false, 'string', $listPHPFiles)
            ->evalParam();
        $this->routerOneLoad($routerFileName);
        if ($run) {
            if ($this->cli->getSTDIN() === null) {
                $this->showLogo();
            }
            $this->cli->evalMenu('mainmenu', $this);
        }

    }

    public function showLogo(): void
    {
        echo "  _____             _        ____             \n";
        echo " |  __ \           | |      / __ \            \n";
        echo " | |__) |___  _   _| |_ ___| |  | |_ __   ___ \n";
        echo " |  _  // _ \| | | | __/ _ \ |  | | '_ \ / _ \\\n";
        echo " | | \ \ (_) | |_| | ||  __/ |__| | | | |  __/\n";
        echo " |_|  \_\___/ \__,_|\__\___|\____/|_| |_|\___| " . self::VERSION . "\n\n";
        echo "\n";
    }

    public function option(): void
    {
        $this->cli->createOrReplaceParam('init', [], 'command')->add();
    }

    /**
     * @throws JsonException
     */
    public function menuRouterOnePaths(): void
    {
        $this->cli->upLevel('paths');
        //$this->cli->setColor(['byellow'])->showBread();
        while (true) {
            $this->cli->setColor(['byellow'])->showBread();
            $this->cli->showValuesColumn($this->paths, 'option');
            $ecc = $this->cli->createOrReplaceParam('extracolumncommand')
                ->setAllowEmpty()
                ->setInput(true, 'optionshort', ['add', 'remove', 'edit'])
                ->setDescription('', 'Select an operation')
                ->evalParam(true);
            switch ($ecc->value) {
                case '':
                    break 2;
                case 'add':
                    $tmp = $this->cli->createOrReplaceParam('selectpath')
                        //->setAllowEmpty()
                        ->setInput()
                        ->setDescription('', 'Select the name of the path',
                            ['select an unique name for this path','example:web'])
                        ->evalParam(true);
                    $tmp2 = $this->cli->createOrReplaceParam('extracolumn_sql')
                        //->setAllowEmpty()
                        ->setInput()
                        ->setDescription('', 'Select the path (? for help)',
                            ['select the path to be used using the syntax:',
                                'fixedpath/{requiredvalue}/{optionalvalue:defaultvalue}',
                                'Example:{controller:Home}/{id}/{idparent} '
                                ,'{controller}: the controller'
                                ,'{action}: the action'
                                ,'{id}: the identifier'
                                ,'{idparent}: the parent object'
                                ,'{category}: the category'
                                ,'{subcategory}: the subcategory'
                                ,'{subsubcategory}: the subsubcategory'])
                        ->setDefault('{controller:Home}/{action:list}/{id}/{idparent}')
                        ->evalParam(true);
                    $tmp3 = $this->cli->createOrReplaceParam('extracolumn_namespace')
                        //->setAllowEmpty()
                        ->setInput()
                        ->setDescription('', 'Select the namespace associate with the path',
                            ['example: eftec\\controller'])
                        ->setDefault('eftec\\controller')
                        ->evalParam(true);
                    $this->paths[$tmp->value] = $tmp2->value . ', ' . $tmp3->value;
                    break;
                case 'remove':
                    $tmp = $this->cli->createOrReplaceParam('extracolumn_delete')
                        ->setAllowEmpty()
                        ->setInput(true, 'option', $this->paths)
                        ->setDescription('', 'Select the column to delete')
                        ->evalParam(true);
                    if ($tmp->valueKey !== $this->cli->emptyValue) {
                        unset($this->paths[$tmp->valueKey]);
                    }
                    break;
                case 'edit':
                    $tmp = $this->cli->createOrReplaceParam('extracolumn_edit')
                        ->setAllowEmpty()
                        ->setInput(true, 'option', $this->paths)
                        ->setDescription('', 'Select the column to edit')
                        ->evalParam(true);
                    if ($tmp->valueKey !== $this->cli->emptyValue) {
                        $v = explode(', ', $this->paths[$tmp->valueKey], 2);
                        $tmp2 = $this->cli->createOrReplaceParam('extracolumn_sql')
                            //->setAllowEmpty()
                            ->setInput()
                            ->setDescription('', 'Select the path',
                                ['select the path to be used using the syntax {id:defaultvalue}',
                                    'example:{controller:Home}/{id}/{idparent} '
                                    ,'{controller}: the controller'
                                    ,'{action}: the action'
                                    ,'{event}: the event'
                                    ,'{verb}: the verb (GET/POST/etc.)'
                                    ,'{id}: the identifier'
                                    ,'{idparent}: the parent object'
                                    ,'{category}: the category'
                                    ,'{subcategory}: the subcategory'
                                    ,'{subsubcategory}: the subsubcategory'])
                            ->setDefault($v[0])
                            ->evalParam(true);
                        $tmp3 = $this->cli->createOrReplaceParam('extracolumn_sql2')
                            //->setAllowEmpty()
                            ->setInput()
                            ->setDescription('', 'Select the namespace', ['example: eftec\\controller'])
                            ->setDefault($v[1])
                            ->evalParam(true);
                        $this->paths[$tmp->valueKey] = $tmp2->value . ', ' . $tmp3->value;
                    }
                    break;
            }
        }
        $this->cli->callVariablesCallBack();
        $this->cli->downLevel(2);
    }

    /** @noinspection PhpUnused */
    /**
     * @throws JsonException
     */
    public function menuRouterOneSave(): void
    {
        $this->cli->upLevel('save');
        $this->cli->setColor(['byellow'])->showBread();
        $sg = $this->cli->createParam('yn', [], 'none')
            ->setDescription('', 'Do you want to save the configurations of connection?')
            ->setInput(true, 'optionshort', ['yes', 'no'])
            ->setDefault('yes')
            ->evalParam(true);
        if ($sg->value === 'yes') {
            $saveconfig = $this->cli->getParameter('routerfilename')->setInput()->evalParam(true);
            if ($saveconfig->value) {
                $r = $this->cli->saveDataPHPFormat($this->cli->getValue('routerfilename'), $this->getConfig());
                if ($r === '') {
                    $this->cli->showCheck('OK', 'green', 'file saved correctly');
                } else {
                    $this->cli->showCheck('ERROR', 'red', 'unable to save file :' . $r);
                }
            }
        }
        $this->cli->downLevel();
    }

    /** @noinspection PhpUnused */
    public function menuRouterOneHtaccess(): void
    {
        $file = 'index.php';
        $content = $this->openTemplate(__DIR__ . '/templates/htaccess_template.php');
        $content = str_replace('changeme.php', $file, $content);
        $this->validateWriteFile('.htaccess', $content);
    }

    public function menuRouterOneRouter(): void
    {
        $config = $this->getConfig();
        $file = 'index.php';
        $content = "<?php\n" . $this->openTemplate(__DIR__ . '/templates/route_template.php');
        $namespaces = [];
        $paths = [];
        foreach ($this->paths as $k => $v) {
            $part = explode(', ', $v);
            $namespaces[$k] = $part[1];
            $paths[$k] = $part[0];
        }
        $content = str_replace([
            '{{baseurldev}}', '{{baseurlprod}}', '{{dev}}', '{{namespaces}}', '{{paths}}'
        ],
            [
                $config['baseurldev'], $config['baseurlprod'], $config['dev'], var_export($namespaces, true), var_export($paths, true)
            ], $content);
        $this->validateWriteFile($file, $content);
    }

    public function validateWriteFile(string $file, string $content): bool
    {
        $fail = false;
        $exists = @file_exists(getcwd() . '/' . $file);
        if ($exists) {
            $this->cli->showCheck('warning', 'yellow', "$file file exists, skipping");
            $fail = true;
        } else {
            $result = @file_put_contents(getcwd() . '/' . $file, $content);
            if (!$result) {
                $this->cli->showCheck('error', 'red', "Unable to write " . getcwd() . '/' . "$file file\n");
                $fail = true;
            } else {
                $this->cli->showCheck('ok', 'green', "OK");
            }
        }
        return $fail;
    }

    /**
     * @param $filename
     * @return false|string
     */
    public function openTemplate($filename)
    {
        $template = @file_get_contents($filename);
        if ($template === false) {
            throw new RuntimeException("Unable to read template file $filename");
        }
        // we delete and replace the first line.
        return substr($template, strpos($template, "\n") + 1);
    }

    /** @noinspection PhpUnused */
    /**
     * @throws JsonException
     */
    public function menuRouterOneload(): void
    {
        $this->cli->upLevel('load');
        $this->cli->setColor(['byellow'])->showBread();
        $routerFileName = $this->cli->getParameter('routerfilename')
            ->setInput()
            ->evalParam(true);
        $this->routerOneLoad($routerFileName);
        $this->cli->downLevel();
    }

    public function routerOneLoad(CliOneParam $routerFileName): void
    {
        if ($routerFileName->value) {
            $r = $this->cli->readDataPHPFormat($this->cli->getValue('routerfilename'));
            if ($r !== null && $r[0] === true) {
                $this->cli->showCheck('OK', 'green', 'file read correctly');
                $this->setConfig($r[1]);
            } else {
                $this->cli->showCheck('ERROR', 'red', 'unable to read file ' .
                    $this->cli->getValue('routerfilename') . ", cause " . $r[1]);
            }
        }
    }

    /**
     * @throws JsonException
     */
    public function menuRouterOneConfigure(): void
    {
        $this->cli->upLevel('configure');
        $this->cli->setColor(['byellow'])->showBread();
       /* $this->cli->createOrReplaceParam('routerfilename', [], 'onlyinput')
            ->setDescription('The router filename', 'Select the router filename', [
                'example: index.php'])
            ->setInput(true, 'string', 'index.php')
            ->setCurrentAsDefault()
            ->evalParam(true);*/
        $this->cli->createOrReplaceParam('dev', [], 'none')
            ->setDefault(gethostname())
            ->setCurrentAsDefault()
            ->setDescription('', "What is the name of your dev machine", [
                'Select the name of your dev machine',
                'If you don\' know it, then select any information'])
            ->setInput()
            ->evalParam(true);
        $this->cli->createOrReplaceParam('baseurldev', [], 'none')
            ->setDefault('http://localhost')
            ->setDescription('the base url', 'Select the base url(dev)',
                ['Example: <dim>https://localhost</dim>'], 'baseurldev')
            ->setRequired(false)
            ->setCurrentAsDefault()
            ->setInput()
            ->evalParam(true);
        $this->cli->createOrReplaceParam('baseurlprod', [], 'none')
            ->setDefault('https://www.domain.dom')
            ->setDescription('the base url', 'Select the base url(prod)',
                ['Example: <dim>https://localhost</dim>'], 'baseurlprod')
            ->setRequired(false)
            ->setCurrentAsDefault()
            ->setInput()
            ->evalParam(true);
        $this->cli->callVariablesCallBack();
        $this->cli->downLevel(2);
    }

    public function getConfig(): array
    {
        $r= $this->cli->getValueAsArray([ 'baseurldev', 'baseurlprod', 'dev']);
        $r['dev']= $r['dev']==='yes'?gethostname():'';
        $r['paths']=$this->paths;
        return $r;
    }

    public function setConfig(array $array): void
    {
        $this->paths = $array['paths'];
        unset($array['paths']);
        $this->cli->setParamUsingArray($array, [ 'baseurldev', 'baseurlprod', 'dev']);
        $this->cli->callVariablesCallBack();
    }

    /***
     * It finds the vendor path (where composer is located).
     * @param string|null $initPath
     * @return string
     *
     */
    public static function findVendorPath(?string $initPath = null): string
    {
        $initPath = $initPath ?: __DIR__;
        $prefix = '';
        $defaultvendor = $initPath;
        // finding vendor
        for ($i = 0; $i < 8; $i++) {
            if (@file_exists("$initPath/{$prefix}vendor/autoload.php")) {
                $defaultvendor = "{$prefix}vendor";
                break;
            }
            $prefix .= '../';
        }
        return $defaultvendor;
    }

    /**
     * It gets a list of files filtered by extension.
     * @param string $path
     * @param string $extension . Example: ".php", "php" (it could generate false positives)
     * @return array
     */
    protected function getFiles(string $path, string $extension): array
    {
        $scanned_directory = array_diff(scandir($path), ['..', '.']);
        $scanned2 = [];
        foreach ($scanned_directory as $k) {
            $fullname = pathinfo($k)['extension'] ?? '';
            if ($this->str_ends_with($fullname, $extension)) {
                $scanned2[$k] = $k;
            }
        }
        return $scanned2;
    }

    /**
     * for PHP <8.0 compatibility
     * @param string $haystack
     * @param string $needle
     * @return bool
     *
     */
    protected function str_ends_with(string $haystack, string $needle): bool
    {
        $needle_len = strlen($needle);
        $haystack_len = strlen($haystack);
        if ($haystack_len < $needle_len) {
            return false;
        }
        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, -$needle_len));
    }
}
