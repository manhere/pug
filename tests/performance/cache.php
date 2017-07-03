<?php

use Pug\Pug;

class PugCachePerformanceTest extends PHPUnit_Framework_TestCase
{
    protected function getPerformanceTemplate($template)
    {
        return TEMPLATES_DIRECTORY . DIRECTORY_SEPARATOR . 'performance' . DIRECTORY_SEPARATOR . $template . '.pug';
    }

    protected function getPhpFromTemplate($template)
    {
        return $this->getPhp(file_get_contents($this->getPerformanceTemplate($template)));
    }

    protected function getPhp($template)
    {
        $Pug = new Pug(array(
            'singleQuote' => false,
            'prettyprint' => false,
            'restrictedScope' => true,
        ));

        return trim($Pug->compile($template));
    }

    protected function getHtmlFromTemplate($template, array $vars = array())
    {
        $Pug = new Pug(array(
            'singleQuote' => false,
            'prettyprint' => false,
            'restrictedScope' => true,
        ));

        return trim($Pug->render($this->getPerformanceTemplate($template), $vars));
    }

    /**
     * Cache weight.
     */
    public function testCacheWeihgt()
    {
        $this->assertSame('<p>Hello world!</p>', $this->getPhp('p Hello world!'), 'Simple template should output simple code.');
    }
}
