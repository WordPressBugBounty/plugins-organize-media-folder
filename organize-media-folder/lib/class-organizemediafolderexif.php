<?php
/**
 * Organize Media Folder Add On Exif
 *
 * @package    Organize Media Folder Add On Exif
 * @subpackage OrganizeMediaFolderExif Main functions
/*
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$organizemediafolderexif = new OrganizeMediaFolderExif();

/** ==================================================
 * Main Functions
 */
class OrganizeMediaFolderExif {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'omf_exif_settings', array( $this, 'exif_html' ), 10, 1 );
		add_filter( 'omf_exif_date', array( $this, 'exif_date' ), 10, 2 );
	}

	/** ==================================================
	 * Exif date
	 *
	 * @param int $id  post id.
	 * @return string $date or bool false
	 * @since 1.00
	 */
	public function exif_date( $id ) {

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$attache_file = wp_get_original_image_path( $id );
		} else {
			$attache_file = get_attached_file( $id );
		}
		if ( ! empty( $attache_file ) ) {
			$filetype = wp_check_filetype( $attache_file );
			if ( 'image/jpeg' === $filetype['type'] || 'image/tiff' === $filetype['type'] ) {
				$shooting_date_time = null;
				$exifdata = @exif_read_data( $attache_file, 'FILE', true );
				if ( isset( $exifdata['EXIF']['DateTimeOriginal'] ) && ! empty( $exifdata['EXIF']['DateTimeOriginal'] ) ) {
					$shooting_date_time = $exifdata['EXIF']['DateTimeOriginal'];
				} else if ( isset( $exifdata['IFD0']['DateTime'] ) && ! empty( $exifdata['IFD0']['DateTime'] ) ) {
					$shooting_date_time = $exifdata['IFD0']['DateTime'];
				}
				if ( ! empty( $shooting_date_time ) ) {
					$shooting_date = str_replace( ':', '-', substr( $shooting_date_time, 0, 10 ) );
					$shooting_time = substr( $shooting_date_time, 10 );
					$date = $shooting_date . $shooting_time;
					return $date;
				}
			}
		}

		return false;
	}

	/** ==================================================
	 * Exif html
	 *
	 * @param array $omf_admin_settings  Settings.
	 * @since 1.00
	 */
	public function exif_html( $omf_admin_settings ) {
		?>
		<div style="display: block; padding:5px 5px">
			<input type="radio" name="dateset" value="exif" <?php checked( 'exif', $omf_admin_settings['dateset'] ); ?> /><?php esc_html_e( 'Update to use of the exif information date/time.', 'organize-media-folder' ); ?>
		</div>
		<?php
	}
}


