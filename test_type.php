<?php
try {
    array_diff_key([1 => 1], "hello");
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
try {
    $a = "hello" + ["world"];
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
