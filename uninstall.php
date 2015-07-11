<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
/*  Uninstall-Routine des Plugin
 *  Wenn in den Optionen so definiert, werden beim Uninstall ALLE Daten, auch die Bewegungs-Lizenzdaten der enizelnen
 *  Attachments gelöscht
 *  @since   4.2.2
 */
    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit();
    }

    $delete_all = get_option(OVM_PO_UNINSTALL_TAB);
    if ($delete_all['uninstall_delete']==1)
    {//löschen aller Einstellungen in der postmeta
        $wpdb->delete($wpdb->postmeta,array("meta_key"=>'ovm_picturedata_lizenz'));
        $wpdb->delete($wpdb->postmeta,array("meta_key"=>'ovm_picturedata'));
        delete_option('ovm_po_uninstall_tab');
    }
