# Imágenes

Images is a class to upload images to the server in a simple and completely configurable way

## Install

```
$ composer require manujoz/images
```

## Use



<span style="font-size:12px">index.php</span>

```php
<?php

use Manujoz\Images\Images;

require( "vendor/autoload.php" );

$IMGS = new Images();
$response = $IMGS->copy( $_FILES[ "img" ], "/img/path/folder" );

echo $response[ "copy" ] . "<br>";
echo $response[ "dest" ] . "<br>";
echo $response[ "name" ] . "<br>";
echo $response[ "ext" ] . "<br>";
echo $response[ "path" ];

?>

```

## Documentation

### copy() method

With the **_of()_** method we perform the translations, this method admits two parameters:

```php

$IMGS->copy( $file, $path, $name = null, $width = null, $height = null, $cover = true, $bigResize = false, $quality = "good" );

```

$file:

File object to upload to server

$path:

Absolute path to save the image

$name:

Name of image file on destination

$width: 

Destination width of image

$heigh:

Destiantion height of image

$cover:

Crop the resized image so that it fully covers the given width and height

$bigResize:

Forces the image to be resized to a larger size, by default it only resizes to a smaller size.

$quality:

Quality we want for the image (max | good | medium | low)


### $response

Array with saved image data

$response[ "copy" ]: Boolean

Indicates whether or not the image has been copied

$response[ "dest" ]: String.

Destination path of image

$response[ "ext" ]: String.

Destination image extension

$response[ "name" ]: String.

Destination image name

$response[ "path" ]: String.

Destination absolute path of image


