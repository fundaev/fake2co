#! /usr/bin/env php
<?php

$args = getopt('f:');
$fileName = $args['f'];

$lines = file($fileName);


echo "    \$data = array(\n";

foreach ($lines as $line) {
    $line = trim($line);
    echo "        '$line' => '',\n";
}

echo "    );\n";
