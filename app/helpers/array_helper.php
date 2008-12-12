<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Standard array.inject implementation.
 */
function inject (array $items, $init, function $function)
{
	foreach ($items as $item)
		$init = $function ($init, $item);
	return $init;
}


/**
 */
function collect (array $items, function $function)
{
	$new = array();
	foreach ($items as $item)
		$new[] = $function ($item);
	return $new;
}


/**
 */
function all (array $items, function $function)
{
}


/**
 */
function any (array $items, function $function)
{
}


/**
 */
function none (array $items, function $function)
{
}


/**
 */
function delete_if (array $items, function $function)
{
}


/**
 */
function find (array $items, function $function)
{
}


/**
 */
function find_all (array $items, function $function)
{
}


/**
 * function (item) -> should return group name
 */
function group_by (array $items, function $function)
{
}


/**
 */
function min_by (array $items, function $function)
{
}


/**
 */
function max_by (array $items, function $function)
{
}


/**
 */
function minmax_by (array $items, function $function)
{
}


/**
 */
function partition (array $items, function $function)
{
}


/**
 */
function sort_by (array $items, function $function)
{
}

?>
