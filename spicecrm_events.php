<?php

/*
 Plugin Name: SpiceCRM-Events
 Description: Events von SpiceCrm darstellen und buchen.
 Version: 0.1
 Author: 20reasons Solutions
 Plugin URI: https://www.spicecrm.com/
 License: GPLv2
 */


//security check - not allowing to address php directly from frontend
if( ! defined('ABSPATH' ) ) exit;

//defining class
class SpiceCRMEventsPlugin
{
    //constructor function - calling class functions on actions and setting filters
    function __construct() {
        add_action('admin_menu', array($this, 'myEventsSettingsPage'));
        add_action('wp_enqueue_scripts', array($this, 'my_custom_enqueue_scripts'), 10);
        add_action('init', array($this, 'insert_detail_page'));
        add_action('init', array($this, 'insert_booking_page'));
        add_action('init', array($this, 'start_session'));
    }

    //enqueueing all necessary script and style files
    function my_custom_enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('spicecrm_search', plugin_dir_url(__FILE__).'spicecrm_search.js');
        wp_enqueue_style('bootstrap-css', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
    }

    //start session
    function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    //creates the main and sub menu(s) SpiceCRM in the admin area of WP
    function myEventsSettingsPage() {
        $mainPageHook = add_menu_page('Settings I','SpiceCRM', 'manage_options', 'myevents-settings', array($this, 'myEventsSettingsHTML'), 'dashicons-calendar', 25);
        add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
    }

    //creates the content of SpiceCRM admin area
    public function myEventsSettingsHTML() { ?>
        <div class="wrap">
            <h1>SpiceCRM Events-Manager</h1>
            <p><b>Dear User,</b></p>
            <p>To use the plugin properly, please fill out the form beneath, containing the URL and credentials you obtained from your SpiceCRM account manager, as well as the title of the two pages that will be created in your dashboard. The first one will be for the detail page of one specific event, and the second one will be the booking page of the selected event. <br> Afterwards you can implement the list of your events anywhere in your website by simply using the shortcode [eventlist]!</p>
            <?php if (isset ($_POST["transferred"]) && $_POST["transferred"] == 'true') $this->handleForm();?>
            <form name="form" id="credentials"  method="POST">
                <input type="hidden" name="transferred" id="transferred" value="true">
                <?php wp_nonce_field('saveChanges', 'ourNonce') ?>

                <label for="url" class="form-control">URL</label><br>
                <input type="text" name="url" id="url" class="form-control" required="required" value="<?php echo get_option('url') ?>"><br>

                <label for="user" class="form-control">Username</label><br>
                <input type="text" name="user" id="user" class="form-control" required="required" value="<?php echo get_option('user') ?>"><br>

                <label for="password" class="form-control">Password</label><br>
                <input type="password" name="password" id="password" class="form-control" required="required" value="<?php echo get_option('password') ?>"><br>

                <label for="detailpage" class="form-control">Titel detail page</label><br>
                <input type="text" name="detailpage" id="detailpage" class="form-control" required="required" value="<?php echo get_option('detailpage') ?>"><br>

                <label for="bookingpage" class="form-control">Titel booking page</label><br>
                <input type="text" name="bookingpage" id="bookingpage" class="form-control" required="required" value="<?php echo get_option('bookingpage') ?>"><br><br>

                <input type="submit" name="submit" id="submit" class="button button-primary" value="Send Data">

            </form>

        </div>
    <?php }

    //enqueue styles.css for SpiceCRM admin area
    function mainPageAssets(){
        wp_enqueue_style('styleAdminCss', plugin_dir_url(__FILE__).'styles.css');
    }

    //shows confirmation or error after saving credentials in admin area
    public function handleForm(){
        if (wp_verify_nonce($_POST['ourNonce'], 'saveChanges') AND current_user_can('manage_options')) {
            update_option('url', sanitize_text_field($_POST['url']));
            update_option('user', sanitize_text_field($_POST['user']));
            update_option('password', sanitize_text_field($_POST['password']));
            update_option('detailpage', sanitize_text_field($_POST['detailpage']));
            update_option('bookingpage', sanitize_text_field($_POST['bookingpage']));
            ?>
            <div class="updated">
                <p>You're changes were saved!</p>
            </div>

        <?php } else { ?>
            <div class="error">
                <p>Oooops, it seams there was a problem!</p>
            </div>
        <?php }
    }

    //creates the detail page, if not existing yet (otherwise update)
    function insert_detail_page() {
         //check if detail-slug already exists
         $detail_exists = get_page_by_path( 'detail', OBJECT, 'page' );
         //define content of details page
         $content = '[eventdetails]';

         //insert or update the booking page
         if ( $detail_exists == NULL ) {
             $my_post = array(
                 'post_title'    => get_option('detailpage'),
                 'post_content'  => $content,
                 'post_status'   => 'publish',
                 'post_author'   => 1,
                 'post_category' => array(1),
                 'post_type'     => 'page',
                 'post_name'     => 'detail',
             );
             //temporarily disable WP predefined content filters
             remove_filter('content_save_pre', 'wp_filter_post_kses');
             remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
             wp_insert_post($my_post);
             //enable filters again
             add_filter('content_save_pre', 'wp_filter_post_kses');
             add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
         } else {
             $my_post = array(
                 'ID'           => $detail_exists->ID,
                 'post_title'   => get_option('detailpage'),
                 'post_content' => $content,
             );
             //temporarily disable WP predefined content filters
             remove_filter('content_save_pre', 'wp_filter_post_kses');
             remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
             wp_update_post($my_post);
             //enable filters again
             add_filter('content_save_pre', 'wp_filter_post_kses');
             add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
         }
    }

    //creates the booking page, if not existing yet (otherwise update)
    function insert_booking_page() {
        //check if booking-slug already exists
        $booking_exists = get_page_by_path( 'booking', OBJECT, 'page' );
        //define content of booking page - including content of booking_form.php for html-content
        $content = '[eventbooking]';

        //insert or update the booking page
        if ( $booking_exists == NULL ) {
            $my_post = array(
                'post_title'    => get_option('bookingpage'),
                'post_content'  => $content,
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_category' => array(1),
                'post_type'     => 'page',
                'post_name'     => 'booking',
            );
            //temporarily disable WP predefined content filters
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
            wp_insert_post($my_post);
            //enable filters again
            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        } else {
            $my_post = array(
                'ID'           => $booking_exists->ID,
                'post_title'   => get_option('bookingpage'),
                'post_content' => $content,
            );
            //temporarily disable WP predefined content filters
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
            wp_update_post($my_post);
            //enable filters again
            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        }
    }

} //---END OF CLASS

//open new instance of class MyEventsPlugin
$spiceCRMEventsPlugin = new SpiceCRMEventsPlugin();

//calls data from API, extracts array with events and lists them in html-table
function loadeventlist() {
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(get_option('user') . ':' . get_option('password'))
        )
    );
    //concat url for HTTP-request
    $url = get_option('url') . '/api/module/Events';
    //GET request from API
    $request = wp_remote_get($url, $args);
    //extracting the body from the requested data
    $jsonfile = wp_remote_retrieve_body($request);
    //turning request body from string to array
    $event_array = json_decode($jsonfile, true);
    //variable to count all events - addressing subarray totalcount (where the number of total events is noted)
    $_SESSION['y_of'] = $event_array["totalcount"];

    if (isset($_GET['currentpage']) && is_numeric($_GET['currentpage'])) {
        // cast var as int
        $currentpage = (int) $_GET['currentpage'];
    } else {
        // default page num
        $currentpage = 1;
    } // end if

//div-wrapper for table

?>
    <style>
        label {
            display: inline-block;
            width: 100%;
            text-align: right;
            line-height: 30px;
        }
        .form-control {
            font-family: Arial, sans-serif;
            font-size: 1rem;
        }

    </style>

    <div class="row">

        <div>
            <input id="search" type="text" class="form-control" placeholder="&#128269  Search events" <?php
             if (isset($_GET['searchterm'])) {
                 echo "value =".$_GET['searchterm'];
             }
            ?>>
        </div>
        <div>
            <input id="currpage" type="hidden" type="text" value="<?php echo $currentpage; ?> ">
        </div>
    </div>

    <div id="output" style="overflow-x:auto;">
            <!-- content from ajax -->
    </div>
<?php
}//---END OF FUNCTION loadeventlist

//defines shortcode
$myShortcode = 'eventlist';

//adds shortcode and calls the list
add_shortcode($myShortcode, 'loadeventlist');

//----------------------------------------------------

function loadeventdetails() {
    //getting id from url
    $id = $_GET['id'];
    //including API connection data
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(get_option('user') . ':' . get_option('password'))
        )
    );
    //concat url for HTTP-request
    $url = get_option('url') . '/api/module/Events/'.$id;
    //GET request from API
    $request = wp_remote_get($url, $args);
    //extracting the body from the requested data
    $jsonfile = wp_remote_retrieve_body($request);
    //turning request body from string to array
    $events = json_decode($jsonfile, true);
    //getting event details from array
    $title     = $events['name'];
    $free_lots = $events['capacity_participants'];
    $start     = $events['date_start'];
    $end       = $events['date_end'];
    $details   = $events['description'];
    //variable for booking-link
    $booking_url = get_permalink(get_page_by_path('booking')).'?id='.$id ;

//template structure in html, content in php
    ?>
    <style>
        input.right {
            float: right;
        }
        input.left {
            float: left;
        }
        div.entry-content {
            max-width: 95%;
            margin: 0 10%;
            padding: 0 60px;
        }
        div.description {
            padding: 20px 0;
        }
    </style>
    <div class="entry-content">
        <h2><?php echo $title ?></h2>
        <p><small><b>ID: <?php echo $id?></b> - (noch <?php echo $free_lots?> Plätze frei)</small></p>
        <div class="description">
            <?php echo $details ?>
        </div>
        <div>
            <b>Beginn: </b><?php echo date('d.m.Y, G:i',strtotime($start)); ?> Uhr<br>
            <b>Ende: </b><?php echo date('d.m.Y, G:i',strtotime($end)); ?> Uhr
        </div>
        <div>
            <input type="submit" value="Zurück" class="left" onclick="history.back()">
            <a href="<?php echo $booking_url ?>"><input type="submit" value="Buchen" class="right"></a>
        </div>
    </div>
    <?php
}//---END OF FUNCTION loadeventdetails

//defines shortcode
$myShortcode2 = 'eventdetails';

//adds shortcode and calls the list
add_shortcode($myShortcode2, 'loadeventdetails');

//----------------------------------------------------

function loadeventbooking() {
    //getting id from url
    $id = $_GET['id'];
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(get_option('user') . ':' . get_option('password'))
        )
    );
    //concat url for HTTP-request
    $url = get_option('url') . '/api/module/Events/'.$id;
    //GET request from API
    $request = wp_remote_get($url, $args);
    //extracting the body from the requested data
    $jsonfile = wp_remote_retrieve_body($request);
    //turning request body from string to array
    $events = json_decode($jsonfile, true);
    //getting event title from array
    $title = $events['name'];


    //recaptcha validation
    //localhost keys
    $public_key = "6LdTCD4cAAAAAHF3CbFkNoV6zVxnNBGC5sLgpd2z";
    $private_key = "6LdTCD4cAAAAAHUxL5P9QWeW93gcnXo5on30j-UY";

    //stage.twentyreasons.com keys
    //$public_key = "6LfnBz4cAAAAAEK4s4tCkzmv4p4WQkxNEbcGgXNj";
    //$private_key = "6LfnBz4cAAAAAEwg29TeSK9Aq5m1ij4J0NMKZYE1";
    $captcha_url = "https://www.google.com/recaptcha/api/siteverify";

    if(array_key_exists('booking', $_POST)){
       // echo "<pre>";print_r($_POST);echo"</pre>";
        $response_key = $_POST['g-recaptcha-response'];
        $response = file_get_contents($captcha_url.'?secret='.$private_key.'&response='.$response_key);
        $response = json_decode($response);
       // echo "<pre>";print_r($response);echo"</pre>";

       /* if($response -> success == 1){
            echo "<script type='text/javascript'>alert('Form successfully submited! ')</script>";
        }else{
            echo "<script type='text/javascript'>alert('reCaptcha not checked!')</script>";
        }*/
    }

    ?>

    <style>
        .form-control {
            font-family: Arial, sans-serif;
            font-size: 1rem;
        }
        .small-label {
            font-size: 1rem;
        }
        input[type="date"].form-control {
            line-height: 100%;
        }
        div.entry-content {
            max-width: 90%;
            margin: 0 10%;
            padding: 0 60px;
        }
        input.right {
            float: right;
        }
        #g-recaptcha-response {
            display: block !important;
            position: absolute;
            margin: -78px 0 0 0 !important;
            width: 302px !important;
            height: 76px !important;
            z-index: -999999;
            opacity: 0;
        }
    </style>
    <script>
        window.onload = function() {
            var $recaptcha = document.querySelector('#g-recaptcha-response');

            if($recaptcha) {
                $recaptcha.setAttribute("required", "required");
            }
            const $form = document.querySelector('form');
            $form.addEventListener('submit', (event) => {
                event.preventDefault();
                console.log('prevented submit by code for demo');
            });
        };
    </script>

    <div class="entry-content">
        <h2><?php echo $title ?></h2>
        <p><small><b>ID: <?php echo $id?></b></small></p>
        <br>
        <form method="post">
            <div class="row">
                <div class="col-md-6 col-xs-6">
                    <label for="title" >Anrede *</label>
                    <select id="title" class="form-control" name="title" required="required" >
                        <option disabled selected value hidden>Anrede</option>
                        <option value="m">Herr</option>
                        <option value="f">Frau</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="first_name">Vorname *</label> <input id="first_name" type="text" class="form-control" name="first_name" placeholder="Vorname" required="required">
                </div>
                <div class="col-md-6">
                    <label for="family_name" >Familienname *</label> <input id="family_name" type="text" class="form-control" name="family_name" placeholder="Familienname" required="required">
                </div>
            </div>
            <div id="gender" class="row">
                <div class="col-md-12">
                    <label for="gender" >Geschlecht *</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-3">
                    <input  id="m" class="form-check-input" type="radio" name="gender" value="m" required="required"> <label for="m" class="small-label" >männlich</label>
                </div>
                <div class="col-md-3 col-sm-3">
                    <input id="f" class="form-check-input" type="radio" name="gender" value="f"> <label for="f" class="small-label">weiblich</label>
                </div>
                <div class="col-md-4 col-sm-6">
                    <input id="n" class="form-check-input" type="radio" name="gender" value="n"> <label for="n" class="small-label">neutral/ohne/divers</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-4 col-xs-6">
                    <label for="birth_date" >Geburtsdatum</label> <input id="birth_date" type="date" class="form-control" name="birth_date" value="">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="phone" >Telefon/Mobil *</label> <input id="phone" type="text" class="form-control" name="phone" placeholder="Telefon-/Mobilnummer" required="required">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="email" >E-Mail *</label> <input id="email" type="email" class="form-control" name="email" placeholder="E-Mail-Adresse" required="required">
                </div>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <label for="address" >Straße/Hnr. *</label> <input id="address" type="text" class="form-control" name="address" placeholder="Straße &amp; Hausnummer" required="required">
                </div>
                <div class="col-md-3 col-xs-5">
                    <label for="postal_code" >PLZ *</label> <input id="postal_code" type="text" class="form-control" name="postal_code" placeholder="PLZ" required="required">
                </div>
                <div class="col-md-4 col-xs-7">
                    <label for="place" >Ort *</label> <input id="place" type="text" class="form-control" name="place" placeholder="Ort" required="required">
                </div>
            </div>
            <br>
            <div class="row">
                <div class="form-check">
                    <label for="data_protection" class="small-label form-check-label"><input id="data_protection" class="form-check-input" type="checkbox" name="data_protection" required="required" value="1"> Ich habe die<a href="#"> Datenschutzrichtlinie </a>gelesen und bin damit einverstanden.</label>
                </div>
            </div>
            <div class="row">
                <div class="form-check">
                    <label for="terms" class="small-label form-check-label"><input id="terms" class="form-check-input" type="checkbox" name="terms" required="required" value="1"> Ich habe die <a href="#">AGBs </a>gelesen und bin damit einverstanden.</label>
                </div>
            </div>
            <small>Felder markiert mit * sind Pflichtfelder.</small>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php print $public_key; ?>"></div><br>
                <input type="submit" class="left" name="booking" value="Jetzt buchen">
            </div>

        </form>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php
}//---END OF FUNCTION loadeventdetails

//defines shortcode
$myShortcode3 = 'eventbooking';

//adds shortcode and calls the list
add_shortcode($myShortcode3, 'loadeventbooking');


