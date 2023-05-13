<?php

namespace Manujoz\Images;

use Exception;

class Image
{

	public ImagesCopyConfig $config;

	private bool $cover;
	private string $destination;
	private string $extension;
	private int $height;
	private array $image;
	private int $width;

	private string $ftpPath;

	function __construct()
	{
		$this->config = new ImagesCopyConfig();
	}

	/**
	 * Upload image to server
	 */
	function upload(array $file): ImageResponse
	{
		// Create response
		$Response = new ImageResponse();

		// Set default document root
		if ($this->config->documentRoot === null) {
			$this->config->documentRoot = $_SERVER["DOCUMENT_ROOT"];
		}

		// Set image config params
		$this->image = $file;
		$this->width = $this->config->width;
		$this->height = $this->config->heigth;
		$this->cover = $this->config->cover;

		// Adjust the path
		$this->adjustPath();

		// Create dirs
		$this->createDirs();

		// Get image extension
		$this->setImageExtension();

		// Set destination
		$this->setDestination();

		// Copy image
		if (!$this->copy()) {
			$Response->copy = false;
			return $Response;
		}

		// Set response
		$Response->copy = true;
		$Response->extension = $this->extension;
		$Response->name = $this->config->name;
		$Response->path = $this->destination;
		$Response->url = $this->config->path . "/" . $this->config->name . "." . $this->extension;

		return $Response;
	}

	/**
	 * Adjust path
	 */
	private function adjustPath(): void
	{
		// Adjust the path
		if ($this->config->path[0] !== "/") {
			$this->config->path = "/" . $this->config->path;
		}

		if ($this->config->path[strlen($this->config->path) - 1] == "/") {
			$this->config->path = substr($this->config->path, 0, -1);
		}
	}

	/**
	 * Create necesary directories
	 */
	private function createDirs()
	{
		$path = $this->config->path;

		// Remove the first slash to the route
		if ($path[0] == "/") {
			$path = substr($path, 1);
		}

		// Creamos el documentRoot
		$documentRoot = substr($this->config->documentRoot, -1) !== "/" ? $this->config->documentRoot . "/" : $this->config->documentRoot;

		// We separate the route by slash and we travel it
		$sRuta = explode("/", $path);
		$ruRide = "";
		foreach ($sRuta as $ru) {
			$ruRide .= !$ruRide ? $documentRoot . $ru : "/" . $ru;

			if (!file_exists($ruRide)) {
				mkdir($ruRide, 0777, true);
			}
		}
	}

	/**
	 * Copy image
	 */
	private function copy(): bool
	{
		if ($this->extension === "jpg") {
			return $this->copyJpeg();
		} else if ($this->extension === "png") {
			return $this->copyPng();
		}
	}

	/**
	 * Copy jpg image
	 */
	private function copyJpeg(): bool
	{
		$imageTempName = $this->image["tmp_name"];

		// Get origina sizes
		$heightancho = GetImageSize($imageTempName);
		$originalSize["width"] = $heightancho[0];
		$originalSize["height"] = $heightancho[1];

		// Get quality
		$quality = $this->getQuality();

		// If there not dimensions, resize
		$resize = $this->resize($originalSize, $this->config->bigResize);

		if (!$this->width) {
			$this->width = $resize["width"];
		}

		if (!$this->height) {
			$this->height = $resize["height"];
		}

		// Create resource
		$resource = imagecreatefromjpeg($imageTempName);

		// Create image thumb
		$thumb = imagecreatetruecolor($this->width, $this->height);
		$colorFondo = imagecolorallocate($thumb, 255, 255, 255);
		imagefilledrectangle($thumb, 0, 0, $this->width, $this->height, $colorFondo);

		// Copy image to the thumb and destination
		imagecopyresampled($thumb, $resource, $resize["coX"], $resize["coY"], 0, 0, $resize["width"], $resize["height"], $originalSize["width"], $originalSize["height"]);
		$copy = imagejpeg($thumb, $this->destination, $quality);

		// Destroy thumb
		imagedestroy($thumb);

		return $copy;
	}

	/**
	 * Copy jpg image
	 */
	private function copyPng(): bool
	{
		$imageTempName = $this->image["tmp_name"];

		// Image dimensions
		$heightancho = GetImageSize($imageTempName);
		$originalSize["width"] = $heightancho[0];
		$originalSize["height"] = $heightancho[1];

		// Get quality
		$quality = $this->getQuality();

		// If there not dimensions, resize
		$resize = $this->resize($originalSize);

		if (!$this->width) {
			$this->width = $resize["width"];
		}

		if (!$this->height) {
			$this->height = $resize["height"];
		}

		// Create resource
		$resource = imagecreatefrompng($imageTempName);

		// Creamos el thumb de la imagen

		$thumb = imagecreatetruecolor($this->width, $this->height);

		// Preservamos la transparencia

		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		imagefilledrectangle($thumb, 0, 0, $this->width, $this->height, imagecolorallocatealpha($thumb, 255, 255, 255, 127));

		// Copiamos la imagen

		imagecopyresampled($thumb, $resource, $resize["coX"], $resize["coY"], 0, 0, $resize["width"], $resize["height"], $originalSize["width"], $originalSize["height"]);
		$copy = imagepng($thumb, $this->destination, $quality);

		// Destruimos el thumb y devolvemos

		imagedestroy($thumb);

		return $copy;
	}

	/**
	 * Return images dimeension that is sclaed to different proportions
	 */
	private function getCenteredResize(array $originalSize): array
	{
		$changeProportions = false;

		// Si la imagen original tiene un ancho mayor al alto

		if ($originalSize["width"] > $originalSize["height"]) {
			$resize["width"] = $this->width;
			$resize["height"] = ($originalSize["height"] * $this->width) / $originalSize["width"];

			// Si el alto redimensionado es más pequeño al alto dado, se reajusta

			if ($this->cover && $resize["height"] < $this->height) {
				$resize["width"] = ($originalSize["width"] * $this->height) / $originalSize["height"];
				$resize["height"] = $this->height;
				$changeProportions = true;
			}
		}

		// Si la imagen oroginal tiene un alto mayor al ancho

		if ($originalSize["height"] > $originalSize["width"]) {
			$resize["width"] = ($originalSize["width"] * $this->height) / $originalSize["height"];
			$resize["height"] = $this->height;

			// Si el ancho redimensionado es más pequeño al ancho dado, se reajusta

			if ($this->cover && $resize["width"] < $this->width) {
				$resize["width"] = $this->width;
				$resize["height"] = ($originalSize["height"] * $this->width) / $originalSize["width"];
				$changeProportions = true;
			}
		}

		// Si la imagen original es cuadrada

		if ($originalSize["width"] == $originalSize["height"]) {
			if ($this->width > $this->height) {
				$resize["width"] = $this->width;
				$resize["height"] = $this->width;
			} else {
				$resize["width"] = $this->height;
				$resize["height"] = $this->height;
			}
		}

		// Si la imagen ha sido redimensionada a mayor escala y no hay $bigResize

		if ($resize["width"] > $originalSize["width"] && $resize["height"] > $originalSize["height"] && !$this->config->bigResize && !$changeProportions) {
			if ($this->width > $this->height) {
				$this->height = ($originalSize["width"] * $this->height) / $this->width;
				$this->width = $originalSize["width"];
			} else if ($this->height > $this->width) {
				$this->width = ($originalSize["height"] * $this->width) / $this->height;
				$this->height = $originalSize["height"];
			} else {
				if ($originalSize["width"] < $originalSize["height"]) {
					$this->width = $originalSize["width"];
					$this->height = $this->width;
				} else {
					$this->height = $originalSize["height"];
					$this->width = $this->height;
				}
			}

			return $this->getCenteredResize($originalSize, true);
		}

		// Asinamos coordenadas

		$resize["coX"] = 0;
		$resize["coY"] = 0;
		if ($resize["width"] > $this->width && $resize["height"] == $this->height) {
			$resize["coX"] = ($this->width - $resize["width"]) / 2;
		}
		if ($resize["width"] < $this->width && $resize["height"] == $this->height) {
			$resize["coX"] = ($this->width - $resize["width"]) / 2;
		}
		if ($resize["height"] > $this->height && $resize["width"] == $this->width) {
			$resize["coY"] = ($this->height - $resize["height"]) / 2;
		}
		if ($resize["height"] < $this->height && $resize["width"] == $this->width) {
			$resize["coY"] = ($this->height - $resize["height"]) / 2;
		}

		return $resize;
	}

	/**
	 * Return integer image quality throght given quality in config
	 */
	private function getQuality()
	{
		if ($this->config->quality == "max") {
			return ($this->extension == "jpg") ? 100 : 9;
		}
		if ($this->config->quality == "good") {
			return ($this->extension == "jpg") ? 80 : 8;
		}
		if ($this->config->quality == "medium") {
			return ($this->extension == "jpg") ? 60 : 7;
		}
		if ($this->config->quality == "low") {
			return ($this->extension == "jpg") ? 40 : 5;
		}
	}

	/**
	 * Get image proportions
	 */
	private function getProportions(int $width, int $height): int
	{
		return ($height * 100) / $width;
	}

	/**
	 * Return proportional resize
	 */
	private function getProportionalResize(array $originalSize): array
	{
		if ($this->width && ($this->width < $originalSize["width"] || $this->config->bigResize)) {
			$resize["width"] = $this->width;
			$resize["height"] = ($this->width * $originalSize["height"]) / $originalSize["width"];
		} else if ($this->height && ($this->height < $originalSize["height"] || $this->config->bigResize)) {
			$resize["width"] = ($this->height * $originalSize["width"]) / $originalSize["height"];
			$resize["height"] = $this->height;
		} else {
			$resize["width"] = $originalSize["width"];
			$resize["height"] = $originalSize["height"];
		}

		if (!$this->config->bigResize) {
			$this->width = $resize["width"];
			$this->height = $resize["height"];
		}

		$resize["coX"] = 0;
		$resize["coY"] = 0;

		return $resize;
	}

	/**
	 * Resize image
	 */
	private function resize(array $originalSize): array
	{
		if ($this->height && $this->width) {
			$propsOr = $this->getProportions($originalSize["width"], $originalSize["height"]);
			$propsRs = $this->getProportions($this->width, $this->height);

			// If same proportions
			if ($propsOr == $propsRs) {
				return $this->getProportionalResize($originalSize);
			} else {
				return $this->getCenteredResize($originalSize);
			}
		} else {
			return $this->getProportionalResize($originalSize);
		}
	}

	/**
	 * Set image destination
	 */
	private function setDestination(): void
	{
		$this->destination = $this->config->documentRoot . $this->config->path;

		// If there is no name we assign a random name that is not repeated
		if (!$this->config->name) {
			$this->config->name = mt_rand(0, mt_getrandmax());
			$this->destination = $this->destination . "/" . $this->config->name . "." . $this->extension;
			while (file_exists($this->destination)) {
				$this->config->name = mt_rand(0, mt_getrandmax());
				$this->destination = $this->destination . "/" . $this->config->name . "." . $this->extension;
			}
		} else {
			$this->destination = $this->destination . "/" . $this->config->name . "." . $this->extension;
		}
	}

	/**
	 * Get image extension
	 */
	private function setImageExtension()
	{
		$imgType = $this->image['type'];
		// Get extension
		if ($imgType == "image/jpeg") {
			$this->extension = "jpg";
		} else if ($imgType == "image/png") {
			$this->extension = "png";
		} else {
			throw new Exception("Image only can be JPG or PNG");
		}
	}
}
