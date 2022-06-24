<?php

    function redirect($location) {
        header("Location: ".$location.".php");
    }

    function is_image($path) {
        $a = getimagesize($path);
        $image_type = $a[2];
        
        if (in_array($image_type, array('jpg', 'jpeg', 'png', 'gif','bmp'))) {
            return true;
        }
        return false;
    }

    if (!function_exists('str_contains')) {
        function str_contains(string $haystack, string $needle): bool {
            return '' === $needle || false !== strpos($haystack, $needle);
        }
    }

?>