<?php
/**
 * Plugin Name: Contact Form 7 - Send Data to URL
 * Description: An add-on for Contact Form 7 that send the data form to URL you choose.
 * Version: 0.0.1
 * Author: Iran Alves
 * Author URI: https://www.makingpie.com.br
 * License: GPLv3
 */

 
/**
 * Verify CF7 dependencies.
 */
function cf7_sdtou_data_admin_notice() {
    // Verify that CF7 is active and updated to the required version (currently 3.9.0)
    if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
        $wpcf7_path = plugin_dir_path( dirname(__FILE__) ) . 'contact-form-7/wp-contact-form-7.php';
        $wpcf7_plugin_data = get_plugin_data( $wpcf7_path, false, false);
        $wpcf7_version = (int)preg_replace('/[.]/', '', $wpcf7_plugin_data['Version']);
        // CF7 drops the ending ".0" for new major releases (e.g. Version 4.0 instead of 4.0.0...which would make the above version "40")
        // We need to make sure this value has a digit in the 100s place.
        if ( $wpcf7_version < 100 ) {
            $wpcf7_version = $wpcf7_version * 10;
        }
        // If CF7 version is < 3.9.0
        if ( $wpcf7_version < 390 ) {
            echo '<div class="error"><p><strong>Warning: </strong>Contact Form 7 - Success Page Redirects requires that you have the latest version of Contact Form 7 installed. Please upgrade now.</p></div>';
        }
    }
    // If it's not installed and activated, throw an error
    else {
        echo '<div class="error"><p>Contact Form 7 is not activated. The Contact Form 7 Plugin must be installed and activated before you can use Success Page Redirects.</p></div>';
    }
}
add_action( 'admin_notices', 'cf7_sdtou_data_admin_notice' );

/* 
*   Carrega biblioteca de requisação Requests for PHP
    http://requests.ryanmccue.info/
*/
require_once  plugin_dir_path(dirname(__FILE__)) . 'contact-form-7-send-data-to-url/Requests.php';

/*
*  Filter data form and submit to URL  
*/
function cf7_sdtou_data($posted_data){

    //Ids de formulários aceitos
    $validForms = array('51');

    //Função executa somente em formulário de newsletter com ID 51
    if( !in_array($posted_data['_wpcf7'], $validForms)):
        return $posted_data;
    endif;

    Requests::register_autoloader(); //Carrega a biblioteca

    //Lista de informativos aceitos
    $base = array('Cota diária','Nota Mensal');

    //Verifica os dados enviados
    foreach( $posted_data as $key => $data  ):  
        //Se array e não tiver dados
        if( is_array($data) && count($data) <= 0 ):
            continue;
        endif;
        //Percorre array e adiciona valores
        if( is_array($data) ):
            foreach ($base as $k => $v) {
                $posted_data[$v] = (in_array($v, $data))? 1 : 0;
            }
        else:            
            $key = ( isset($data) )? $data : ""; 
        endif;        
    endforeach;

    //Token de Segurança 
    $token = "2B6D1831-95E5-4737-8900-24B100986AF4";

    //URL para requisição
    $url = "http://extranetquest.azurewebsites.net/_servicos/incluir-contato-newsletter.ashx";
    
    //Header de requisição
    $headers = array(
        'Accept' => 'application/json', 
        'Content-Type' => 'application/x-www-form-urlencoded');

    //Dados a enviar
    $query = array(
        'ts' => $token,
        'Idioma' => $posted_data['language'], 
        'nome' => $posted_data['name'], 
        'email' => $posted_data['email'], 
        'telefone' => $posted_data['telefone'], 
        'empresa' => $posted_data['business'], 
        'endereco' => $posted_data['address'], 
        'cidade' => $posted_data['city'], 
        'estado' => $posted_data['state'],
        'pais' => $posted_data['country'], 
        'cotadiaria' => $posted_data['Cota diária'],  
        'notamensal' => $posted_data['Nota Mensal']
    );

    //Converte a data no formato urlEncoded
    //$body = $body->Form($query);

    //Faz requisição e retorna dados do servidor cliente
    $response = Requests::post($url, $headers, $query);

    //Verifica se houve erro no registro e envia email ao administrador
    if( is_bool($response->body) && $response->body->Erro == true ){
        wp_mail(array(get_bloginfo('admin_email'), 'iran@prconsultingbrasil.com'),"Erro ao registrar dados de usuário no CRM da AZ QUEST", "Ao administrador, CRM AZ Quest apontando seguinte erro:" . (string) $response->body->Mensagem);
    }

    return $posted_data;

}
add_filter( 'wpcf7_posted_data', 'cf7_sdtou_data', 1 );




