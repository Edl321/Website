<?php

if (!function_exists("edlImagePath")) {

    function edlImagePath($image)
    {
        if (empty($image)) {
            return "";
        }

        $image = trim((string)$image);

        if (
            str_starts_with($image, "http://") ||
            str_starts_with($image, "https://")
        ) {
            return $image;
        }

        if (
            str_starts_with($image, "Images/") ||
            str_starts_with($image, "images/") ||
            str_starts_with($image, "uploads/") ||
            str_starts_with($image, "Uploads/")
        ) {
            return $image;
        }

        if (str_contains($image, "/")) {
            return "Images/" . $image;
        }

        return "Images/" . basename($image);
    }

}