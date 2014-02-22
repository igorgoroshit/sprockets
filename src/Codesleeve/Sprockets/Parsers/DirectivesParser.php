<?php namespace Codesleeve\Sprockets\Parsers;

class DirectivesParser extends PathParser
{
    /**
     * Returns an array of all the files inside of this manifest file
     * 
     * @param  string $filename
     * @return array
     */
    public function getFilesArrayFromDirectives($filename)
    {
        $included = array();
        $excluded = array();

        $lines = ($filename) ? file($filename) : array();

        foreach ($lines as $line)
        {
            list($include, $exclude) = $this->processDirectiveFromFileLine($line, $filename);

            $included = array_merge($included, $include);
            $excluded = array_merge($excluded, $exclude);
        }

        return array_unique(array_diff($included, $excluded));
    }

    /**
     * A directive can return different things. It used to be that directives could only return an
     * array of files to include. But that didn't allow us to exclude (stub) or bust cache(depend_on)
     * for our files like the asset pipeline does in Rails.
     * 
     * @param  string $line
     * @param  file $filename
     * @return array($include, $exclude, $depend)
     */
    private function processDirectiveFromFileLine($line, $filename, $tokens = array('//=', '*=', '#='))
    {
        $line = ltrim($line);
        
        if (!$line) {
            return $this->structureDirectiveResults(array());
        }

        foreach ($tokens as $token)
        {
            if (strpos($line, $token) === 0)
            {
                $directive = trim(substr($line, strlen($token)));
                $results = $this->processDirective($directive, $filename);

                return $this->structureDirectiveResults($results);
            }
        }

        return $this->structureDirectiveResults(array());

    }

    /**
     * Returns a structured array from $results, to be used like this,
     *
     * list($include, $exclude, $depend) = $this->structureDirectiveResults($results):
     * 
     * @param  array $results
     * @return array($include, $exclude, $depend)
     */
    private function structureDirectiveResults($results)
    {
        if (!$this->isAssociativeArray($results))
        {
            return array($results, array(), array());
        }

        $data = array(array(), array(), array());

        if (array_key_exists('include', $results))
        {
            $data[0] = $results['include'];
        }

        if (array_key_exists('exclude', $results))
        {
            $data[1] = $results['exclude'];
        }

        if (array_key_exists('depend', $results))
        {
            $data[2] = $results['depend'];
        }

        return $data;
    }

    /**
     * Is this array an associative array (does it have string indexes?)
     * 
     * @param  array $array
     * @return boolean
     */
    private function isAssociativeArray($array)
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Returns an array of file(s) based on directive
     * 
     * @param  string $directive
     * @param  string $filename
     * @return array
     */
    private function processDirective($line, $filename)
    {
        foreach ($this->directives as $directive_name => $directive)
        {
            $param = $this->checkForDirective($directive_name, $line);
            
            if ($param)
            {
                $directive->initialize($this, $filename);
                return $directive->process($param);
            }
        }

        $directive = new \Codesleeve\Sprockets\Directives\BaseDirective;
        $directive->initialize($this, $filename);

        return $directive->process(null);
    }

    /**
     * See if the directive and diretive name match and if so, then we have a match and
     * should return the parameters of this directive on this line
     * 
     * @param  string $directive_name
     * @param  string $directive
     * @return 
     */
    private function checkForDirective($directive_name, $directive)
    {
        if (strpos($directive, $directive_name) === 0) {
            $param = trim(substr($directive, strlen($directive_name)));
            return ($param) ? $param : true;
        }

        return null;
    }

}