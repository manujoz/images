<?php

namespace Manujoz\Images;

class ImagesCopyConfig
{
    // If true image can be resize to longer dimensions, else never will exced ther original size
    public bool $bigResize = false;
    // If true the image will cover all space
    public bool $cover = true;
    // Document root path on the server
    public ?string $documentRoot = null;
    // Image height
    public ?int $heigth = null;
    // Image name
    public ?string $name = null;
    // Path where image will be saved
    public string $path;
    // Image quality ("max"|"good"|"medium"|"low")
    public string $quality = "good";
    // Image width
    public ?int $width = null;
}
