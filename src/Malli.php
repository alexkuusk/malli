<?php

declare(strict_types=1); 

namespace Alexkuusk\Malli;

class Malli
{
    private string $path = './';
    private string $file;
    private string $block;
    private ?string $language = '';
    private ?string $code;
    private array $data = [];
    private array $customTags = [];
    private ?Malli $parent = null;
    private string $cachePath = '../tmp/tpl/';
    //TODO: extension '.tpl'

    public function __construct(?array $params = null, ?Malli $parent = null)
    {
        if (is_array($params) && (isset($params['_params']))) {
            $this->setParams($params['_params'] ?? null, $params['_data'] ?? null, $parent);
        }
    }

    public function setParams(?array $params = null, ?array $data = null, ?Malli $parent = null): self
    {
        if (isset($params['file'])) {
            $this->setFile($params['file']);
        } elseif (isset($parent)) {
            $this->setFile($parent->file);
        }
        if (isset($params['path'])) {
            $this->setPath($params['path']);
        } elseif (isset($parent)) {
            $this->setPath($parent->path);
        }
        if (isset($params['cachePath'])) {
            $this->setCachePath($params['cachePath']);
        } elseif (isset($parent)) {
            $this->setCachePath($parent->cachePath);
        }
        if (realpath($this->cachePath) == realpath($this->path)) {
            throw new MalliException('invalid cache path: ' . $this->cachePath);
        }
        if (isset($params['language'])) {
            $this->setLanguage($params['language']);
        } elseif (isset($parent)) {
            $this->setLanguage($parent->getLanguage());
        }
        if (isset($parent)) {
            $this->setParent($parent);
        }

        if (isset($params['block'])) {
            $this->setBlock($params['block']);
        } else {
            $this->setBlock(pathinfo($this->file, PATHINFO_FILENAME));
        }

        $this->setData($data);
        return $this;
    }
 
    static function createSubs(array $data, $parent): array
    {
        foreach ($data as $key => $subParams) {
            if (is_array($subParams)) {
                if (isset($subParams['_params']) || isset($subParams['_data'])) {
                    $data[$key] = (new malli($subParams, $parent));
                } else {
                    $data[$key] = malli::createSubs($subParams, $parent);
                }
            }
        }
        return $data;
    }
 
    public function getParent(): ?Malli
    {
        return $this->parent;
    }
    
    public function setParent(Malli $parent): void
    {
        $this->parent = $parent;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    // you can not set data before params when using subtemplates
    public function setData(?array $data = []): self
    {
        $this->data = $data ?? [];
        if (is_array($data)) {
            $this->data = malli::createSubs($data, $this);
        }
        return $this;
    }

    public function getLanguage(bool $asPath = false): ?string
    {
        return ($this->language && $asPath) ? (trim($this->language, '/') . '/') : $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = preg_replace('/[^a-z]/', '', $language);
    }

    public function setCustomTag(string $pattern, string $replacementCode, bool $makeStatic = false): self
    {
        $this->customTags[] = [$pattern, $replacementCode, $makeStatic];
        return $this;
    }

    public function getCustomTags(): array
    {
        return $this->customTags;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setCachePath(string $cachePath): void
    {
        $this->cachePath = $cachePath;
    }

    public function setBlock(string $block): void
    {
        $this->block = $block;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    protected function getFullPath(): string
    {
        return $this->path . $this->file;
    }
    
    protected function getCachePath(): string
    {
        return $this->cachePath . $this->getLanguage(true) . $this->file;
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
        $fnTpl = $this->getFullPath();
        if (is_readable($fnTpl)) {
            $code = file_get_contents($fnTpl);
        } else {
            throw new MalliException('unable to read template file: ' . $fnTpl);
        }

        $code = $this->getSubBlocks($code);

        foreach ($this->getCustomTags() as [$pattern, $replacementCode, $makeStatic]) {
            if (!$makeStatic) {
                $code = preg_replace($pattern, '<' . '?php ' . $replacementCode . ' ?' . '>', $code);
            } else {$PARSER = &$this;
                $code = preg_replace_callback($pattern, function ($match) use ($replacementCode, $PARSER) {ob_start(); eval($replacementCode); return ob_get_clean();}, $code);
            }
        }

        $fnComp = $this->getCachePath();
        $code = str_replace('{{hash}}', md5($fnComp), $code);

        $code = preg_replace('/\{\{ \/(FOREACH|FOR|IF|) \}\}/', '<' . '?php } ?' . '>', $code); //closing braces

        $code = preg_replace('/\{\{ FOREACH (.+) \}\}/Ums', '<' . '?php foreach (\1) { ?' . '>', $code);
        $code = preg_replace('/\{\{ FOR (.+) \}\}/Ums', '<' . '?php for (\1) { ?' . '>', $code);
        $code = preg_replace('/\{\{ IF (.+) \}\}/Ums', '<' . '?php if (\1) { ?' . '>', $code);
        $code = str_replace('{{ ELSE }}', '<' . '?php } else { ?' . '>', $code);
        $code = preg_replace('/\{\{ ELSEIF (.+) \}\}/Ums', '<' . '?php } elseif { ?' . '>', $code);

        //everything else is plain echo
        //todo: optional escaping with flags
        $code = preg_replace(
            '/\{\{ (.*) \}\}/Ums',
            '<?php echo \1;?>',
            $code);

        if (($res = @file_put_contents($fnComp, $code, LOCK_EX)) === false) {
            if (!is_dir($this->dirname($fnComp))) {
                $this->mkdir($this->dirname($fnComp)); //path probably missing, just create it
            }
            //try again
            $res = @file_put_contents($fnComp, $code, LOCK_EX);
        }
        if (!$res) {
            throw new MalliException('unable to write template file: ' . $fnComp);
        }
        touch($fnComp, filemtime($fnTpl));

        return $code;
    }

    public function render(?array $params = null): string
    {
        if ($params !== null) {
            $this->setParams($params, null);
        }

        $fnComp = $this->getCachePath();

        if (!is_readable($fnComp) || (filemtime($fnComp) !== filemtime($this->getFullPath()))) {
            $this->compile();
        }
        ob_start();
        include_once $fnComp;
        ob_end_clean();

        ob_start();
        $func = '__parsed__' . md5($fnComp) . '_' . $this->block;
        $func($this);
        return ob_get_clean();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    private function mkdir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return is_dir($dir);
    }

    private function dirname(string $path): string
    {
        $dir = dirname($path);
        $dir = str_replace("\\", '/', $dir);

        if ($dir === '/') {
            return '';
        }
        return $dir;
    }
}
