<?php
$source = file_get_contents('C:/Users/calvi/Downloads/screencapture-fiatluxacademe-registrar-printenrollmentreport-php-2026-09-21-14_38_45.pdf');
$offset = 0;
$index = 0;
while (($position = strpos($source, '/Subtype /Image', $offset)) !== false) {
    $dictionaryEnd = strpos($source, '>>', $position);
    $dictionary = substr($source, $position, $dictionaryEnd - $position);
    preg_match('/\/Length\s+(\d+)/', $dictionary, $match);
    $stream = strpos($source, 'stream', $dictionaryEnd) + 6;
    if (substr($source, $stream, 2) === "\r\n") {
        $stream += 2;
    } elseif (substr($source, $stream, 1) === "\n") {
        $stream++;
    }
    $data = substr($source, $stream, (int) $match[1]);
    $index++;
    $path = __DIR__."/page-{$index}.jpg";
    file_put_contents($path, $data);
    echo $path.' '.strlen($data).PHP_EOL;
    $offset = $stream + strlen($data);
}
