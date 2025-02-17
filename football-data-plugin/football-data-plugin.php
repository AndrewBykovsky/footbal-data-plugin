<?php
/*
Plugin Name: Footbal Data Plugin
Description: A plugin to show footbal data
Author: Andrew Bykovsky
*/

class FootbalDataPlugin
{
    const FDP_GET_DATA = 'fdp_get_league_data';
    const FDP_GET_LEAGUE_LIST = 'fdp_get_league_list';
    const TABLE_NAME = 'footbal_data_plugin';

    public function __construct()
	{
        //Init Actions
        add_action( 'wp_enqueue_scripts', array($this, 'fdp_enqueue') );
        //Ajax Handlers
        add_action('wp_ajax_' . self::FDP_GET_DATA, array( $this, self::FDP_GET_DATA ));
        add_action('wp_ajax_nopriv_' . self::FDP_GET_DATA, array( $this, self::FDP_GET_DATA ));

        add_action('wp_ajax_' . self::FDP_GET_LEAGUE_LIST, array( $this, self::FDP_GET_LEAGUE_LIST ));
        add_action('wp_ajax_nopriv_' . self::FDP_GET_LEAGUE_LIST, array( $this, self::FDP_GET_LEAGUE_LIST ));
        //Init plugin settings
        add_action( 'admin_menu', array($this, 'fdp_add_settings_page') );
        add_action( 'admin_init',  array($this, 'fdp_settings_fields') );
        //Add Shortcode
        add_shortcode( 'fdp-get-data', array($this, 'fdp_get_data') );
	}

    public function fdp_enqueue(){
        //enqueue script
        wp_register_script( 'fdp_js', plugins_url( '/assets/js/footbal_data_plugin.js', __FILE__ ), ['jquery'], '0.0.1', true );
        wp_localize_script( 'fdp_js', 'params', $this->fdp_ajax_js_data() );
        wp_enqueue_script( 'fdp_js' );
        //enqueue style
        wp_enqueue_style( "fdp_css", plugins_url( '/assets/css/footbal_data_plugin.css', __FILE__ ), [], '0.0.1' );
    }

    private function fdp_ajax_js_data(){
        return array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'ajax_nonce' => wp_create_nonce('check_nonce')
        );
    }

    public function fdp_add_settings_page(){
        add_options_page( 'FDP plugin page', 'FDP Plugin Info', 'manage_options', 'fdp-custom-settings-page', array($this, 'fdp_render_plugin_settings_page') );
    }

    public function fdp_render_plugin_settings_page(){ ?>
        <div class="wrap">
			<h1>Footbal data Plugin</h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'fdp_plugin_settings' );
					do_settings_sections( 'fdp-custom-settings-page' );
					submit_button();
				?>
			</form>
		</div>
    <?php 
    }

    public function fdp_settings_fields(){
        $page_slug = 'fdp-custom-settings-page';
	    $option_group = 'fdp_plugin_settings';

        add_settings_section( 'fdr_section_id', '', '', $page_slug);
        register_setting( $option_group, 'fdr_api_key', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => NULL ) );

        add_settings_field(
            'fdr_api_key',
            'API Key',
            array($this, 'fdp_api_key_field'),
            $page_slug,
            'fdr_section_id',
            array(
                'name' => 'fdr_api_key'
            )
        );
    }

    public function fdp_api_key_field(  $args  ){
        printf(
            '<input id="%s" name="%s" value="%s" />',
            $args[ 'name' ],
            $args[ 'name' ],
            get_option( $args[ 'name' ], '' )
        );
    }

    public function fdp_get_data(){
        $form_data = '<section class="fdp-data-form">';
        $form_data .= '<div class="fdp-loader fdp-loader--js"><p>Loading...</p></div>';
        $form_data .= '<h3>Footbal Plugin Data</h3>';
        $form_data .= '<div class="fdp-data-form__top">';
            $form_data .= '<div class="league-list--js"></div>';
            $form_data .= '<div class="dates">';
                $form_data .= '<div class="date-from-holder">';
                    $form_data .= '<p class="list-title">Date From:</p>';
                    $form_data .= '<input type="date" class="date-field--js" id="date-from" name="date-from" />';
                $form_data .= '</div>';
                $form_data .= '<div class="date-to-holder">';
                    $form_data .= '<p class="list-title">Date To:</p>';
                    $form_data .= '<input type="date" id="date-to" class="date-field--js" name="date-to" />';
                $form_data .= '</div>';
                $form_data .= '<div class="dates-clear dates-clear--js"><span>Clear Dates</span></div>';
            $form_data .= '</div>';
        $form_data .= '</div>';
        $form_data .= '<div class="fdp-data-form__bottom fdp-data-form__bottom--js"></div>';
        $form_data .= '</section>';

        return $form_data;
    }

    public function fdp_get_league_data(){
        check_ajax_referer('check_nonce', 'security');

        $data_html = '';
        $url_date_attr = '';

        if( isset( $_POST['code'] ) && $_POST['code'] !== '' ){
            $data_html .= '<div class="fdp-data-table--js">';

            if( isset( $_POST['dateFrom'] ) && $_POST['dateFrom'] !== '' ){
                $url_date_attr = '/?dateFrom=' . $_POST['dateFrom'];
            }

            $uri = 'http://api.football-data.org/v4/competitions/' . $_POST['code'] . '/matches?status=FINISHED';
            $reqPrefs['http']['method'] = 'GET';
            $reqPrefs['http']['header'] = 'X-Auth-Token: ' . get_option( 'fdr_api_key' );
            $stream_context = stream_context_create($reqPrefs);
            $response = file_get_contents($uri, false, $stream_context);
            $all_matches = json_decode($response);
            
            if( !empty( $all_matches ) ){
                foreach( $all_matches->matches as $match ){
                    
                    $match_date = date("Y-m-d", strtotime($match->utcDate));

                    if( isset( $_POST['dateFrom'] ) && $_POST['dateFrom'] !== '' ){
                        if( $match_date < $_POST['dateFrom'] ) continue;
                    }

                    if( isset( $_POST['dateTo'] ) && $_POST['dateTo'] !== '' ){
                        if( $match_date > $_POST['dateTo'] ) continue;
                    }

                    $data_html .= '<div class="results-holder">';
                        $data_html .= '<div class="results-holder-result">';

                            $data_html .= '<div class="home-team">';
                            $data_html .= '<img src="' . $match->homeTeam->crest . '" alt="' . $match->homeTeam->name . '">';
                            $data_html .= '<p>' . $match->homeTeam->name . '</p>';
                            $data_html .= '</div>';

                            $data_html .= '<div class="score">';
                            $data_html .= '<p>' . $match->score->fullTime->home . ' - ' . $match->score->fullTime->away . '</p>';
                            $data_html .= '</div>';

                            $data_html .= '<div class="away-team">';
                            $data_html .= '<img src="' . $match->awayTeam->crest . '" alt="' . $match->awayTeam->name . '">';
                            $data_html .= '<p>' . $match->awayTeam->name . '</p>';
                            $data_html .= '</div>';

                        $data_html .= '</div>';

                        $data_html .= '<div class="results-holder-date"><p class="date">' . $match_date . '</p></div>';
                    $data_html .= '</div>';
                    
                }
            }

            $data_html .= '</div>';

            wp_send_json(array( 'success' => true, 'data_html' => $data_html ));
        }

        wp_send_json(array( 'success' => false, 'msg' => 'Unnable to fetch data' ));
    }

    public function fdp_get_league_list(){
        check_ajax_referer('check_nonce', 'security');

        if( get_option( 'fdr_api_key' ) == '' ) wp_send_json(array( 'success' => false, 'msg' => 'API key is empty' ));

        $list_html = '';

        $uri = 'http://api.football-data.org/v4/competitions';
        $reqPrefs['http']['method'] = 'GET';
        $reqPrefs['http']['header'] = 'X-Auth-Token: ' . get_option( 'fdr_api_key' );
        $stream_context = stream_context_create($reqPrefs);
        $response = file_get_contents($uri, false, $stream_context);
        $matches = json_decode($response);

        if( !empty( $matches ) ){
            $list_html .= '<div class="league-list__holder">';
            $list_html .= '<p class="list-title">'. __( 'LEAGUE: ', 'fdp-plugin' ) .'</p>';

            $list_html .= '<select name="fdp-league">';
            $list_html .= '<option value="" disabled selected>-- Select League --</option>';
            foreach( $matches->competitions as $match ){
                if( $match->type == 'LEAGUE' ){
                    $list_html .= '<option value="'. $match->code .'">'. $match->name .'</option>';
                }
            }
            $list_html .= '</select>';
            $list_html .= '</div>';
        }else{
            wp_send_json(array( 'success' => false, 'msg' => 'Unable to fetch data or API key is invalid', 'matches' => $matches ));
        }

        wp_send_json(array( 'success' => true, 'list_html' => $list_html ));
    }
}

global $footbalDataPlugin;
$footbalDataPlugin = new FootbalDataPlugin();

?>