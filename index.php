<?php

define("ALIGN_LEFT", "left");
define("ALIGN_CENTER", "center");
define("ALIGN_RIGHT", "right");

function calculateTextBox($font_size, $font_angle, $font_file, $text) {
    $box = imagettfbbox($font_size, $font_angle, $font_file, $text);
    if (!$box) {
        return false;
    }
    $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
    $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
    $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
    $max_y = max(array($box[1], $box[3], $box[5], $box[7]));
    $width = $max_x - $min_x;
    $height = $max_y - $min_y;
    $left = abs($min_x) + $width;
    $top = abs($min_y) + $height;
    // to calculate the exact bounding box i write the text in a large image
    $img = @imagecreatetruecolor($width << 2, $height << 2);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $black);
    // for sure the text is completely in the image!
    imagettftext($img, $font_size, $font_angle, $left, $top, $white, $font_file, $text);
    // start scanning (0=> black => empty)
    $rleft = $w4 = $width << 2;
    $rright = 0;
    $rbottom = 0;
    $rtop = $h4 = $height << 2;
    for ($x = 0; $x < $w4; $x++)
        for ($y = 0; $y < $h4; $y++)
            if (imagecolorat($img, $x, $y)) {
                $rleft = min($rleft, $x);
                $rright = max($rright, $x);
                $rtop = min($rtop, $y);
                $rbottom = max($rbottom, $y);
            }
    // destroy img and serve the result
    imagedestroy($img);
    return array(
        "left" => $left - $rleft,
        "top" => $top - $rtop,
        "width" => $rright - $rleft + 1,
        "height" => $rbottom - $rtop + 1
    );
}

/**
 *
 * @param type $image - true color image object
 * @param type $size - font size
 * @param type $angle
 * @param type $left - left position
 * @param type $top - top position
 * @param type $color - font color
 * @param type $font - ttf file path
 * @param type $text
 * @param type $align - text align
 * @param type $width - image width
 * @param type $height - image height
 */
function myimagettftextbox(&$image, $size, $angle, $left, $top, $color, $font, $text, $align, $width, $height) {
    $text_lines = explode("\n", $text);
    /**
     * Standart function good works with left align or one line text
     */
    if ($align == ALIGN_LEFT || count($text_lines) <= 1) {
        imagettftext($image, $size, $angle, $left, $top, $color, $font, $text);
    } else {
        $lines = array();
        $line_widths = array();
        $line_heights = array();
        $line_ys = array();
        $index = 0;
        $sum_height = 0;
        /**
         * calculate properties for each line
         */
        foreach ($text_lines as $block) {
            $dimensions = calculateTextBox($size, $angle, $font, $block);
            $line_width = $dimensions['width'];
            $line_height = $dimensions['height'];
            $line_y = $dimensions['top'];
            $lines[$index] = $block;
            $line_widths[$index] = $line_width;
            $line_heights[$index] = $line_height;
            $line_ys[$index] = $line_y;
            $sum_height += $line_height;
            $index++;
        }
        $max_width = max($line_widths);
        $max_width = $max_width + floor(($width - $max_width) / 2);
        $index = 0;
        $delta_h = floor(($height - $sum_height) / (count($lines) - 1));
        $top_offset = 0;
        $left_offset = 0;
        foreach ($lines as $line) {
            if ($align == ALIGN_CENTER) {
                $left_offset = ($max_width - $line_widths[$index]) / 2;
            } elseif ($align == ALIGN_RIGHT) {
                $left_offset = ($max_width - $line_widths[$index]);
            }
            imagettftext($image, $size, $angle, $left_offset + $left, $line_ys[$index] + $top_offset, $color, $font, $line);
            $top_offset += (isset($line_heights[$index]) ? $line_heights[$index] : 0) + $delta_h;
            $index++;
        }
    }
}

/**
 * Read in request
 */
$inText = isset($_GET['text']) ? trim($_GET['text']) : "Hello\nbeautiful\nworld.";
$redColor = isset($_GET['red']) ? intval($_GET['red']) : 146;
$greenColor = isset($_GET['green']) ? intval($_GET['green']) : 39;
$blueColor = isset($_GET['blue']) ? intval($_GET['blue']) : 143;
$inFontFile = isset($_GET['font']) ? trim($_GET['font']) : "BillMoney.ttf";
$inFontSize = isset($_GET['fsize']) ? trim($_GET['fsize']) : 24;
$inAlign = isset($_GET['align']) ? trim($_GET['align']) : ALIGN_CENTER;
/**
 * Calculate image position
 */
$bbox = imagettfbbox($inFontSize, 0, $inFontFile, $inText);
$width = abs($bbox[0]) + abs($bbox[2]);
$height = abs($bbox[5]) + abs($bbox[1]);
$x = abs($bbox[0]);
$y = abs($bbox[5]);
/**
 * Create image
 */
header('Content-type: image/png');
$img = imagecreatetruecolor($width, $height);
$text_colour = imagecolorallocate($img, $redColor, $greenColor, $blueColor);
$background = ImageColorAllocateAlpha($img, ($redColor == 255 ? 254 : $redColor + 1), ($greenColor == 255 ? 254 : $greenColor + 1), ($blueColor == 255 ? 254 : $blueColor + 1), 127);
imagefill($img, 0, 0, $background);
imagecolortransparent($img, $background);
/**
 * Add text
 */
myimagettftextbox($img, $inFontSize, 0, $x, $y, $text_colour, $inFontFile, $inText, $inAlign, $width, $height);
imageAlphaBlending($img, false);
imageSaveAlpha($img, true);
imagepng($img);
/**
 * Destroys used resources
 */
imagecolordeallocate($img, $text_colour);
imagecolordeallocate($img, $background);
imagedestroy($img);
?>
