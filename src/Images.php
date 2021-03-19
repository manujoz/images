<?php namespace Manujoz\Images;

class Images {
	private $width;
	private $height;
	private $cover;

	/**
	 * Copia una imagen al servidor y la redimensiona automáticamente si es pertinente.
	 * 
	 * Si no se pasan dimensiones, la imagen no será redimensionada y será copiada del mismo tamaño que
	 * la imagen original. Si solo se pasa el ancho o el alto, la imagen será escalada en proporción al 
	 * ancho o alto dados.
	 * 
	 * Si se psaa el ancho y el alto, se entiende que la imagen debe escalarse a esas medidas concretas, por
	 * defecto, se recortará la imagen para que sea devuelta en las dimensiones dadas, cubriendo completamente
	 * el ancho y alto dados. Si por el contrario, no queremos que la imagen sea recortada, si no que sea 
	 * enmarcada en las dimensiones dadas, usar el atributo --$cover=false--.
	 * 
	 * Por defecto las imágenes solo son redimensionadas a tamaños más pequeños para evitar una pérdida 
	 * excesiva de calidad. Si el ancho o el alto dados, son superiores a los tamaños originales de la imagen
	 * la imagen no quedará más grande que su tamaño original, aunque sí adoptará las nuevas proporciones. Si 
	 * queremos que la imagen se redimensiones a tamaños superiores forzosamente, usar el parámetro --$bigResize=true--
	 * lo que forzará el escalado a tamños mayores que la imagen original.
	 *
	 * @param   object  	$file         	Objeto con el archivo de imagen que queremos copiar
	 * @param   string  	$path         	Ruta absoluta de destino de la imagen (/img/mi-carpeta|img/mi-carp)
	 * @param   string  	$name       	Nombre que queremos que tenga la imagen, si no se pasa se asigna uno automático
	 * @param   integer  	$width    		Ancho que queremos que tenga la imagen, si no se pasa un alto, esta se redimensaionará en proporción
	 * @param   integer  	$height     	Alto que queremos que tenga la imagen, si no se pasa un ancho esta se redimensionará en proporción
	 * @param 	boolean 	$cover			Recorta la imagen redimensionada para que cubra completamente al ancho y alto dado
	 * @param 	boolean 	$bigResize		Fuerza el redimensionado de la imagen a mayor tamaño, por defecto solo redimensaiona a menor tamaño.
	 * @param   string  	$quality      	Calidad que queremos para la imgen (max|good|medium|low)
	 * @return array("copy":boolean,"dest":string,"name":string,"ext":string,"path":string)
	 */
	public function copy( $file, $path, $name = null, $width = null, $height = null, $cover = true, $bigResize = false, $quality = "good" ) {
		// Set config params
		$this->width = $width;
		$this->height = $height;
		$this->cover = $cover;

		// Get image data
		$image = $file[ 'tmp_name' ];
		$imgType = $file[ 'type' ]; 

		// Adjust the path
		if( $path[ 0 ] != "/" ) {
			$path = "/" . $path;
		}

		if( $path[ strlen( $path ) - 1 ] == "/") {
			$path = substr( $path, 0, -1);
		}
		$destination = $_SERVER[ "DOCUMENT_ROOT" ] . $path;

		// Create dirs
		$this->_create_dirs( $path );

		// Get extension
		if( $imgType == "image/jpeg" ) {
			$ext = "jpg";
		} else if ( $imgType == "image/png" ) {
			$ext = "png";
		}

		// If there is no name we assign a random name that is not repeated
		if( !$name ) {
			$name = mt_rand( 0, mt_getrandmax());
			$destination = $destination . "/" . $name . "." . $ext;
			while( file_exists( $destination )) {
				$name = mt_rand( 0, mt_getrandmax());
				$destination = $destination . "/" . $name . "." . $ext;
			}
		} else {
			$destination = $destination . "/" . $name . "." . $ext;
		}

		// We copy the image according to the type
		if( $imgType == "image/jpeg" ) {
			$copy = $this->_copy_jpeg( $image, $destination, $bigResize, $this->_get_quality( $quality, "jpg" ) );
		} else if( $imgType == "image/png" ) {
			$copy = $this->_copy_png( $image, $destination, $bigResize, $this->_get_quality( $quality, "png" ) );
		}

		// We organize the response
		$return[ "copy" ] = $copy;
		$return[ "dest" ] = $destination;
		$return[ "name" ] = $name;
		$return[ "ext" ] = $ext;
		$return[ "path" ] = $path . "/" . $name . "." . $ext;

		return $return;
	}

	/**
	 * Copia una imagen jpg redimensionándola a un ancho y alto dado
	 *
	 * @param   object  	$image   		Objeto con la imagen que queremos copiar
	 * @param   string  	$destination  		Dirección de destino donde queremos copiarla
	 * @param 	boolean 	$bigResize		Fuerza el redimensionado de la imagen a mayor tamaño, por defecto solo redimensaiona a menor tamaño.
	 * @param   string  	$quality      	(Opcional) Calidad que queremos para la imgen (max|good|medium|low)
	 */
	private function _copy_jpeg( $image = null, $destination = null, $bigResize = false, $quality = 80 ) {
		// Dimensiones de la imagen

		$heightancho = GetImageSize( $image );
		$originalSize[ "ancho" ] = $heightancho[ 0 ];
		$originalSize[ "alto" ] = $heightancho[ 1 ];

		// Si no hay dimensiones no redimensionamos
		
		$resize = $this->_resize( $originalSize, $bigResize );

		if( !$this->width ) {
			$this->width = $resize[ "ancho" ];
		}

		if( !$this->height ) {
			$this->height = $resize[ "alto" ];
		}

		// Creamos el recurso

		$resource = imagecreatefromjpeg( $image );

		// Creamos el thumb de la imagen
		
		$thumb = imagecreatetruecolor( $this->width, $this->height );
		$colorFondo = imagecolorallocate( $thumb, 255, 255, 255 );
		imagefilledrectangle( $thumb, 0, 0, $this->width, $this->height, $colorFondo );

		// Copiamos la imagen al thumb y al servidor
		
		imagecopyresampled( $thumb, $resource, $resize[ "coX" ], $resize[ "coY" ], 0, 0, $resize[ "ancho" ], $resize[ "alto" ], $originalSize[ "ancho" ], $originalSize[ "alto" ] );
		$copy = imagejpeg( $thumb, $destination, $quality );

		// Destruimos el thumb y devolvemos

		imagedestroy( $thumb );

		return $copy;
	}

	/**
	 * Copia una imagen png redimensionándola a un ancho y alto dado
	 *
	 * @param   object  	$image   		Objeto con la imagen que queremos copiar
	 * @param   string  	$destination  	Dirección de destino donde queremos copiarla
	 * @param 	boolean 	$bigResize		Fuerza el redimensionado de la imagen a mayor tamaño, por defecto solo redimensaiona a menor tamaño.
	 * @param   string  	$quality      	(Opcional) Calidad que queremos para la imgen (max|good|medium|low)
	 */
	private function _copy_png( $image = null, $destination = null, $bigResize = false, $quality = 9 ) {
		// Dimensiones de la imagen

		$heightancho = GetImageSize( $image );
		$originalSize[ "ancho" ] = $heightancho[ 0 ];
		$originalSize[ "alto" ] = $heightancho[ 1 ];

		// Si no hay dimensiones no redimensionamos

		$resize = $this->_resize( $originalSize, $bigResize );
		
		if( !$this->width ) {
			$this->width = $resize[ "ancho" ];
		}

		if( !$this->height ) {
			$this->height = $resize[ "alto" ];
		}

		// Creamos el recurso
		
		$resource = imagecreatefrompng( $image );

		// Creamos el thumb de la imagen

		$thumb = imagecreatetruecolor( $this->width, $this->height );

		// Preservamos la transparencia

		imagealphablending( $thumb, false );
		imagesavealpha( $thumb,true );
		imagefilledrectangle( $thumb, 0, 0, $this->width, $this->height, imagecolorallocatealpha( $thumb, 255, 255, 255, 127 ));

		// Copiamos la imagen

		imagecopyresampled( $thumb, $resource, $resize[ "coX" ], $resize[ "coY" ], 0, 0, $resize[ "ancho" ], $resize[ "alto" ], $originalSize[ "ancho" ], $originalSize[ "alto" ] );
		$copy = imagepng( $thumb, $destination, $quality );

		// Destruimos el thumb y devolvemos

		imagedestroy( $thumb );

		return $copy;
	}

	/**
	 * Crea los directorios que no existen en la ruta dada
	 *
	 * @param   string  	$path      		Ruta en la que se va a guardar la imagen
	 */
	private function _create_dirs( $path ) {
		// Remove the first slash to the route
        if ($path[0] == "/") {
            $path = substr($path, 1);
        }

        // Creamos el documentRoot
        $documentRoot = substr($_SERVER["DOCUMENT_ROOT"], -1) !== "/" ? $_SERVER["DOCUMENT_ROOT"] . "/" : $_SERVER["DOCUMENT_ROOT"];

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
	 * Redimenensiona la imagen a unas dimensiones dadas.
	 *
	 * @param   array  		$originalSize  	Array con los tamaños originales de la imagen
	 * @param   boolean  	$bigResize     	Determina si debe forzarse el reescaado
	 * @return 	array						Array con las dimensiones y coordenadas ajustadas.
	 */
	private function _resize( $originalSize, $bigResize = false ) {
		if( $this->height && $this->width ) {
			$propsOr = $this->_get_proportions( $originalSize[ "ancho" ], $originalSize[ "alto" ] );
			$propsRs = $this->_get_proportions( $this->width, $this->height );

			// Si la tiene las mismas proporciones de lo contrario centramos

			if( $propsOr == $propsRs ) {
				return $this->_proportional_resize( $originalSize, $bigResize );
			} else {
				return $this->_centered_resize( $originalSize, $bigResize );
			}
		} else {
			return $this->_proportional_resize( $originalSize, $bigResize );
		}
	}

	/**
	 * Devuelve las proporciones de una alto con respecto a un ancho dados
	 *
	 * @param   integer  	$width  	Ancho de referencia para la proporción
	 * @param   integer  	$height   	Alto de referencia para la proporción
	 */
	private function _get_proportions( $width, $height ) {
		return ( $height * 100 ) / $width;
	}

	/**
	 * Devuelve las dimensiones de una imagen proporcionalmente en la relación de tamaño original.
	 *
	 * @param   array  		$originalSize  	Array con los tamaños originales de la imagen
	 * @param   boolean  	$bigResize     	Determina si debe forzarse el reescaado
	 * @return 	array						Array con las dimensiones y coordenadas ajustadas.
	 */
	private function _proportional_resize( $originalSize, $bigResize ) {
		if( $this->width && ( $this->width < $originalSize[ "ancho" ] || $bigResize )) {
			$resize[ "ancho" ] = $this->width;
			$resize[ "alto" ] = ( $this->width * $originalSize[ "alto" ] ) / $originalSize[ "ancho" ];
		} else if( $this->height && ( $this->height < $originalSize[ "alto" ] || $bigResize )) {
			$resize[ "ancho" ] = ( $this->height * $originalSize[ "ancho" ] ) / $originalSize[ "alto" ];
			$resize[ "alto" ] = $this->height;
		} else {
			$resize[ "ancho" ] = $originalSize[ "ancho" ];
			$resize[ "alto" ] = $originalSize[ "alto" ];
		}

		if( !$bigResize ) {
			$this->width = $resize[ "ancho" ];
			$this->height = $resize[ "alto" ];
		}

		$resize[ "coX" ] = 0;
		$resize[ "coY" ] = 0;
		
		return $resize;
	}

	/**
	 * Devuelve la redimensión de una imagen que se está escalando a unas proporciones diferentes
	 *
	 * @param   array  		$originalSize  	Array con los tamaños originales de la imagen
	 * @param   boolean  	$bigResize     	Determina si debe forzarse el reescaado
	 * @return 	array						Array con las dimensiones y coordenadas ajustadas.
	 */
	private function _centered_resize( $originalSize, $bigResize ) {
		$changeProportions = false;

		// Si la imagen original tiene un ancho mayor al alto

		if( $originalSize[ "ancho" ] > $originalSize[ "alto" ] ) {
			$resize[ "ancho" ] = $this->width;
			$resize[ "alto" ] = ( $originalSize[ "alto" ] * $this->width ) / $originalSize[ "ancho" ];

			// Si el alto redimensionado es más pequeño al alto dado, se reajusta

			if( $this->cover && $resize[ "alto" ] < $this->height ) {
				$resize[ "ancho" ] = ( $originalSize[ "ancho" ] * $this->height ) / $originalSize[ "alto" ];
				$resize[ "alto" ] = $this->height;
				$changeProportions = true;
			}
		}

		// Si la imagen oroginal tiene un alto mayor al ancho

		if( $originalSize[ "alto" ] > $originalSize[ "ancho" ] ) {
			$resize[ "ancho" ] = ( $originalSize[ "ancho" ] * $this->height ) / $originalSize[ "alto" ];
			$resize[ "alto" ] = $this->height;
			
			// Si el ancho redimensionado es más pequeño al ancho dado, se reajusta

			if( $this->cover && $resize[ "ancho" ] < $this->width ) {
				$resize[ "ancho" ] = $this->width;
				$resize[ "alto" ] = ( $originalSize[ "alto" ] * $this->width ) / $originalSize[ "ancho" ];
				$changeProportions = true;
			}
		}

		// Si la imagen original es cuadrada

		if( $originalSize[ "ancho" ] == $originalSize[ "alto" ] ) {
			if( $this->width > $this->height ) {
				$resize[ "ancho" ] = $this->width;
				$resize[ "alto" ] = $this->width;
			} else {
				$resize[ "ancho" ] = $this->height;
				$resize[ "alto" ] = $this->height;
			}
		}

		// Si la imagen ha sido redimensionada a mayor escala y no hay $bigResize

		if( $resize[ "ancho" ] > $originalSize[ "ancho" ] && $resize[ "alto" ] > $originalSize[ "alto" ] && !$bigResize && !$changeProportions ) {
			if( $this->width > $this->height ) {
				$this->height = ( $originalSize[ "ancho" ] * $this->height ) / $this->width;
				$this->width = $originalSize[ "ancho" ];
			} else if( $this->height > $this->width ) {
				$this->width = ( $originalSize[ "alto" ] * $this->width ) / $this->height;
				$this->height = $originalSize[ "alto" ];
			} else {
				if( $originalSize[ "ancho" ] < $originalSize[ "alto" ] ) {
					$this->width = $originalSize[ "ancho" ];
					$this->height = $this->width;
				} else {
					$this->height = $originalSize[ "alto" ];
					$this->width = $this->height;
				}
			}

			return $this->_centered_resize( $originalSize, true );
		}

		// Asinamos coordenadas

		$resize[ "coX" ] = 0;
		$resize[ "coY" ] = 0;
		if( $resize[ "ancho" ] > $this->width && $resize[ "alto" ] == $this->height ) {
			$resize[ "coX" ] = ( $this->width - $resize[ "ancho" ] ) / 2;
		}
		if( $resize[ "ancho" ] < $this->width && $resize[ "alto" ] == $this->height ) {
			$resize[ "coX" ] = ( $this->width - $resize[ "ancho" ] ) / 2;
		}
		if( $resize[ "alto" ] > $this->height && $resize[ "ancho" ] == $this->width ) {
			$resize[ "coY" ] = ( $this->height - $resize[ "alto" ] ) / 2;
		}
		if( $resize[ "alto" ] < $this->height && $resize[ "ancho" ] == $this->width ) {
			$resize[ "coY" ] = ( $this->height - $resize[ "alto" ] ) / 2;
		}

		return $resize;
	}

	/**
	 * Devualve la calidad en número computable a raiz de una calidad dadoa en un string (max|good|medium|low)
	 *
	 * @param   string  	$quality  		Calidad que queremos computar en string
	 * @param   string  	$type     		Tipo de imagen (jpg|png)
	 */
	private function _get_quality( $quality = null, $type = null ) {
		if( $quality == "max" ) {
			return ( $type == "jpg" ) ? 100 : 9;
		}
		if( $quality == "good" ) {
			return ( $type == "jpg" ) ? 80 : 8;
		}
		if( $quality == "medium" ) {
			return ( $type == "jpg" ) ? 60 : 7;
		}
		if( $quality == "low" ) {
			return ( $type == "jpg" ) ? 40 : 5;
		}
	}
}

?>