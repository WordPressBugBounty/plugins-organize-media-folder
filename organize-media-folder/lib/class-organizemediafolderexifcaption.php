<?php
/**
 * Organize Media Folder Add On Exif Caption
 *
 * @package    Organize Media Folder Add On Exif Caption
 * @subpackage OrganizeMediaFolderExifCaption Main functions
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

$organizemediafolderexifcaption = new OrganizeMediaFolderExifCaption();

/** ==================================================
 * Main Functions
 */
class OrganizeMediaFolderExifCaption {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );

		add_action( 'omf_exifcaption_settings', array( $this, 'exifcaption_html' ) );
		add_action( 'omf_exifcaption_options_updated', array( $this, 'options_updated' ) );
		add_action( 'omf_exifcaption_regist', array( $this, 'do_exif_caption' ), 10, 2 );
	}

	/** ==================================================
	 * Do Exif caption
	 *
	 * @param array $metadata  metadata.
	 * @param int   $attach_id  ID.
	 * @since 1.00
	 */
	public function do_exif_caption( $metadata, $attach_id ) {

		if ( get_option( 'omf_addon_exifcaption' ) ) {

			$omf_add_on_exifcaption_settings = get_option( 'omf_addon_exifcaption' );

			if ( function_exists( 'wp_get_original_image_path' ) ) {
				$file_path = wp_get_original_image_path( $attach_id );
			} else {
				$file_path = get_attached_file( $attach_id );
			}
			$filetype = wp_check_filetype( $file_path );
			if ( 'image/jpeg' === $filetype['type'] || 'image/tiff' === $filetype['type'] ) {
				if ( $omf_add_on_exifcaption_settings['caption']['apply'] ) {
					$exif = @exif_read_data( $file_path, 0, true );
					$this->exifcaption( $attach_id, $metadata, $exif, $omf_add_on_exifcaption_settings['caption']['exif_text'] );
				}
			}
		}
	}

	/** ==================================================
	 * Exif caption
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param array  $exif  exif.
	 * @param string $exif_text_tag  exif_text_tag.
	 * @since 1.00
	 */
	private function exifcaption( $attach_id, $metadata, $exif, $exif_text_tag ) {

		do_action( 'exif_details_update', $attach_id );

		$exifdatas = array();
		if ( $metadata['image_meta']['title'] ) {
			$exifdatas['title'] = $metadata['image_meta']['title'];
		}
		if ( $metadata['image_meta']['credit'] ) {
			$exifdatas['credit'] = $metadata['image_meta']['credit'];
		}
		if ( $metadata['image_meta']['camera'] ) {
			$exifdatas['camera'] = $metadata['image_meta']['camera'];
		}
		if ( $metadata['image_meta']['caption'] ) {
			$exifdatas['caption'] = $metadata['image_meta']['caption'];
		}
		$exif_ux_time = $metadata['image_meta']['created_timestamp'];
		if ( ! empty( $exif_ux_time ) ) {
			if ( function_exists( 'wp_date' ) ) {
				$exifdatas['created_timestamp'] = wp_date( 'Y-m-d H:i:s', $exif_ux_time, new DateTimeZone( 'UTC' ) );
			} else {
				$exifdatas['created_timestamp'] = date_i18n( 'Y-m-d H:i:s', $exif_ux_time, false );
			}
		} elseif ( isset( $exif['EXIF']['DateTimeOriginal'] ) && ! empty( $exif['EXIF']['DateTimeOriginal'] ) ) {
				$shooting_date_time = $exif['EXIF']['DateTimeOriginal'];
				$shooting_date = str_replace( ':', '-', substr( $shooting_date_time, 0, 10 ) );
				$shooting_time = substr( $shooting_date_time, 10 );
				$exifdatas['created_timestamp'] = $shooting_date . $shooting_time;
		}
		if ( $metadata['image_meta']['copyright'] ) {
			$exifdatas['copyright'] = $metadata['image_meta']['copyright'];
		}
		if ( $metadata['image_meta']['aperture'] ) {
			$exifdatas['aperture'] = 'f/' . $metadata['image_meta']['aperture'];
		}
		if ( $metadata['image_meta']['shutter_speed'] ) {
			if ( $metadata['image_meta']['shutter_speed'] < 1 ) {
				$shutter = round( 1 / $metadata['image_meta']['shutter_speed'] );
				$exifdatas['shutter_speed'] = '1/' . $shutter . 'sec';
			} else {
				$exifdatas['shutter_speed'] = $metadata['image_meta']['shutter_speed'] . 'sec';
			}
		}
		if ( $metadata['image_meta']['iso'] ) {
			$exifdatas['iso'] = 'ISO-' . $metadata['image_meta']['iso'];
		}
		if ( $metadata['image_meta']['focal_length'] ) {
			$exifdatas['focal_length'] = $metadata['image_meta']['focal_length'] . 'mm';
		}
		if ( $metadata['image_meta']['orientation'] ) {
			$ort_no = $metadata['image_meta']['orientation'];
			$ort_text = null;
			switch ( $ort_no ) {
				case 1:
					$ort_text = __( 'Horizontal (normal)', 'organize-media-folder' );
					break;
				case 2:
					$ort_text = __( 'Mirror horizontal', 'organize-media-folder' );
					break;
				case 3:
					$ort_text = __( 'Rotate 180', 'organize-media-folder' );
					break;
				case 4:
					$ort_text = __( 'Mirror vertical', 'organize-media-folder' );
					break;
				case 5:
					$ort_text = __( 'Mirror horizontal and rotate 270 CW', 'organize-media-folder' );
					break;
				case 6:
					$ort_text = __( 'Rotate 90 CW', 'organize-media-folder' );
					break;
				case 7:
					$ort_text = __( 'Mirror horizontal and rotate 90 CW', 'organize-media-folder' );
					break;
				case 8:
					$ort_text = __( 'Rotate 270 CW', 'organize-media-folder' );
					break;
			}
			$exifdatas['orientation'] = $ort_text;
		}

		if ( ! empty( get_post_meta( $attach_id, '_exif_details', true ) ) ) {
			$exifdatas += get_post_meta( $attach_id, '_exif_details', true );
		}

		$exif_text = null;
		if ( $exifdatas ) {
			$exif_text = $exif_text_tag;
			foreach ( $exifdatas as $item => $exif ) {
				$exif_text = str_replace( '%' . $item . '%', $exif, $exif_text );
			}
			preg_match_all( '/%(.*?)%/', $exif_text, $exif_text_per_match );
			foreach ( $exif_text_per_match as $key1 ) {
				foreach ( $key1 as $key2 ) {
					$exif_text = str_replace( '%' . $key2 . '%', '', $exif_text );
				}
			}
		}

		if ( ! empty( $exif_text ) ) {
			/* Change DB Attachement post */
			global $wpdb;
			$update_array = array(
				'post_excerpt' => $exif_text,
			);
			$id_array = array( 'ID' => $attach_id );
			$wpdb->update( $wpdb->posts, $update_array, $id_array, array( '%s' ), array( '%d' ) );
			unset( $update_array, $id_array );
		}
	}

	/** ==================================================
	 * Exif html
	 *
	 * @since 1.00
	 */
	public function exifcaption_html() {

		$omf_add_on_exifcaption_settings = get_option( 'omf_addon_exifcaption' );

		?>
		<div style="display:block;padding:5px 0">
		<input type="checkbox" name="omf_exifcaption_apply" value="1" <?php checked( true, $omf_add_on_exifcaption_settings['caption']['apply'] ); ?> />
		<?php esc_html_e( 'Apply', 'organize-media-folder' ); ?>
		</div>
		<div style="display: block; padding:5px 20px;">
		Exif <?php esc_html_e( 'Tags', 'organize-media-folder' ); ?>
		<input type="button" name="omf_exifcaption_default" id="omf_exifcaption_default" class="button" value="<?php esc_attr_e( 'Default', 'organize-media-folder' ); ?>" style="position:relative; top:-5px;"  />
		<div style="display: block; padding:5px 20px;">
		<textarea name="omf_exifcaption_text" style="width: 100%;"><?php echo esc_textarea( $omf_add_on_exifcaption_settings['caption']['exif_text'] ); ?></textarea>
		<div>
		<a href="https://developer.wordpress.org/reference/functions/wp_read_image_metadata/" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;"><?php esc_html_e( 'For Exif tags, please read here.', 'organize-media-folder' ); ?></a>
		</div></div></div>
		<?php
		if ( is_multisite() ) {
			$exifdetails_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=exif-details' );
			$exifcaption_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=exif-caption' );
		} else {
			$exifdetails_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=exif-details' );
			$exifcaption_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=exif-caption' );
		}
		$exifdetails_install_html = '<a href="' . $exifdetails_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">Exif Details</a>';
		$exifcaption_install_html = '<a href="' . $exifcaption_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">Exif Caption</a>';
		?>
		<div>
		<?php
		/* translators: Exif Details install link */
		echo wp_kses_post( sprintf( __( 'If you want to extend the Exif tags, Please use the %1$s.', 'organize-media-folder' ), $exifdetails_install_html ) );
		?>
		</div><div>
		<?php
		/* translators: Exif Caption install link */
		echo wp_kses_post( sprintf( __( 'If you want to insert the Exif in the media that have already been registered in the media library, Please use the %1$s.', 'organize-media-folder' ), $exifcaption_install_html ) );
		?>
		</div>
		<?php
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 1.00
	 */
	public function load_custom_wp_admin_style() {
		if ( $this->is_my_plugin_screen() ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'omfaddonexifcaption-js', plugin_dir_url( __DIR__ ) . 'js/jquery.omfaddonexifcaption.js', array( 'jquery' ), '1.00', false );
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'media_page_organizemediafolder' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	public function options_updated() {

		$omf_add_on_exifcaption_settings = get_option( 'omf_addon_exifcaption' );

		if ( isset( $_POST['organize-media-folder-settings-options-apply'] ) && ! empty( $_POST['organize-media-folder-settings-options-apply'] ) ) {
			if ( check_admin_referer( 'omf_settings', 'organize_media_folder_settings' ) ) {
				if ( ! empty( $_POST['omf_exifcaption_apply'] ) ) {
					$omf_add_on_exifcaption_settings['caption']['apply'] = true;
				} else {
					$omf_add_on_exifcaption_settings['caption']['apply'] = false;
				}
				if ( ! empty( $_POST['omf_exifcaption_text'] ) ) {
					$omf_add_on_exifcaption_settings['caption']['exif_text'] = wp_strip_all_tags( wp_unslash( $_POST['omf_exifcaption_text'] ) );
				}
				update_option( 'omf_addon_exifcaption', $omf_add_on_exifcaption_settings );
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		$caption_apply = true;
		$exif_text = '%title% %credit% %camera% %caption% %created_timestamp% %copyright% %aperture% %shutter_speed% %iso% %focal_length% %white_balance% %orientation%';

		if ( ! get_option( 'omf_addon_exifcaption' ) ) {
			$omf_addon_exifcaption_tbl = array(
				'caption' => array(
					'apply' => $caption_apply,
					'exif_text' => $exif_text,
				),
			);
			update_option( 'omf_addon_exifcaption', $omf_addon_exifcaption_tbl );
		}
	}
}


