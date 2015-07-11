<?php
/*
 * Plugin Name: OVM Picture Organizer
 * Version: 1.4.3
 * Text Domain: picture-organizer
 * Plugin URI: http://www.picture-organizer.com
 * Description: Nie wieder Abmahnungen wegen fehlender _Bildnachweise bei Bildern. Mit diesem Plugin kannst Du notwendigen Daten zu jedem Bild zuordnen und über den Shortcode [ovm_picture-organizer liste] z.B. im Impressum als formatierte Liste mit allen Angaben und Links ausgeben.
 * Projekt: ovm-picture-organizer
 * Author: Rudolf Fiedler 
 * Author URI: http://www.picture-organizer.com
 * License: GPLv2 or later

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
 *  @since   1.1
 *
 */
define('PO_EMAIL','r.fiedler@ovm.de');
define('OVM_PO_OPTIONS_TAB','ovm_po_options_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_OUTPUT_OPTIONS_TAB','ovm_po_output_options_tab');  //Tab for output-options
define('OVM_PO_PICTUREDATA_LIZENZ','ovm_picturedata_lizenz');   //meta-key zum Speichern der PIC-Lizenz-Nr., ist Kriterium für das Vorhandensein von Meta-Daten
define('OVM_PO_PICTUREDATA','ovm_picturedata');   //meta-key zum Speichern der zusätzlichen Lizenzdaten serialized

if (get_option("siteurl")=='http://po')
    define('OVM_PO_URI','http://basic/?ovm_po_info=1&log=1'); //test/development
  else
   define('OVM_PO_URI','http://com.profi-blog.com/?ovm_po_info=1&log=1'); //production-environment


class OVM_Picture_organizer{
    public $plugin_data,$blogurl,$checked;
    public $dashboard_warning,$dashboard_info,$commercial;


    /**
     * Konstruktor der Klasse
     *
    *  @since   1.1
     */
    public function __construct()
{
    $this->blogurl = get_bloginfo('url');
    if (is_admin()) { //actions for backend
        add_action('admin_head', array($this,'css_for_mediadetails' ));
        add_action('admin_head', array($this,'get_po_links'));
        add_action('admin_menu', array($this, 'my_plugin_menu'));
        add_filter("attachment_fields_to_edit", array($this, "add_image_attachment_fields_to_edit"), 10, 2);
        add_filter("attachment_fields_to_save", array($this, "add_image_attachment_fields_to_save"), 10, 2);

        add_action('wp_dashboard_setup', array($this, 'show_dashboard_box'));
        add_action('admin_notices', array($this, 'show_dashboard_warning'));
    }
    else {//frontend
        add_shortcode('ovm_picture-organizer', array($this, 'show_lizenzinformationen'));
    }
}

    /**  Bei Aktivierung des Plugins werden die Default-Einstellungen für die Ausgabe definiert und gespeichert
     *
     */
    public function plugin_init() {
        $vars = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
        if (false === $vars) {//Vorhandene Daten nicht überschreiben
            $vars["promotion_text"] = 'Bildnachweise einfach und professionell verwalten und ausgeben mit dem <a href="http://www.picture-organizer.com" target="_blank">Picture-Organizer</a>';
            $vars["promotion_position"] = 0;
            update_option(OVM_PO_OUTPUT_OPTIONS_TAB, $vars);
        }
    }

    /**  get_po_links()
     *   Checks only in dashboard-mode, attachment-details and in po-settings
     *
     */
    public function get_po_links(){
        $screen = get_current_screen();
        if ($screen->base=='dashboard' or $screen->base=="settings_page_picture-organizer-options" or ($screen->base=='post' and $screen->post_type=='attachment')) {
            $this->possible_uris = $this->get_uri(OVM_PO_URI);
            if (is_object($this->possible_uris)) {
                $this->dashboard_warning = ($this->possible_uris->dashboard_warning > '') ? $this->possible_uris->dashboard_warning : '';
                $this->dashboard_info = ($this->possible_uris->dashboard_info > '') ? $this->possible_uris->dashboard_info : '';
                $this->commercial = ($this->possible_uris->commercial > '') ? $this->possible_uris->commercial : '';
            }
        }
        return;
    }

    /*  show_dashboard_warning()
     *  shows a warning in the dashboard in case of a link ist omitted via com.picture-organizer.com
     *
     */
    public function show_dashboard_warning(){
        if ($this->dashboard_warning > '') {
            $info = $this->get_uri($this->dashboard_warning, false);
            if ($info > '') {
                $h = "<div class=\"error\">{$info}</div>";
                echo($h);
            }
        }
    }


    /*  show_dashboard_box()
     *  shows a dashboard-info-box in the dashboard in case of a link ist omitted via com.picture-organizer.com
     *  only using for important infos, but not warnings.
     */
    public function show_dashboard_box()
    {
        if ($this->dashboard_info > '')
            wp_add_dashboard_widget( "ovm_picture_organizer", "OVM Picture-Organizer", array($this,'picture_organizer_dashboard_widget_content'));
        return;
    }



    /*  picture_organizer_dashboard_widget_content()
     *  Using only for important infos like neccessary update or anything else...
     *  Is only shown if OVM_PO_DASHBOARD_INFO > '', is controlled in _construct width setting action or not
     *
     */
    public function picture_organizer_dashboard_widget_content()
    {
        if ($this->dashboard_info > '') echo($this->get_uri($this->dashboard_info,false));
    }


    /* Definition des Options-Menüs
 *  @since   1.1
     */
    public function my_plugin_menu() {
        add_options_page('Picture-Organizer', 'Picture-Organizer', 'manage_options', 'picture-organizer-options.php', array($this,'picture_organizer_options'));
    }

    /* Einstellungen vom Picture-Organizer
     * Aktuell nur die Einstellung, ob beim Deinstallieren alles gelöscht werden soll
     * Weitere Erweiterungen für Premium-Version geplant
 *  @since   1.1
     */
    public function picture_organizer_options()
    {global $wpdb;
        $active_tab = sanitize_text_field(isset($_GET['tab'])? $_GET['tab']:OVM_PO_OUTPUT_OPTIONS_TAB);
        $this->checked = ' checked="checked" ';
//-----------------------speichern der Optionen
   if (count($_POST)>0)
        {// Speichern:
            unset($vars);
            switch($active_tab)
            {
                case OVM_PO_OPTIONS_TAB:
                    $vars["uninstall_delete"]=(int)$_POST["uninstall_delete"];
                    break;
                case OVM_PO_OUTPUT_OPTIONS_TAB:
                    $vars["promotion_text"]=stripslashes($_POST["promotion_text"]);
                    $vars["promotion_position"]=(int)$_POST["promotion_position"];
            }
            update_option($active_tab,$vars);
        }


//-----------------------Ende speichern der Optionen
        $o = maybe_unserialize(get_option($active_tab));
        if (is_array($o)) extract($o);
        //CSS für die eigenen Inhalte
        ?>
        <style type="text/css">
            #form_div      {padding-top:12px;display:block;float:left;width:500px;margin-right:24px;}
            #ovm_po_commercials   {margin-top:18px;display:block;border:1px solid #aaaaaa;width:320px;float:left}
            .ovm fieldset       {border:1px solid #aaaaaa;;padding:8px;width:100%}
            .ovm table th   {text-align:left}
            .ovm table th, .ovm table td   {vertical-align:top}
            .ovm textarea     {width:100%;height:200px;font-size:10pt}
        </style>

        <div class="wrap ovm">
            <h2>Picture-Organizer - Einstellungen</h2>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OUTPUT_OPTIONS_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_OUTPUT_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Ausgabe-Einstellungen</a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OPTIONS_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Uninstall-Einstellungen</a>
            </h2>
            <div id="form_div">
            <form method="post" action="#" id="options_form">
                <?php
                switch ($active_tab)
                {
                    case OVM_PO_OUTPUT_OPTIONS_TAB:
                        if (!isset($promotion_position)) $promotion_position=0;  //Default keine Ausgabe!

                        ?>
                        <fieldset><legend>Einstellungen für die Ausgabe der Bildnachweise</legend>
                            <table style="width:100%">
                                <tr>
                                    <th>HTML-Text für die Ausgabe</th>
                                    <td><textarea name="promotion_text" id="promotion_text"><?php echo(isset($promotion_text)?$promotion_text :'')?></textarea></td>
                                </tr>
                                <tr>
                                    <th>Positionierung des Textes</th>
                                    <td><p>Zeigen Sie Ihren Besuchern, dass Sie Ihre Homepage und Bildnachweise professionell organisieren!<br>Die beste Wirkung erreichen Sie durch Positionierung oberhalb der Nachweise.</p>

                                        <input type="radio" name="promotion_position" id="promotion_position" value="0" <?php echo((0==$promotion_position) ?$this->checked:'') ?>> Nicht ausgeben <br>
                                        <input type="radio" name="promotion_position" id="promotion_position" value="1" <?php echo((1==$promotion_position) ?$this->checked:'') ?>> Oben ausgeben <br>
                                        <input type="radio" name="promotion_position" id="promotion_position" value="2" <?php echo((2==$promotion_position) ?$this->checked:'') ?>> Unten ausgeben <br>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                        <?
                        break;
                    case OVM_PO_OPTIONS_TAB:
                        if (!isset($uninstall_delete)) $uninstall_delete=0;
                        if (1 == $uninstall_delete)
                            $c = "checked = \"checked\"";
                        else
                            $c="";

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
                <?php submit_button();?>
            </form>
            </div>
            <?php
              if ($this->commercial > ''){
              $h = '<div id="ovm_po_commercials">'.$this->get_uri($this->commercial,false).'</div>';echo($h);
              }?>
        </div><!-- /.wrap -->
    <?php
    }


    /*  function get_picture_credits()
      * Ermittlung aller Posts/type=attachment zur Ausgabe über shortcode
      *
      *  @since   1.1
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
        $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);

        if ($promotion_ausgabe===false) {
            $this->plugin_init();
            $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
        }// Anlege

        if($promotion_ausgabe["promotion_position"]==1) {
            $h .= "<p>{$promotion_ausgabe['promotion_text']}</p>";
        }


        $h.="<table id=\"image_credits\"\n>"; //html zur Ausgabe erzeugen
        foreach($image_credits as $credit)
        {
            $credit_lizenz = get_post_meta($credit->ID,OVM_PO_PICTUREDATA_LIZENZ);
            $credit_data = get_post_meta($credit->ID,OVM_PO_PICTUREDATA);
            $h .="<tr>
                    <td>{$credit_lizenz[0]}</td>
                    <td><a href=\"{$credit_data[0]['uri']}\">&copy; {$credit_data[0]['author']} {$credit_data[0]['portal']} {$credit_data[0]['kauf']}</a><br>{$credit_data[0]['bemerkung_online']}</td>
                  </tr>\n";
        }
        $h.="</table>\n";//end table width image_credits
        if($promotion_ausgabe["promotion_position"]==2) {
            $h .= "<p>{$promotion_ausgabe['promotion_text']}</p>";
        }
        return $h;   //Rückgabe der html-inhalte
    }


/*      show_lizenzinformationen($atts)
 *      Holt die Inhalte der Attachments zur Ausgabe über den Shortcode
      *  @since   1.1
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
      *  @since   1.1
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


    /**
     * @param $uri : uri to read
     * @return mixed in json-format
     */
    private function get_uri($uri,$json_encode=true)
    {
        $args = array(
            'timeout' => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent' => 'WordPress/' . '; ' . get_bloginfo('url'),
            'blocking' => true,
            'headers' => array(),
            'cookies' => array(),
            'body' => null,
            'compress' => false,
            'decompress' => true,
            'sslverify' => true,
            'stream' => false,
            'filename' => null
        );
        if (strpos($uri, "?") === false)
            $uri .= "?";
        else
            $uri .= "&";
        if (!function_exists('get_plugin_data')) require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $this->plugin_data = get_plugin_data(__FILE__);
        $uri .= "version=" . $this->plugin_data['Version'] . "&p=" . $this->blogurl;

        $response = wp_remote_get($uri, $args);
        if (is_wp_error($response)) {
            $message = "Fehler bei wp_remote_get, uri: " . $uri . "\n";
            $message .= "\nError-Message:" . $response->errors["http_request_failed"][0];
            mail(PO_EMAIL, "wp_remote_get_error", $message);
            return false;   //error getting Info-Uris - no problem - be silent
        }
        if ($json_encode === true) {
            $retval = json_decode($response['body']);  //return error-free response
        } else {
            $retval = $response['body'];
        }
        return $retval;
    }

    /**  css_for_mediatetails()
     *   Adds CSS-Infos for the mediacenter-detail-pages
     *
     */
    public function css_for_mediadetails(){
        $h = '<style type="text/css" media="screen">.compat-attachment-fields th { text-align:left;vertical-align:top }</style>';
        echo $h;
        return;
    }


}//end class

$OVM_Picture_organizer = new OVM_Picture_organizer();
