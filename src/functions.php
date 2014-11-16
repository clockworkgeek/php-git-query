<?php
namespace GitQuery;

function is_stream($handle)
{
    return is_resource($handle) && (get_resource_type($handle) === 'stream');
}
