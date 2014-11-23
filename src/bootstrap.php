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

/**
 * Intelligent alternative to rename()
 * 
 * @param string $from
 * @param string $to
 * @return bool
 */
function move($from, $to)
{
    mkdir(dirname($to), 0755, true);
    return @rename($from, $to) or copy($from, $to) and unlink($from);
}

if (! function_exists('hex2bin')) {
    function hex2bin($data) {
        return pack('H*', $data);
    }
}

/* Initialise */
ini_set('allow_url_fopen', true);
stream_wrapper_register(ObjectStream::SCHEME, '\GitQuery\ObjectStream');
stream_wrapper_register(PackfileStream::SCHEME, '\GitQuery\PackfileStream');
