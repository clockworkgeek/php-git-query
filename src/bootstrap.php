<?php
namespace GitQuery;

/* Constants */
const HEAD = 'HEAD';

const DS = DIRECTORY_SEPARATOR;

const PS = PATH_SEPARATOR;

const LF = "\n";

/* Functions */
function is_stream($handle)
{
    return is_resource($handle) && (get_resource_type($handle) === 'stream');
}

function is_file_mode_read($mode)
{
    return (bool) strpbrk($mode, 'r+');
}

function is_file_mode_write($mode)
{
    return (bool) strpbrk($mode, 'acxw+');
}

/* Initialise */

stream_wrapper_register(ObjectStream::SCHEME, '\GitQuery\ObjectStream');
