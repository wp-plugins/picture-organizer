<?php
/*
 * Plugin Name: OVM Picture Organizer
 * Text Domain: picture-organizer
 * Plugin URI: http://www.picture-organizer.com
 * Description: Nie wieder Abmahnungen wegen fehlender Urheberrechtsangabe bei Bildern. Mit diesem Plugin kannst Du notwendigen Daten zu jedem Bild zuordnen und über den Shortcode [ovm_picture-organizer liste] z.B. im Impressum als formatierte Liste mit allen Angaben und Links ausgeben.
 * Projekt: ovm-picture-organizer
 * Author: Rudolf Fiedler 
 * Author URI: http://www.profi-blog.com/plugins/picture-organizer
 * Update Server: http://www.profi-blog.com/plugins/picture-organizer
 * License: GPLv2 or later
 * Version: 1.3
 */

/*
Copyright (C)  2014-2015 Rudolf Fiedler

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/


// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/*  Definition der metas für wp_options und post_meta
 *  @since   4.2.2
 *
 */
define('OVM_PO_OPTIONS_TAB','ovm_po_options_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_PICTUREDATA_LIZENZ','ovm_picturedata_lizenz');   //meta-key zum Speichern der PIC-Lizenz-Nr., ist Kriterium für das Vorhandensein von Meta-Daten
define('OVM_PO_PICTUREDATA','ovm_picturedata');   //meta-key zum Speichern der zusätzlichen Lizenzdaten serialized
define('OVM_PO_URI','http://com.profi-blog.com?ovm_po_info=1'); //mit dieser URI werden evtl. andere URIS geholt.

class OVM_Picture_organizer{


    /*  get_curl()
     *  @since 4.2.2
     *
     *
     */

    private function get_curl($uri)
    {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $content = curl_exec($ch);
            curl_close($ch);
        return $content;
    }


   /**
     * Konstruktor der Klasse
     *
     * @since   4.2.2
     */
    public function __construct()
{
    add_filter("attachment_fields_to_edit", array($this,"add_image_attachment_fields_to_edit"), 10, 2);
    add_filter("attachment_fields_to_save", array($this,"add_image_attachment_fields_to_save"), 10 , 2);
    add_shortcode('ovm_picture-organizer',array($this,'show_lizenzinformationen'));
    add_action('admin_menu', array($this,'my_plugin_menu'));

    $possible_uris = json_decode($this->get_curl(OVM_PO_URI));
    define('OVM_PO_DASHBOARD_WARNING',($possible_uris->dashboard_warning>'') ? $possible_uris->dashboard_warning : '');
    define('OVM_PO_DASHBOARD_INFO',($possible_uris->dashboard_info>'') ? $possible_uris->dashboard_info : '');
    define('OVM_PO_COMMERCIAL_URI',($possible_uris->commercial>'') ? $possible_uris->commercial : '');

    if (OVM_PO_DASHBOARD_INFO > '') add_action( 'wp_dashboard_setup', array($this,'show_dashboard_box' ));
    if (OVM_PO_DASHBOARD_WARNING > '') add_action('admin_notices', array($this,'show_dashboard_warning'));
}


    public function show_dashboard_warning(){
        $info = $this->get_curl(OVM_PO_DASHBOARD_WARNING);
        $h = "<div class=\"error\">{$info}</div>";
        echo($h);

    }





    public function show_dashboard_box()
    {
        wp_add_dashboard_widget( "ovm_picture_organizer", "OVM Picture-Organizer", array($this,'picture_organizer_dashboard_widget_content'));
        return;
    }


    public function picture_organizer_dashboard_widget_content()
    {
        echo($this->get_curl(OVM_PO_DASHBOARD_INFO));
    }


    /* Definition des Options-Menüs
     * @since   4.2.2
     */
    public function my_plugin_menu() {
        add_options_page('Picture-Organizer', 'Picture-Organizer', 'manage_options', 'picture-organizer-options.php', array($this,'picture_organizer_options'));
    }

    /* Einstellungen vom Picture-Organizer
     * Aktuell nur die Einstellung, ob beim Deinstallieren alles gelöscht werden soll
     * Weitere Erweiterungen für Premium-Version geplant
     * @since   4.2.2
     */
    public function picture_organizer_options()
    {global $wpdb;
        $tab = sanitize_text_field($_GET['tab']);
        $active_tab = $tab >'' ? $tab : OVM_PO_OPTIONS_TAB;
//-----------------------speichern der Optionen
   if (count($_POST)>0)
        {// Speichern:
            unset($vars);
            switch($active_tab)
            {
                case OVM_PO_OPTIONS_TAB:
                    $vars["uninstall_delete"]=(int)$_POST["uninstall_delete"];
                    update_option($active_tab,$vars);
                    break;
            }
        }


//-----------------------Ende speichern der Optionen
        $o = maybe_unserialize(get_option($active_tab));
        if (is_array($o)) extract($o);
        if ($uninstall_delete==1)
            $c = "checked = \"checked\"";
        else
            $c="";

        //CSS für die eigenen Inhalte
        ?>
        <style type="text/css">
            #form_div      {padding-top:12px;display:block;float:left;width:500px;margin-right:24px;}
            #commercials   {margin-top:18px;display:block;border:1px solid #aaaaaa;width:320px;background-color:#dddddd;float:left}
            fieldset       {border:1px solid #aaaaaa;;padding:12px}
        </style>
        <div class="wrap">
            <h2>Picture-Organizer - Einstellungen</h2>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=picture-organizer-options.php&tab=".OVM_PO_OPTIONS_TAB." class="nav-tab <?php echo $active_tab == OVM_PO_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Uninstall-Einstellungen</a>
            </h2>
            <div id="form_div">
            <form method="post" action="#" id="options_form">
                <?php
                $ds = maybe_unserialize(get_option($active_tab));
                switch ($active_tab)
                {
                    case OVM_PO_OPTIONS_TAB:
                        ?>
                        <fieldset><legend>Einstellung bei Deinstallation</legend>
                        <table>
                            <tr>
                                <th>Alle Daten bei der Deinstallation löschen??</th>
                                <td><input type="checkbox" name="uninstall_delete" value="1" <?=$c?>></td>
                            </tr>
                        </table>
                        </fieldset>
                        <?
                        break;
                } // end switch
                ?>
                <?php     submit_button();?>
            </form>
            </div>
            if (OVM_PO_COMMERCIAL_URI>''){
            ?>
            <div id="commercials">
                <?php
                 echo($this->get_curl(OVM_PO_COMMERCIAL_URI));
                ?>
            </div>
            }?>
        </div><!-- /.wrap -->
    <?php
    }


    /*  function get_picture_credits()
      * Ermittlung aller Posts/type=attachment zur Ausgabe über shortcode
      *
      * @since   4.2.2
      */
    private function get_picture_credits()
    {global $wpdb;

        $args=array(
            'post_type'=>'attachment',
            'meta_key'=>OVM_PO_PICTUREDATA_LIZENZ,
            'meta_compare'=>'>=',
            'meta_value'=>''
        );
        $image_credits = get_posts($args);   //alle attachments in $image_credits
        $h='<style type=text/css>#image_credits td {vertical-align:top}</style>';
        $h.="<table id=\"image_credits\"\n>"; //html zur Ausgabe erzeugen
        foreach($image_credits as $credit)
        {
            $credit_lizenz = get_post_meta($credit->ID,OVM_PO_PICTUREDATA_LIZENZ);
            $credit_data = get_post_meta($credit->ID,OVM_PO_PICTUREDATA);
            //var_dump($credit_data);
            $h .="<tr>
                    <td>{$credit_lizenz[0]}</td>
                    <td><a href=\"{$credit_data[0]['uri']}\">&copy; {$credit_data[0]['author']} {$credit_data[0]['portal']} {$credit_data[0]['kauf']}</a><br>{$credit_data[0]['bemerkung_online']}</td>
                  </tr>\n";
        }
        $h.="</table>\n";//end table width image_credits
        return $h;   //Rückgabe der html-inhalte
    }


/*      show_lizenzinformationen($atts)
 *      Holt die Inhalte der Attachments zur Ausgabe über den Shortcode
 *      @since   4.2.2
 */
 public function show_lizenzinformationen($atts)
    {
        switch($atts[0])
        {
            case "liste":
                $h=$this->get_picture_credits();
                break;
        }
        return $h;
    }
/*      add_image_attachment_fields_to_edit
 *      Erzeugt das Array mit den zusätzlichen Feldinformatinoen für die Ausgabe der Maske im  Pflegebereich/Dashboard
 *      @since 4.2.2
 */
    public function add_image_attachment_fields_to_edit($form_fields, $post)
    {
        $h="
        <style type=\"text/css\">
            .compat-attachment-fields th, .compat-attachment-fields td
            {vertical-align:top;
                border:1px solid #cccccc}
        </style>";
        $h2="
        <h2>Lizenzinformationen für Bilder</h2>
        <p>Diese Felder dienen der Erfassung von Lizenzinformatinen von Bilder, die Du z.B. über Fotolia gekauft hast,
            und die im Impressum oder einem anderen Bildnachweis veröffentlich werden müssen.</p>
        <p>Gerade bei kostenlosen oder sehr preiswerten Bildern ist die Veröffentlichung der Quellen Bestandteil der Lizenzvereinbarung.</p>
        <p>Erfolg die Veröffentlichung nicht, drohen Abmahnungen mit hohen Kosten.</p>
        <p>Hier eingegebene Informationen werden über den Shortcode  angezeigt.</p>
        ";
        //echo($h);
        $ovm_picturedata = maybe_unserialize(get_post_meta($post->ID, OVM_PO_PICTUREDATA, true));
        $ovm_picturedata_lizenz = get_post_meta($post->ID, OVM_PO_PICTUREDATA_LIZENZ, true);

        if (!isset($ovm_picturedata['author'])) {$ovm_picturedata['author']='';}
        if (!isset($ovm_picturedata['portal'])) {$ovm_picturedata['portal']='';}
        if (!isset($ovm_picturedata['uri'])) {$ovm_picturedata['uri']='';}
        if (!isset($ovm_picturedata['kauf'])) {$ovm_picturedata['kauf']='';}
        if (!isset($ovm_picturedata['bemerkung'])) {$ovm_picturedata['bemerkung']='';}
        if (!isset($ovm_picturedata['bemerkung_online'])) {$ovm_picturedata['bemerkung_online']='';}

        $form_fields["author"] = array(
            "label" => __("Autor"),
            "input" => "text", // this is default if "input" is omitted
            "value" => $ovm_picturedata['author'],
            "helps" => __("Der Fotograf des Bildes"),
            'application'=>'image',
            'exclusions'  => array( 'audio', 'video' ),
            'required'=>false,
            'error_text'=>'Feld ist Pflichtfeld, bitte ausfüllen'
        );

        $form_fields["lizenz"] = array(
            "label" => __("Lizenzkey"),
            "input" => "text",
            "value" => $ovm_picturedata_lizenz,
            "helps" => __("Lizenznummer - Achtung: Ohne Lizenznummer erfolgt keine Speicherung und Ausgabe der Lizenzdaten")
        );
        $form_fields["portal"] = array(
            "label" => __("Portal"),
            "input" => "text",
            "value" => $ovm_picturedata['portal'],
            "helps" => __("Download-Portal (z.B. Fotolia)")
        );
        $form_fields["uri"] = array(
            "label" => __("URI"),
            "input" => "text",
            "value" => $ovm_picturedata['uri'],
            "helps" => __("(Link zur Portalseite)")
        );

        $form_fields["kauf"] = array(
            "label" => __("Kaufdatum"),
            "input" => "text",
            "value" => $ovm_picturedata['kauf'],
            "helps" => __("(Kaufdatum des Bildes)")
        );
        $form_fields["bemerkung"] = array(
            "label" => __("Bemerkung (intern)"),
            "input" => "textarea",
            "value" => $ovm_picturedata['bemerkung'],
            "helps" => __("(Interne Hinweise, z.B. Kauf über anderen Namen etc.)")
        );
        $form_fields["bemerkung_online"] = array(
            "label" => __("Bemerkung (Online)"),
            "input" => "textarea",
            "value" => $ovm_picturedata['bemerkung_online'],
            "helps" => __("(Hinweise zur Veröffentlichung, z.B. auf welcher Seite oder wo genau das Bild eingesetzt wird)")
        );
        return $form_fields;
    }

    public function add_image_attachment_fields_to_save($post, $attachment)
    {
        // $attachment part of the form $_POST ($_POST[attachments][postID])
        // $post['post_type'] == 'attachment'
        $ovm_picturedata = array();
        $ovm_picturedata['author'] = $attachment['author'];
        $ovm_picturedata['portal'] = $attachment['portal'];
        $ovm_picturedata['uri'] = $attachment['uri'];
        $ovm_picturedata['kauf'] = $attachment['kauf'];
        $ovm_picturedata['bemerkung']=$attachment['bemerkung'];
        $ovm_picturedata['bemerkung_online']=$attachment['bemerkung_online'];
        update_post_meta($post['ID'], OVM_PO_PICTUREDATA_LIZENZ, $attachment['lizenz']);
        update_post_meta($post['ID'], OVM_PO_PICTUREDATA, $ovm_picturedata);
        return $post;
    }
}//end class

$OVM_Picture_organizer = new OVM_Picture_organizer();
