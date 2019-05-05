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


class ImageResizer
{
	private $image;
	private $width;
	private $height;
	private $width_src;
	private $height_src;
	private $filename;

	/**
	 * Load and resize an uploaded file
	 * @param array $file
	 * @return ImageResizer
	 */
	function __construct ($file, $filename_override = null) {
		if (empty($file['tmp_name'])) {
			throw new Exception('Filename was not provided');
		}

		// Check file type (allow images only)
		if (!$this->is_valid_type($file)) {
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

		// Set upload path
		$this->set_upload_path($file, $filename_override);

		// Save image to uploads directory
		if (!$this->save($file)) {
			throw new Exception('Image could not be saved to file');
		}
	}

	public function get_permalink () {
		return ml_base_url() . $this->filename;
	}

	private function is_valid_type ($file) {
		$allowed_mime_types = [
			'image/jpg', 'image/jpeg', 'image/gif', 'image/png',
		];

		return in_array($file['type'], $allowed_mime_types);
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
		switch ($file['type']) {
		case 'image/jpg':
		case 'image/jpeg':
			$this->image = @imagecreatefromjpeg($file['tmp_name']);
			break;
		case 'image/gif':
			$this->image = @imagecreatefromgif($file['tmp_name']);
			break;
		case 'image/png':
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
			$destination,                       // New image
			$this->image,                       // Old image
			0,
			0,
			0,
			0,                         // Image origin coords
			$this->width,
			$this->height,        // New image size
			$this->width_src,
			$this->height_src // Old image size
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

		switch ($file['type']) {
		case 'image/jpg':
		case 'image/jpeg':
			$quality = 70; // Use 70% quality
			if (imagetypes() & IMG_JPG) {
				$success = imagejpeg($this->image, $this->filename, $quality);
			} else {
				$success = false;
			}
			break;

		case 'image/gif':
			if (imagetypes() & IMG_GIF) {
				$success = imagegif($this->image, $this->filename);
			} else {
				$success = false;
			}
			break;

		case 'image/png':
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

	private function set_upload_path ($file, $filename_override) {
		$extension = strrchr($file['name'], '.');

		if (isset($filename_override) && $filename_override !== null) {
			return 'uploads/' . $filename_override . $extension;
		}

		$this->filename = 'uploads/' . md5(uniqid(rand(), true)) . $extension;

		// Make sure the local file does not already exist
		while (file_exists($this->filename)) {
			// Set the filename to a random alphanumeric string
			$this->filename = 'uploads/' . md5(uniqid(rand(), true)) . $extension;
		}
	}
}
