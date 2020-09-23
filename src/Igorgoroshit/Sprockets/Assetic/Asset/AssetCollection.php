<?php namespace Igorgoroshit\Sprockets\Assetic\Asset;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Assetic\Asset\AssetCollection as AsseticAssetCollection;

use axy\sourcemap\SourceMap;

class AssetCollection extends AsseticAssetCollection {

    public function dump(FilterInterface $additionalFilter = null)
    {
        $parts = array();
        
        foreach ($this as $asset) {
            $parts[] = $asset->dump($additionalFilter);
        }

        return implode("\n", $parts);
    }

    public function sourcemap(FilterInterface $additionalFilter = null, $parser = null)
    {
        $map                = new SourceMap();
        $prefix             = $parser->config['routing']['prefix'];
        $map->sourceRoot    = url('/') . $prefix . '/';
        $line               = 0;
        $lines              = 0;
        $fileIndex          = 0;
        $count              = 0;
        $content            = '';
        $parts              = array();

        foreach ($this as $asset) {

            $fileName   = $asset->getSourceRoot().'/'.$asset->getSourcePath();
            $filePath   = $parser->absolutePathToWebPath($fileName);

            $content = $asset->dump($additionalFilter);

            $lines = substr_count($content, "\n");
            $i = 0;

			$map->sources->setContent($filePath, null);

            for($i = 0; $i <= $lines; $i++)
            {
				$map->addPosition([

	                'generated' => [
	                    'line' => $line + $i,
	                    'column' => 0
	                ],

	                'source' => [
	                    'fileIndex' => $fileIndex,
	                    'line' => $i,
	                    'column' => 0
	                ]

	            ]);            	
            }

            $fileIndex++;


            $line += ($lines + 1);
        }

        return $map->getData();
    }

}