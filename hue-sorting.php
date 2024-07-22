<?php

// function heavily based on https://stackoverflow.com/a/13887939
// shout-out to https://stackoverflow.com/users/629493/unsigned
// most of my changes are just handling edge cases better,
// such as returning null instead of zero for grayscale.

// Licensed under the terms of the BSD License.
// (Basically, this means you can do whatever you like with it,
//   but if you just copy and paste my code into your app, you
//   should give me a shout-out/credit :)
function RGBtoHSV($R, $G, $B)    // RGB values:    0-255, 0-255, 0-255
{                                // HSV values:    0-360, 0-100, 0-100
    // Convert the RGB byte-values to percentages
    $R = ($R / 255.0);
    $G = ($G / 255.0);
    $B = ($B / 255.0);

    // Calculate a few basic values, the maximum value of R,G,B, the
    //   minimum value, and the difference of the two (chroma).
    $maxRGB = max($R, $G, $B);
    $minRGB = min($R, $G, $B);
    $chroma = $maxRGB - $minRGB;

    // Value (also called Brightness) is the easiest component to calculate,
    //   and is simply the highest value among the R,G,B components.
    // We multiply by 100 to turn the decimal into a readable percent value.
    $computedV = 100.0 * $maxRGB;

    // Special case if hueless (equal parts RGB make black, white, or grays)
    // Note that Hue is technically undefined when chroma is zero, as
    //   attempting to calculate it would cause division by zero (see
    //   below), so most applications simply substitute a Hue of zero.
    // Saturation will always be zero in this case, see below for details.
    /* allow a tiny deviation of hue to be regarded as grayscale ~ABS */
    if ($chroma <= 0.02) // grayscale +/- 1%
        return array('null', 0, $computedV);

    // Saturation is also simple to compute, and is simply the chroma
    //   over the Value (or Brightness)
    // Again, multiplied by 100 to get a percentage.
    $computedS = 100.0 * ($chroma / $maxRGB);

    // Calculate Hue component
    // Hue is calculated on the "chromacity plane", which is represented
    //   as a 2D hexagon, divided into six 60-degree sectors. We calculate
    //   the bisecting angle as a value 0 <= x < 6, that represents which
    //   portion of which sector the line falls on.
    if ($R == $minRGB)
        $h = 3.0 - (($G - $B) / $chroma);
    elseif ($B == $minRGB)
        $h = 1.0 - (($R - $G) / $chroma);
    else // $G == $minRGB
        $h = 5.0 - (($B - $R) / $chroma);

    // After we have the sector position, we multiply it by the size of
    //   each sector's arc (60 degrees) to obtain the angle in degrees.
    /* allow red with a tiny amount of green/blue to be regarded as red ~ABS */
    $computedH = 60.0 * $h;
    if ($computedH >= 359.28) // red +0.2% green/blue
        $computedH = 0;

    return array($computedH, $computedS, $computedV);
}

$file = file_get_contents(__DIR__ . '/list-by-name.html');
$lines = [];
foreach (explode("\n", $file) as $line) {
    if ( ! strpos($line, '#')) {
        continue;
    }
    $hex = explode('"', explode('#', $line)[1])[0];
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    [$hue, $sat, $val] = RGBtoHSV($r, $g, $b);
    if ($hue === 'null') {
        // grayscale at the beginning
        $key = sprintf("***-%03.0f-%s", 200.0-$val, $hex);
    } else {
        // $key = sprintf("%07.03f-%07.03f-%07.03f-%s", $hue, $sat, $val, $hex); // initial sorting, by exact hue, fallback to darkest to brightest
        $key = sprintf("%03.0f-%03.0f-%s", $hue/7.2, 300.0-$val-$sat, $hex); // group by hue +/- 2%, order groups by brightest to darkest
    }
    $lines[$key] = $line;
}
ksort($lines);
foreach ($lines as $key => $line) {
    echo "$line\n";
}








