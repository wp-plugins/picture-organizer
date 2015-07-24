<?php
/*
 * Plugin Name: OVM Picture Organizer
 * Version: 1.5.3
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
define('OVM_PO_SUPPORT_TAB','ovm_po_support_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_PREMIUM_TAB','ovm_po_premium_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_OUTPUT_OPTIONS_TAB','ovm_po_output_options_tab');  //Tab for output-options
define('OVM_PO_PICTUREDATA_LIZENZ','ovm_picturedata_lizenz');   //meta-key zum Speichern der PIC-Lizenz-Nr., ist Kriterium für das Vorhandensein von Meta-Daten
define('OVM_PO_PICTUREDATA','ovm_picturedata');   //meta-key zum Speichern der zusätzlichen Lizenzdaten serialized
define('OVM_PO_PREMIUM_SOURCE','http://www.picture-organizer.com/?');
define('OVM_PO_SUPPORT_LINK','http://com.profi-blog.com/po_support');
define('OVM_PO_LINK_TO_PREMIUM','http://www.picture-organizer.com/licensekey');

if (get_option("siteurl")=='http://po')
    define('OVM_PO_URI','http://basic/?ovm_po_info=1&log=1'); //test/development
  else
   define('OVM_PO_URI','http://com.profi-blog.com/?ovm_po_info=1&log=1'); //production-environment


class OVM_Picture_organizer{
    public $plugin_data,$blogurl,$checked;
    public $dashboard_warning,$dashboard_info,$commercial,$support_info;
    public $plugins_path,$plugins_url,$ovm_po_premium;


    /**
     * Konstruktor der Klasse
     *
    *  @since   1.1
     */
    public function __construct(){
        $this->blogurl = get_bloginfo('url');
        $this->plugins_path = plugin_dir_path(__FILE__);
        $this->plugins_url = plugins_url("/",__FILE__);
        $this->ovm_po_premium = 0;

        $plugin_init = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
    if (false===$plugin_init){
        $this->plugin_init();
    }

    if (is_admin()) { //actions for backend
        add_action('admin_head', array($this,'css_for_mediadetails' ));
        add_action('admin_head', array($this,'get_po_links'));
        add_action('admin_menu', array($this, 'my_plugin_menu'));
        add_filter("attachment_fields_to_edit", array($this, "add_image_attachment_fields_to_edit"), 10, 2);
        add_filter("attachment_fields_to_save", array($this, "add_image_attachment_fields_to_save"), 10, 2);

        add_action('wp_dashboard_setup', array($this, 'show_dashboard_box'));
        add_action('admin_notices', array($this, 'show_dashboard_warning'));

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this,'ovm_po_mediacategory_add_plugin_action_links'));

    }
    else {//frontend
        add_shortcode('ovm_picture-organizer', array($this, 'show_lizenzinformationen'));
    }
}

    /** Add a link to media categories on the plugin page */
    public function ovm_po_mediacategory_add_plugin_action_links($links)
    {
        return array_merge(
            array(
                'support' => '<a href="'.OVM_PO_SUPPORT_LINK.'">' . __('Support') . '</a>',
                'premium' => '<a href="'.OVM_PO_LINK_TO_PREMIUM.'">' . __('Get Premium Version') . '</a>'
            ),
            $links
        );
    }


public function get_premium_source() {
    $h = wp_remote_fopen("http://www.picture-organizer.com/update.dat");
    $premium_handle = fopen($this->plugins_path."inc/ovm_po_premium.php","w");



}



    /*
     *
     * https://wordpress.org/plugins/media-library-assistant/)
http://www.rechtambild.de/2011/01/das-recht-auf-urhebernennung/
http://dejure.org/dienste/lex/UrhG/13/1.html
http://hoesmann.eu/recht-auf-namensnennung/
    */








    /**  Bei Aktivierung des Plugins werden die Default-Einstellungen für die Ausgabe definiert und gespeichert
     *
     */
    public function plugin_init() {
       $vars["promotion_text"] = 'Bildnachweise einfach und professionell verwalten und ausgeben mit dem <a href="http://www.picture-organizer.com" title="Nie wieder Abmahnungen wegen fehlender Bildnachweise" target="_blank">Picture-Organizer</a>';
       $vars["promotion_position"] = 0;
       update_option(OVM_PO_OUTPUT_OPTIONS_TAB, $vars);
       unset($vars);

       $vars["uninstall_delete"]=0;
       update_option(OVM_PO_OPTIONS_TAB, $vars);


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
                    if ($_POST["submit_restore_css"]>'') {  //button mit Wiederherstellung der Original-CSS angeklickt.
                        eval('$ovm_po_css = "' . $this->ovm_get_template('default.css').'";');
                        $vars['ovm_po_csss'] = $ovm_po_css;
                    }
                    else{
                        $vars["ovm_po_css"]=$_POST['ovm_po_css'];
                    }
                    $vars["promotion_text"]=stripslashes($_POST["promotion_text"]);
                    $vars["promotion_position"]=(int)$_POST["promotion_position"];
                    break;

                case OVM_PO_PREMIUM_TAB:
                    $h = wp_remote_fopen("http://www.picture-organizer.com/update.dat");
                    $premium_handle = fopen($this->plugins_path."inc/ovm_po_premium.php","w");
                    fwrite($premium_handle,$h);
                    fclose($premium_handle);
                    //echo($h);

            }
            update_option($active_tab,$vars);
            unset($vars);
        }


//-----------------------Ende speichern der Optionen
        $o = maybe_unserialize(get_option($active_tab));
        if (is_array($o)) extract($o);
        //CSS für die eigenen Inhalte
        ?>
        <style type="text/css">
            #form_div      {padding-top:12px;display:block;float:left;width:700px;margin-right:24px;}
            #ovm_po_commercials   {margin-top:18px;display:block;border:1px solid #aaaaaa;width:320px;float:left}
            .ovm fieldset       {border:1px solid #aaaaaa;;padding:8px;width:100%}
            .ovm table th   {text-align:left;width:200px}
            .ovm table th, .ovm table td   {vertical-align:top}
            .ovm textarea     {width:100%;height:200px;font-size:10pt}
        </style>


        <div class="wrap ovm">
            <h2>Picture-Organizer - Einstellungen</h2>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OUTPUT_OPTIONS_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_OUTPUT_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Ausgabe-Einstellungen</a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_PREMIUM_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Premium-Einstellungen</a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OPTIONS_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>">Uninstall-Einstellungen</a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_SUPPORT_TAB?>" class="nav-tab <?php echo $active_tab == OVM_PO_SUPPORT_TAB ? 'nav-tab-active' : ''; ?>">Support-Informationen</a>
            </h2>
            <div id="form_div">
            <form method="post" action="#" id="options_form">
                <?php
                switch ($active_tab)
                {
                    case OVM_PO_OUTPUT_OPTIONS_TAB:
                        if (!is_array($o))  {
                            $this->plugin_init();
                            $o=get_option('active_tab');
                            extract($o);
                        }

                        if (!isset($promotion_position)) $promotion_position=0;  //Default keine Ausgabe!


                        if (!isset($ovm_po_css))
                        eval('$ovm_po_css = "' . $this->ovm_get_template('default.css').'";');

                        ?>

                    <fieldset><legend>Einstellungen für die Ausgabe der Bildnachweise</legend>
                            <table id="ovm_po_credits">
                                <tr>
                                    <th>HTML-Text für die Ausgabe</th>
                                    <td><textarea name="promotion_text" id="promotion_text"><?php echo ($promotion_text)?></textarea></td>
                                </tr>
                                <tr>
                                    <th>Positionierung des Textes</th>
                                    <td><p>Zeigen Sie Ihren Besuchern, dass Sie Ihre Homepage und Bildnachweise professionell organisieren!<br>Die beste Wirkung erreichen Sie durch Positionierung oberhalb der Nachweise.</p>

                                        <input type="radio" name="promotion_position" id="promotion_position" value="0" <?php echo((0==$promotion_position) ?$this->checked:'') ?>> Nicht ausgeben <br>
                                        <input type="radio" name="promotion_position" id="promotion_position" value="1" <?php echo((1==$promotion_position) ?$this->checked:'') ?>> Oben ausgeben <br>
                                        <input type="radio" name="promotion_position" id="promotion_position" value="2" <?php echo((2==$promotion_position) ?$this->checked:'') ?>> Unten ausgeben <br>
                                    </td>
                                </tr>
                                <tr>
                                    <th>CSS-Einstellungen für die Ausgabe
                                    <p style="font-size:9pt;font-weight:normal">CSS-ID der Tabelle: #ovm_po_image_credits<br>
                                       CSS-Klasse Spalte Bild:  .ovm_po_credit_image<br>
                                       CSS-Klasse Spalte Text:  .ovm_po_credit_text
                                       CSS-ID des Divs der Beschreibung: #ovm_po_credits_info</p>
                                    </th>
                                    <td><textarea name="ovm_po_css" id="ovm_po_css"><?php echo ($ovm_po_css)?></textarea><p style="cursor:pointer;font-weight:bold"><input class="button button-primary" name="submit_restore_css" type="submit" value="Speichern und CSS-Standard-Einstellungen verwenden"/></td>
                                </tr>
                            </table>
                        </fieldset>
                        <?
                        break;

                    case OVM_PO_PREMIUM_TAB:
                        ?>
                        <fieldset><legend>Lizenzkey</legend>
                            <table>
                                <tr>
                                    <th style="white-space: nowrap">Bitte geben Sie Ihren Lizenzkey ein:</th>
                                    <td><input type="text" name="ovm_po_license" id="ovm_po_license"></td>
                                </tr>
                                <tr>
                                    <th style="white-space: nowrap"><a href="http://www.picture-organizer.com/lizenzkey" title="Hier klicken zum Anfordern eines Lizenzkeys" target="_blank">Lizenzkey anfordern<a></a></th>
                                    <td> </td>
                                </tr>

                            </table>
                        </fieldset>
                        <?
                        break;

                    case OVM_PO_SUPPORT_TAB:
                        $support_content = $this->get_uri(OVM_PO_SUPPORT_LINK,false);
                        echo($support_content);
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
    public function get_picture_credits($src= array())
    {global $wpdb;
        if(count($src)==0) { //Ausgabe für alle Bilder der Seite
            $args = array(
                'post_type' => 'attachment',
                'nopaging'=>true,
                'meta_query'=>array(
                    array(
                        'key' => 'ovm_picturedata_lizenz',
                        'compare' => '>',
                        'value' => '')
                )
            );
            $image_credits = get_posts($args);
        }
        else { //Spezielle Auswahl für die Bilder einer Seite
            for ($i=0; $i<count($src);$i++){
              $src[$i] = "'".$src[$i]."'";   //add ' to the strings for creatin the query
            }
            $guid_string = implode(",",$src);
            $image_credits = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE guid in ({$guid_string})");
        }


        $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);

        if ($promotion_ausgabe===false) {
            $this->plugin_init();
            $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
        }// Anlege
        $ovm_po_css = @$promotion_ausgabe['ovm_po_css'];  //check wether option is set

        if (!isset($ovm_po_css)) {
            eval('$ovm_po_css = "' . $this->ovm_get_template('default.css') . '";');    //default value if not yet defined
        }

        $h = "\n<style type=\"text/css\">\n{$ovm_po_css}\n</style>";

        $h.="\n<table id=\"ovm_po_image_credits\">\n"; //html zur Ausgabe erzeugen
        if (count($image_credits)>0) {
        foreach($image_credits as $credit)
        {
            $src_thumb=wp_get_attachment_thumb_url($credit->ID);
            $src_image = wp_get_attachment_url($credit->ID);
            $credit_lizenz = get_post_meta($credit->ID,OVM_PO_PICTUREDATA_LIZENZ);
            $credit_data = get_post_meta($credit->ID,OVM_PO_PICTUREDATA);
            if (!isset($credit_data[0]['bemerkung_online'])) $credit_data[0]['bemerkung_online']='';
            $h .="<tr>
                    <td class=\"ovm_po_credit_image\"><a alt=\"Anklicken für große Ansicht\" title=\"Anklicken für große Ansicht\" href=\"{$src_image}\" target=\"blank\"><img src=\"{$src_thumb}\"></a></td>
                    <td class=\"ovm_po_credit_text\"><a href=\"{$credit_data[0]['uri']}\">&copy; {$credit_data[0]['author']} {$credit_data[0]['portal']} {$credit_data[0]['kauf']}</a><br>{$credit_data[0]['bemerkung_online']}</td>
                  </tr>\n";
        }}
            else {
                $h .="<tr><td>Keine Daten zur Anzeige vorhanden</td></tr>";
            }
        $h.="</table>\n";//end table width image_credits

        $prom_ausgabe = "<div id=\"ovm_po_credits_info\">{$promotion_ausgabe['promotion_text']}</div>";

        switch ($promotion_ausgabe["promotion_position"]) {
          case 1: $h = $prom_ausgabe .$h;
                break;
            case 2:
                $h = $h . $prom_ausgabe;
                break;
        }
        return $h;   //Rückgabe der html-inhalte
    }


/*      show_lizenzinformationen($atts)
 *      Holt die Inhalte der Attachments zur Ausgabe über den Shortcode
 *      @since   1.1
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
        if (!isset($ovm_picturedata['show_on_page'])) {$ovm_picturedata['show_on_page']=0;}

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
        if ("1"==$this->ovm_po_premium) {
            $disabled = '';
            $helps = "Hier aktivieren, wenn der Urheberrechtsnachweis auch auf der Seite des Bildes angezeigt werden soll";
        }
        else{
            $disabled=' disabled="disabled"';
            $helps = "Nur in der Premium-Version verfügbar";
        }


        if ("1"==$ovm_picturedata['show_on_page']) {
            $ovm_picturedata_checked = ' checked = "checked"';
        }
              else
              {
            $ovm_picturedata_checked='';
        }

        $form_fields["show_on_page"] = array(
            "label" => __("Nachweis auf Bildseite zeigen"),
            "input" => "html",
            "value"=>$ovm_picturedata['show_on_page'],
            "html" =>"<input type=\"checkbox\" name=\"attachments[{$post->ID}][show_on_page]\" id=\"attachments[{$post->ID}][show_on_page]\" value=\"1\" {$ovm_picturedata_checked} {$disabled}/>",
            "helps" => $helps
        );



        return $form_fields;
    }

    public function add_image_attachment_fields_to_save($post, $attachment)
    {
        $ovm_picturedata = array();
        $ovm_picturedata['author'] = $attachment['author'];
        $ovm_picturedata['portal'] = $attachment['portal'];
        $ovm_picturedata['uri'] = $attachment['uri'];
        $ovm_picturedata['kauf'] = $attachment['kauf'];
        $ovm_picturedata['bemerkung']=$attachment['bemerkung'];
        $ovm_picturedata['bemerkung_online']=$attachment['bemerkung_online'];
        $ovm_picturedata['show_on_page']=(int)$attachment['show_on_page'];
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

    public function ovm_get_template($template, $cache = 1) // $cash: bei 1 ins templatecashe, bei 0 NICHT!
    {   global $templatecache, $ovmnl;
        if (! isset ( $templatecache [$template] )) {
            $filename = $this->plugins_path."tpl/".$template;
            if (file_exists($filename)) {
                $templatefile = str_replace ( "\"", "\\\"", implode ( file ( $filename ), '' ) );
            } else {
                $templatefile = '<!-- TEMPLATE NOT FOUND: ' . $filename . ' -->';
                die ( $template . " not found !" );
            }
            $templatefile = preg_replace ( "'<if ([^>]*?)>(.*?)</if>'si", "\".( (\\1) ? \"\\2\" : \"\").\"", $templatefile );
            $retval = $templatefile;
            if ($cache == 1) {
                $templatecache [$template] = $retval;
            }
        } else {
            $retval = $templatecache [$template];
        }
        return $retval;
    }





}//end class

$OVM_Picture_organizer = new OVM_Picture_organizer();

$OVM_Picture_organizer->ovm_po_premium=0;

