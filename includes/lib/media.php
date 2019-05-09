<?php

if (!defined('MICROLIGHT')) die();

class UploadException extends Exception {
	/**
	 * Thrown when a file upload encounters an error
	 * @param array $file
	 * @return UploadException
	 */
	function __construct ($file) {
		$code = $this->get_code($file);
		$message = $this->get_message($code);

		parent::__construct($message, $code);
	}

	/**
	 * Get the error code based on the provided file (or not)
	 * @param array $file
	 * @return integer
	 */
	private function get_code ($file) {
		if (isset($file['error'])) {
			return $file['error'];
		} else {
			// 'Unknown upload error'
			return -1;
		}
	}

	/**
	 * Get the error message based on the error code
	 * @param integer $error
	 * @return string
	 */
	private function get_message ($error) {
		switch ($error) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return 'The uploaded file is too large';

		case UPLOAD_ERR_PARTIAL:
			return 'The uploaded file was only partially uploaded';

		case UPLOAD_ERR_NO_FILE:
			return 'No file was uploaded';

		case UPLOAD_ERR_NO_TMP_DIR:
		case UPLOAD_ERR_CANT_WRITE:
		case UPLOAD_ERR_EXTENSION:
			return 'Server error';

		default:
			return 'Unknown upload error';
		}
	}
}

abstract class ImageType extends BasicEnum {
	const PNG = 'image/png';
	const JPG = 'image/jpg';
	const JPEG = 'image/jpeg';
	const GIF = 'image/gif'; // jif
}

class ImageResizer {
	private $image;
	private $type;
	private $width;
	private $height;
	private $width_src;
	private $height_src;
	private $filename;

	private $filename_override;
	private $mimetype_override;

	/**
	 * Load and resize an uploaded file
	 * @param array $file
	 * @param string $filename_override If set, the image will be forcibly saved here
	 * @param string $mimetype_override If set, the image will be forcibly saved with this type
	 * @return ImageResizer
	 */
	function __construct ($file, $filename_override = null, $mimetype_override = null) {
		if (empty($file['tmp_name'])) {
			throw new Exception('Filename was not provided');
		}

		if (!$this->get_type($file)) {
			throw new Exception('Invalid file type');
		}

		// Check file type (allow images only)
		if (!ImageType::isValidValue($this->type)) {
			throw new Exception('Image was not provided');
		}

		// Calculate new image size
		if (!$this->dimensions($file)) {
			throw new Exception('Image dimensions could not be determined');
		}

		// Load image into memory
		if (!$this->load($file)) {
			throw new Exception('Image could not be loaded');
		}

		// Resize image
		if (!$this->resize()) {
			imagedestroy($this->image);
			throw new Exception('Image could not be resized');
		}

		$this->filename_override = $filename_override;
		$this->mimetype_override = $mimetype_override;

		if ($this->mimetype_override !== null && !ImageType::isValidValue($this->mimetype_override)) {
			throw new Exception('Image type override is invalid');
		}

		// Set upload path
		$this->set_upload_path($file);

		// Save image to uploads directory
		if (!$this->save($file)) {
			throw new Exception('Image could not be saved to file');
		}
	}

	public function get_permalink () {
		return ml_base_url() . $this->filename;
	}

	// Source: https://www.php.net/manual/en/function.finfo-open.php#112617
	// This function reads the first 6 bytes of the uploaded file and determines
	// the file type based on its contents.
	private function get_type ($file) {
		$fh = fopen($file['tmp_name'],'rb');

		if ($fh) {
			$bytes6 = fread($fh,6);
			fclose($fh);

			if ($bytes6 === false) return false;

			if (substr($bytes6,0,3) == "\xff\xd8\xff") {
				$this->type = ImageType::JPG;
				return true;
			}
			if ($bytes6 == "\x89PNG\x0d\x0a") {
				$this->type = ImageType::PNG;
				return true;
			}
			if ($bytes6 == "GIF87a" || $bytes6 == "GIF89a") {
				$this->type = ImageType::GIF;
				return true;
			}

			return false;
		}

		return false;
	}

	private function dimensions ($file) {
		$dimensions = getimagesize($file['tmp_name']);
		if ($dimensions === false) return false;

		$max_width = Config::MEDIA_IMAGE_WIDTH;
		$width_src = $dimensions[0];
		$height_src = $dimensions[1];
		$width = $dimensions[0];
		$height = $dimensions[1];

		// Resize based on width
		if ($width_src > $max_width) {
			$ratio = $max_width / $width_src;

			$width = $max_width;
			$height = ceil($height_src * $ratio);
		}

		// Resize based on height
		if ($height_src > $max_width && $height_src > $width_src) {
			$ratio = $max_width / $height_src;

			$height = $max_width;
			$width = ceil($width_src * $ratio);
		}

		$this->width      = $width;
		$this->height     = $height;
		$this->width_src  = $width_src;
		$this->height_src = $height_src;

		return true;
	}

	private function load ($file) {
		switch ($this->type) {
		case ImageType::JPG:
		case ImageType::JPEG:
			$this->image = @imagecreatefromjpeg($file['tmp_name']);
			break;

		case ImageType::GIF:
			$this->image = @imagecreatefromgif($file['tmp_name']);
			break;

		case ImageType::PNG:
			$this->image = @imagecreatefrompng($file['tmp_name']);
			break;

		default:
			$this->image = false;
			break;
		}

		return $this->image !== false;
	}

	private function resize () {
		$destination = imagecreatetruecolor($this->width, $this->height);
		$success = imagecopyresampled(
			$destination,                      // New image
			$this->image,                       // Old image
			0, 0, 0, 0,                         // Image origin coords
			$this->width, $this->height,        // New image size
			$this->width_src, $this->height_src // Old image size
		);

		if (!$success) return false;

		// Overwrite original image, freeing memory first
		imagedestroy($this->image);
		$this->image = $destination;

		return true;
	}

	private function save ($file) {
		// Assume unsuccessful
		$success = false;

		$type = $this->type;

		// Allow manual override of output filetype
		if (
			$this->mimetype_override !== null &&
			ImageType::isValidValue($this->mimetype_override)
		) {
			$type = $this->mimetype_override;
		}

		switch ($type) {
		case ImageType::JPG:
		case ImageType::JPEG:
			$quality = 70; // Use 70% quality
			if (imagetypes() & IMG_JPG) {
				$success = imagejpeg($this->image, $this->filename, $quality);
			} else {
				$success = false;
			}
			break;

		case ImageType::GIF:
			if (imagetypes() & IMG_GIF) {
				$success = imagegif($this->image, $this->filename);
			} else {
				$success = false;
			}
			break;

		case ImageType::PNG:
			$compression = 9; // Use most compression. PNG is lossless after all
			if (imagetypes() & IMG_PNG) {
				$success = imagepng($this->image, $this->filename, $compression);
			} else {
				$success = false;
			}
			break;

		default:
			// This point should not be reached.
			$success = false;
			break;
		}

		// Always destroy the image in memory, regardless of failure
		imagedestroy($this->image);

		return $success;
	}

	private static function generate_filename () {
		return md5(uniqid(rand(), true)) . md5(uniqid(rand(), true));
	}

	public static function upload_dir () {
		return 'uploads/';
	}

	private function set_upload_path ($file) {
		// Change extension if overriding output format
		if ($this->mimetype_override !== null && ImageType::isValidValue($this->mimetype_override)) {
			$extension = $this->mimetype_override;
		} else {
			$extension = $this->type;
		}
		$extension = substr(strrchr($extension, '/'), 1);

		// Override filename
		if ($this->filename_override !== null) {
			$this->filename = self::upload_dir() . $this->filename_override . '.' . $extension;
			return;
		}

		// Generate a random filename
		$this->filename = self::upload_dir() . self::generate_filename() . '.' . $extension;

		// Make sure the local file does not already exist
		while (file_exists($this->filename)) {
			// Set the filename to a random alphanumeric string
			$this->filename = self::upload_dir() . self::generate_filename() . '.' . $extension;
		}
	}
}
