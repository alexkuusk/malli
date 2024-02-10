<?php
//NB!   preg_replace e-modifier ei saa hakkama suurema templiga kui 100k, vt ini_get('pcre.backtrack_limit')  ini_set('pcre.backtrack_limit', 100000);
// http://www.php.net/manual/en/pcre.configuration.php#ini.pcre.backtrack-limit

declare(strict_types=1); 

namespace alexkuusk\malli;

//use malliException;
//use alexkuusk\malli\malli\malliExecption as malliException;

class malli
{
    private string $path = './';
    private string $file;
    private ?string $language = '';
    private ?string $code;
    private array $data = [];
    private ?string $cacheFile = null;
    //TODO: extension '.tpl'

    public function __construct(?array $params = null, ?malli $parent = null)
    {//echo '<pre>';print_r([$params, $parent]);
        if (is_array($params) && (isset($params['_params']))) {
            $this->setParams($params['_params'] ?? null, $params['_data'] ?? null, $parent);
            //print_r(['here', $parent]);
        }
    }

    public function setParams(?array $params = null, ?array $data = null, ?malli $parent = null): void
    {//print_r(['here', $parent]);
        if (isset($params['file'])) {
            $this->setFile($params['file']);
        } elseif (isset($parent)) {
            $this->setFile($parent->getFile());
        }
        if (isset($params['path'])) {
            $this->setPath($params['path']);
        } elseif (isset($parent)) {//print_r(['here']);
            $this->setPath($parent->getPath());
        }
        if (isset($params['language'])) {
            $this->setLanguage($params['language']);
        } elseif (isset($parent)) {
            $this->setLanguage($parent->getLanguage());
        }
        $this->setBlock($params['block']);

        if (is_array($data)) {
            $data = malli::createSubs($data, $this);
        }
        $this->data = $data;
    }
 
    static function createSubs(array $data, $parent) {
        foreach ($data as $key => $subParams) {//todo:rekursiivselt kõik andmed läbi käia, mitte ainult esimene tase
            if (is_array($subParams)) {
                if (isset($subParams['_params']) || isset($subParams['_data'])) {
                    $data[$key] = (string)(new malli($subParams, $parent));
                //print_r([$key]);
                } else {
                    $data[$key] = malli::createSubs($subParams, $parent);
                }
            }
        }
        return $data;
    }
 
    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getBlock(): ?string
    {
        return $this->block;
    }
    
    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getLanguage(bool $asPath = false): ?string
    {
        return ($this->language && $asPath) ? (trim($this->language, '/') . '/') : '';
    }
    
    public function setPath(string $path): void
    {
        $this->path = $path;
    }
   
    public function setBlock(string $block): void
    {
        $this->block = $block;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getFullPath(): string
    {
        return $this->getPath() . '' . $this->getLanguage(true) . '/' . $this->getFile();
    }
    
    public function getCompiledPath(): string
    {
        return $this->getPath() . 'compiled/' . $this->getLanguage(true) . '/' . $this->getFile();
    }

    private function getSubBlocks($code): string
    {
        preg_match_all(
            '/\{\{ BLOCK:(?<name>\w+) \}\}(?<body>.*)\{\{ \/BLOCK:\1 \}\}/Ums',
            $code,
            $blocks,
            PREG_SET_ORDER
        );

        $ret = '';
        foreach ($blocks as $block) {
            $body = preg_replace(
                '/\{\{ BLOCK:(\w+) \}\}.*\{\{ \/BLOCK:\1 \}\}/Ums',
                '<' . '?php /* block:\1 */ ?' . '>',
                $block['body']
            );

            $ret .= '<?php function __parsed__{{hash}}_' . $block['name'] . '($PARSER){extract($PARSER->getData());?>' 
                . $body . '<?php }; ?>' 
                . $this->getSubBlocks($block['body']);
        }

        return $ret;
    }

    private function compile(): string
    {
        $fn = $this->getFullPath();
        if (is_readable($fn)) {
            $code = file_get_contents($fn);
        } else {
            throw new malliException('unable to read template file: ' . $fn);
        }

        $code = $this->getSubBlocks($code);

        $code = str_replace('{{hash}}', md5($fn), $code);

        //everything else is plain echo
        //todo: optional escaping with flags
        $code = preg_replace(
            '/\{\{ (.*) \}\}/Ums',
            '<?php echo \1;?>',
            $code);

        $fn = $this->getCompiledPath();
        if (($res = @file_put_contents($fn, $code, LOCK_EX)) === false) {
            if (!is_dir($this->dirname($fn))) {
                $this->mkdir($this->dirname($fn)); //ilmselt kaust puudu, tekitame
            }
            //proovime uuesti
            $res = @file_put_contents($fn, $code, LOCK_EX);
        } 
        if (!$res) {
            throw new malliException('unable to write template file: ' . $fn);
        }

        return $code;
    }

    public function render(?array $params = null): string
    {
        if ($params !== null) {
            $this->setParams($params, null);
        }

        $fn = $this->getCompiledPath();

        if (!is_readable($fn) || (filemtime($fn) !== filemtime($this->getFullPath()))) {
            $this->compile();
        }

        include_once $fn;
        ob_start();
        $func = '__parsed__' . md5($this->getFullPath()) . '_' . $this->getBlock();
        $func($this);
        $rendered = ob_get_contents();
        ob_end_clean();
        return $rendered;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    private function mkdir($dir)
    {
        if (!is_dir($dir))
        {
            mkdir($dir, 0777, true);
        }
        return is_dir($dir);
    }

    private function dirname(string $path): string
    {
        $dir = dirname($path);
        $dir = str_replace("\\", '/', $dir);

        if ($dir === '/')
        {
            return '';
        }
        return $dir;
    }
}
