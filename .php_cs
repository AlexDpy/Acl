<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        'long_array_syntax',
        'concat_with_spaces',
    ))
    ->finder($finder)
;
