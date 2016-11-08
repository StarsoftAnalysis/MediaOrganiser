<?php

// Create a new secondary filename, given the old
// and new names.
// e.g. changing main filename from foo.jpg to bar.png
// when the old secondary name is foo-123x456.jpg,
// the new secondary name will be bar-123x456.png
function new_secondary_name ($new, $oldsec) {
    $newparts = pathinfo($new);
    print_r($newparts);
    $newsec = $oldsec;
    if (preg_match('/-\d+x\d+\./', $oldsec, $matches)) {
        $nnnxnnn = $matches[0];
        echo 'nnnxnnn: ', $nnnxnnn, "\n";
        $newsec = $newparts['filename'] . $nnnxnnn . $newparts['extension'];
    }
    return $newsec;
}


echo "new=thing.jpg, oldsec=old-123x45667.png\n";
echo 'newsec=', new_secondary_name('thing.jpg', 'old-123x45667.png');
echo "\n"
?>
