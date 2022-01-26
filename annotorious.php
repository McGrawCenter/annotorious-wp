<?php
/*
	Plugin Name: Annotorious
	Plugin URI:
	Description: Annotorious allows users to add annotations to images directly on the webpage
	Version: 1.0
	Author: Ben Johnston
*/


class Annotorious {

	function __construct() {


	  add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' )  );
	  add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) ); 
	  add_action( 'add_meta_boxes', array( $this, 'annotorius_add_metabox' ) );
	  add_action( 'save_post', array( $this, 'annotorius_image_save' ), 10, 1 );
	  add_filter( 'the_content', array( $this , 'content_filter' ) );
	  
	  add_action( 'wp_ajax_anno_get', array( $this, 'anno_get' ) );	  
	  add_action( 'wp_ajax_anno_add', array( $this, 'anno_add' ) );
	  add_action( 'wp_ajax_anno_delete', array( $this, 'anno_delete' ) );
	  add_action( 'wp_ajax_anno_update', array( $this, 'anno_update' ) );
	  $this->filter_called = 0;
	}




	/*************************************
	* 
	*************************************/

	function load_scripts()
	{

	    wp_register_script( 'annotorius', plugins_url( 'js/annotorius/annotorious.min.js', __FILE__ ));
	    wp_enqueue_script( 'annotorius' );
	    wp_register_style( 'annotorius', plugin_dir_url( __FILE__ ) .  'js/annotorius/annotorious.min.css');
	    wp_enqueue_style( 'annotorius' );

	    wp_register_script('annotorius-js', plugins_url('js/script.js', __FILE__), array('jquery'),'1.1', true);
	    wp_enqueue_script('annotorius-js');
	    
	    //wp_register_script('annotorius-metabox', plugins_url('js/metabox.js', __FILE__), array('jquery'),'1.1', true);
	    //wp_enqueue_script('annotorius-metabox');	    
	    global $post;
	    $data = array('post_id' => $post->ID,'plugin_url' => plugin_dir_url( __FILE__ ),'ajax_url' => admin_url( 'admin-ajax.php' ));
	    wp_localize_script( 'annotorius-js', 'annotoriusvars', $data );  	     

	}
	
	
	function load_admin_scripts() {
	    wp_register_script('admin-js', plugins_url('js/admin.js', __FILE__), array('jquery'),'1.1', true);
	    wp_enqueue_script('admin-js'); 
	}

	 
	 
	function content_filter($content) {
	  global $post;

	  if ($annotorius_img_id = get_post_meta($post->ID, '_annotorius_image_id', true)) {
	    $img = wp_get_attachment_url( $annotorius_img_id );
	    $html = "<img id='annotorius' src='{$img}' />";
	    return $html.$content;
	  }
	  else {return $content; }
	}
	 	
	
	
	
	
	
	/*****************************
	* Render the metabox
	*****************************/

	function annotorius_add_metabox () {
		add_meta_box( 'annotoriusimagediv', __( 'Annotated Image', 'text-domain' ), array( $this, 'annotorius_metabox'), array('post','page'), 'side', 'low');
	}
	
	
	function annotorius_metabox ( $post ) {

		global $content_width, $_wp_additional_image_sizes;

		$image_id = get_post_meta( $post->ID, '_annotorius_image_id', true );

		$old_content_width = $content_width;
		$content_width = 254;

		if ( $image_id && get_post( $image_id ) ) {

			if ( ! isset( $_wp_additional_image_sizes['post-thumbnail'] ) ) {
				$thumbnail_html = wp_get_attachment_image( $image_id, array( $content_width, $content_width ) );
			} else {
				$thumbnail_html = wp_get_attachment_image( $image_id, 'post-thumbnail' );
			}

			if ( ! empty( $thumbnail_html ) ) {
				$content = $thumbnail_html;
				$content .= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_listing_image_button" >' . esc_html__( 'Remove listing image', 'text-domain' ) . '</a></p>';
				$content .= '<input type="hidden" id="upload_listing_image" name="_annotorius_image" value="' . esc_attr( $image_id ) . '" />';
			}

			$content_width = $old_content_width;
		} else {

			$content = '<img src="" style="width:' . esc_attr( $content_width ) . 'px;height:auto;border:0;display:none;" />';
			$content .= '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set annotated image', 'text-domain' ) . '" href="javascript:;" id="upload_listing_image_button" id="set-listing-image" data-uploader_title="' . esc_attr__( 'Choose an image', 'text-domain' ) . '" data-uploader_button_text="' . esc_attr__( 'Set annotated image', 'text-domain' ) . '">' . esc_html__( 'Set annotated image', 'text-domain' ) . '</a></p>';
			$content .= '<input type="hidden" id="upload_listing_image" name="_annotorius_image" value="" />';

		}

		echo $content;
	}
		
	
	
	
	
	
	/*****************************
	* Save the annotated image metabox
	*****************************/

	function annotorius_image_save ( $post_id ) {
	
	
		if( isset( $_POST['_annotorius_image'] ) ) {

			$image_id = (int) $_POST['_annotorius_image'];
			update_post_meta( $post_id, '_annotorius_image_id', $image_id );

		}	
	}
	
	
		
	/******************* AJAX *******************/
	
	
	/*************************************
	* ajax get annotations
	*************************************/
	function anno_get() {
		$post_id = $_GET['post_id'];
		header('Content-Type: application/json');
		if(!$meta = get_post_meta($post_id, 'annotorius', true)){
		    $meta = json_encode(array());
		 }
		echo $meta;
		wp_die();
	}

	/*************************************
	* ajax add annotation
	*************************************/
	function anno_add() {
		$post_id = $_GET['post_id'];

		if(!$meta = json_decode(get_post_meta($post_id, 'annotorius', true))){
		    $meta = array();
		 }
		
		$_GET['annotation']['body'][0]['value'] = str_replace("\'","'",$_GET['annotation']['body'][0]['value']);
		$meta[] = $_GET['annotation'];
		$id = update_post_meta( $post_id , 'annotorius', json_encode($meta));

		echo json_encode(array('success'=>$id));
		wp_die();
	}

	/************************************
	* ajax delete annotation
	************************************/
	function anno_delete() {
	
		$post_id = $_GET['post_id'];
		$annoid = $_GET['annotationid'];

		$meta = json_decode(get_post_meta($post_id, 'annotorius', true));

		foreach($meta as $i=>$m) {
		  if($m->id==$annoid) {
		    unset($meta[$i]);
		    update_post_meta( $post_id , 'annotorius', json_encode($meta));
		  }
		}

		wp_die();
	}
	

	/************************************
	* ajax update annotation
	************************************/
	function anno_update() {
	
		$post_id = $_GET['post_id'];
		$annoid = $_GET['annotationid'];

		$meta = json_decode(get_post_meta($post_id, 'annotorius', true));

		foreach($meta as $i=>$m) {
		  if($m->id==$annoid) {
		    $meta[$i] = $_GET['annotation'];
		    update_post_meta( $post_id , 'annotorius', json_encode($meta));
		  }
		}

		wp_die();
	}	
			
		
		
		


} // end Class
new Annotorious();

