<?php

namespace GitQuery;

/* Constants */

const HEAD = 'HEAD';
const DS = DIRECTORY_SEPARATOR;
const PS = PATH_SEPARATOR;
const LF = "\n";

/*  Functions */

function is_stream($handle)
{
    return is_resource($handle) && (get_resource_type($handle) === 'stream');
}

/* Initialise */


