<?php namespace Igorgoroshit\Sprockets;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Igorgoroshit\Sprockets\Assetic\Asset\AssetCollection;

use axy\sourcemap\SourceMap;

class SprocketsFilter implements FilterInterface
{
    public function __construct($parser, $generator)
    {
        $this->parser = $parser;
        $this->generator = $generator;
    }

    public function filterLoad(AssetInterface $asset)
    {

    }

    public function filterDump(AssetInterface $asset)
    {
        $content = "";

        $files = array();

        $absolutePath = $asset->getSourceRoot() . '/' . $asset->getSourcePath();
//die($absolutePath);
        $this->parser->mime = $this->parser->mimeType($absolutePath);

        $absoluteFilePaths = $this->parser->getFilesArrayFromDirectives($absolutePath);

        foreach ($absoluteFilePaths as $absoluteFilePath)
        {
            $files[] = $this->generator->cachedFile($absoluteFilePath);
        }

        // this happens when the file isn't a manifest
        if (!$absoluteFilePaths)
        {
            $addMaps = false;
            $files[] = $this->generator->cachedFile($absolutePath);
        }
        // this happens when thie file is a manifest
        else
        {
            $addMaps = $this->parser->config['sourcemaps'];
            //print_r($this->parser);die();
        }

        $global_filters = $this->parser->get("sprockets_filters.{$this->parser->mime}", array());

        // handle ASI issue with javascripts
        if ($this->parser->mime == "javascripts")
        {
            $global_filters = array_merge($global_filters, array(new Filters\JavascriptConcatenationFilter));
        }

        $collection = new AssetCollection($files, $global_filters);

        if(!isset($this->generator->sourcemap))
        {
            $content = $collection->dump();
            //build source map url
            if(!in_array($asset->getSourcePath(), $this->parser->config['block-maps-for']))
            {
                $prefix = $this->parser->config['routing']['prefix'];
                if($addMaps)
                {
                    $sourceMapUrl = url('/') . $prefix . '/' . $this->parser->absolutePathToWebPath($absolutePath).'.map';
                    $content .= "\n//# sourceMappingURL=$sourceMapUrl";              
                }
        
            }

        }else{
            $map = $collection->sourcemap(null, $this->parser);
            $content = json_encode($map);
        }

        $asset->setContent($content);
    }

}