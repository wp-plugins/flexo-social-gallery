<?php /*
Plugin Name: flexo-social-gallery
Contributors: flexostudio
Tags:gallery, nggallery, pics, images
Author: Grigor Grigorov, Mariela Stefanova, Flexo Studio Team
Plugin URI: http://www.flexostudio.com/wordpress-plugins-flexo-utils.html
Description:
Version: 1.0003 
Stable tag:1.0003
Requires at least:3.0
Tested up to: 3.0
*/

/* =spisyk s snimki ot izbranata galeriq
-------------------------------------------------------------------*/
class FlexoSocialGallery {
	
	const key_img	=	"sgimage";
	
	public static function ngg_get_img($id){
		global $wpdb;
		global $dirresult;
		global $homeurl;
		global $permalink;
		
		$query = "SELECT p.*,g.path FROM " . $wpdb->prefix . "ngg_pictures as p
		left join (".$wpdb->prefix . "ngg_gallery as g) on (p.galleryid = g.gid)
		WHERE pid=".$id;
		$results = $wpdb->get_results($query);
		
		if(count($results) > 0 ){
			$image	=	$results[0];
		}else{
			return false;
		}
		return $image;		
	}
// zaqvka kym NextGen pri 'false' vry6ta dannite ot galeriqta, pti 'true' masiv sys snimki
	public static	function nggGetPics($chosen_gallery, $check='true') {
		global $wpdb;
		global $dirresult;
		global $homeurl;
		global $permalink;
		global $gallery_anchor;
	
		$gid	=	$chosen_gallery['GalleryID'];
		$gallery_anchor	=	'#g'.$gid;
		//$query = "SELECT * FROM " . $wpdb->prefix . "ngg_pictures WHERE galleryid = " . $gid;
		//$results = $wpdb->get_results($query);
		$query = "SELECT path FROM " . $wpdb->prefix . "ngg_gallery WHERE gid = " . $gid;
		$dirresult = $wpdb->get_results($query);
		$permalink = get_permalink();
		$pics_html = '';
		
		$g=nggdb::get_gallery($gid);

		
				foreach ($g as $gal) {
					$content	=	'<img src="'.$gal->thumbURL.'" />';
					$pics_html.=self::format_img($permalink,$gal->pid,$gal->alttext,$content,$gallery_anchor);

				}
				$i=0;
				foreach ($g as $gal) {
					$new_result[$i]=$gal;
					$i++;
				}
		
		foreach ($new_result as $key => $result) {
			$content	=	'<img src="'.$result->thumbURL.'" />';
			$url			=	self::format_img($permalink,$result->pid,$result->alttext,$content,$gallery_anchor);
			//$pics_html 								.=  $url;
			$result->url							=		$url;
			$new_result[$key]					=		$result;
		}
//echo $pics_html;
		if ($check=='true')
				return '<div class="clear"></div><div class="all_gallery">'.$pics_html.'</div><div class="clear"></div>';
		else
				return $new_result;
	}

	public static function format_img($permalink,$pid,$title,$content,$anchor=false){
		//return '<a href="'.self::img_url($permalink,$pid,$anchor ).'" title="'.$title.'" >'.$content.'</a>';
		return '<a href="'.self::img_url($permalink,$pid,$anchor ).'" title="'.$title.'" >'.$content.'</a>';
	}

	public static function img_url($permalink,$pid,$anchor=false){
		return $permalink.'?'.self::key_img.'='.$pid.$anchor  ;
	}

// proverqva content-a za nali4ie na galeriq i ako ima q gobavq
	public static function filter($content) {
		global $permalink;
		global $gallery_anchor;
		$ret 			=	"";
		$pattern	=	"[flexogallery ";
		$spos 		=	0;
		$epos			=	-1;

		while(($spos = strpos($content,$pattern,++$epos)) > -1):
	
			if(isset($_GET[self::key_img])){
				$is_image	=	true;
				$pid			=	intval($_GET[self::key_img]);
			}else{
				$is_image	=	false;
				$pid			=	0;
			}
		
			$last			=	$epos;
			$epos			=	strpos($content,"]",$spos);
			if($epos != -1){
				$offset		=	strpos($content," ",$spos);
				$settings	=	substr($content,$offset,$epos - $offset);

				$ret   .= substr($content,$last,$spos-$last);
				if(preg_match_all('/(?<key>[^=]+)=[\'"](?<val>[^\'"]+)[\'"]/i',$settings,$m)){
					$params		=	array();	

					foreach($m['key'] as $key => $val):
						$_key	=	trim($m['key'][$key]);
						$params[$_key] = trim($m['val'][$key]);
					endforeach;
		
				if(!$is_image) $ret .= self::nggGetPics($params);
				}
			}
		endwhile;	
	
		if($is_image){
				$arg = self::nggGetPics($params,'false');
				$gallery =	self::show_part_gallery($pid,$params);
				$image   = self::format_image($pid,$arg);	
				if(class_exists('flexoFBManager')){
					$url			=	self::img_url($permalink,$pid);
					$fb 		=	flexoFBManager::get_form($pid,'default',$url);
					
				}
			
				if ($params['nav']=='up'){
					$ret .= $gallery.$image.$fb;
				}
				if ($params['nav']=='down'){
					$ret .= $image.$gallery.$fb;
				}
				if ($params['nav']=='without'){
					$ret .= $image.$fb;
				}
		}

		$ret .= substr($content,$epos);
		return $ret;
	}

// izkarva snimkata koqto e izbrana s vryzki za predi6na i sledva6ta
	public static function format_image($pid,$arg) {
		global $dirresult;
		global $gallery_anchor;
		$razmer = count($arg);
	
		for ($i=0; $i < $razmer; $i++) {
			if($pid == $arg[$i]-> pid) {
				$nom=$i;
			
				$br = $nom == 0 ? $razmer : $nom;

				$ret.='<div id="pic-preview">';
				
				$ret.='<a class="prev" href="'.$permalink.'?'.self::key_img.'='.$arg[$br-1]->pid.$gallery_anchor.'"><div id="prev"></div> </a>';
				
				$br	=	$nom == $razmer-1	? -1 : $nom;
		
			// dobavq funkcionalnost na strelkite ot klaviaturata
			?>
				<script>
					
					document.onkeyup = KeyCheck;       
					function KeyCheck(e) {
						   var KeyID = (window.event) ? event.keyCode : e.keyCode;
						   switch(KeyID){
						      case 39:
							      window.location = '<?php echo $permalink.'?'.self::key_img.'='.$arg[$br+1]->pid.$gallery_anchor;?>';
							      break;
				 
						      case 37:
							      if ( <?php echo $br = $nom == 0 ? $razmer : $nom;?>) {
								      window.location = '<?php echo $permalink.'?'.self::key_img.'='.$arg[$br-1]->pid.$gallery_anchor;?>';
								      break;
							    	}
						   }
					}
				</script>
			<?php
				$br	=	$nom == $razmer-1	? -1 : $nom;
				
				$ret.= 	'<div id="pic"><a href="'.$permalink.'?'.self::key_img.'='.$arg[$br+1]->pid.$gallery_anchor .'"><img class="social-gallery" src="'.$dirresult[0]->path.'/'.$arg[$nom]->filename .'" height='.$arg[$nom]->meta_data['height'].'px'.' width='.$arg[$nom]->meta_data['width'].'px'.'></a></div>';
				
				$ret.=	'<a class="next" href="'.$permalink.'?'.self::key_img.'='.$arg[$br+1]->pid.$gallery_anchor .'"><div id="next"></div></a>';
				
				$ret.=	'</div>';
				return $ret;
			}		
		}
	}
	
// izkarva 5-te snimki za navigaciq
	public static function show_part_gallery($pid,$params) {
		global $permalink;
		global $dirresult;
		global $gallery_anchor;
		global $gallery_anchor;

		$link_name	=	str_replace("#","",$gallery_anchor);
		$images = self::nggGetPics($params,'false');
		$razmer = count($images);
		$ret = '<div><a href='.$permalink.'> обратно в галерия </а></div>';
		$ret .='<div id="part_g">';
		$ret .= "<a name='".$link_name."'></a>";
	

	for ($i=0; $i < $razmer; $i++) {
		if ($images[$i]->pid == $pid){
			
			if($i >= $razmer-3){
				for($m=$razmer-5;$m < $razmer; $m++){
					if ($images[$m]->pid==$pid){
					$ret.= '<a href="'.self::img_url($permalink,$pid,$gallery_anchor).'"class="select_pic"><img src="'.$images[$m]->thumbURL.'" /></a>';
					}
					else{
						$ret.= $images[$m]->url;
					}
				}
			}
			if($i < $razmer-3) {
				for ($j = $i-2; $j < $i+3; $j++ )
				{
					if ($j == -2){
							
							for($m = 0;$m < 5; $m++) {
								//$ret.= $images[$m]->url;
								
								if ($images[$m]->pid==$pid){
									$ret.= '<a href="'.self::img_url($permalink,$pid,$gallery_anchor).'"class="select_pic"><img  src="'.$images[$m]->thumbURL.'" /></a>';
								}
								else{
									$ret.= $images[$m]->url;
								}
							}
							 break;
					}
					if ($j == -1){
					
						for($m = 0;$m < 5; $m++){
						//	$ret.= $images[$m]->url;
							
							if ($images[$m]->pid==$pid){
								$ret.= '<a href="'.self::img_url($permalink,$pid,$gallery_anchor).'"class="select_pic"><img  src="'.$images[$m]->thumbURL.'" /></a>';
							}
							else{
								$ret.= $images[$m]->url;
							}				
						}
						 break;
					}
					if ($j >= 0 && $j < $razmer ){ 
							if ($images[$j]->pid==$pid){
							$ret.= '<a href="'.self::img_url($permalink,$pid,$gallery_anchor).'" class="select_pic"><img src="'.$images[$j]->thumbURL.'" /></a>';
							}
							else{
							$ret.= $images[$j]->url;
							}
					}
				else{
						$ret.= "";
					}
				
				}	
			}
		}
			
	}
		$ret.= '</p>';
		$ret.= '</div>';
		return $ret;
	}

	public static function CSSInit() {
		$path = 	 plugins_url( 'stylefsg.css' , __FILE__ );
		wp_enqueue_style('StyleSheet', $path);
	}

	public static function init(){
		self::CSSInit();
		//self::open_graph_check();
	}
	
	public static function open_graph_check(){
		if(isset($_GET[self::key_img])){
			$id	=	intval($_GET[self::key_img]);
			$img	=	self::ngg_get_img($id);
			$perm	='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$iurl	=	'http://'.$_SERVER['HTTP_HOST']."/".$img->path."/".$img->filename;
			echo '<link rel="canonical" href="'.$perm.'"/>'."\n";
			remove_action('wp_head','rel_canonical');
			//print_r($_SERVER);
			//echo $perm;exit;
			?>
		<meta property="og:title" content="Gallery <?php echo $img->galleryid; ?> Image <?php echo $img->image_slug; ?>"/>
    <meta property="og:description" content="<?php echo $img->description; ?>"/>			
    <meta property="og:locale" content="<?php echo self::parse_locale(get_bloginfo('language')); ?>" />
    <meta property="og:type" content="blog"/>
    <meta property="og:url" content="<?php echo $perm; ?>"/>
    <meta property="og:image" content="<?php echo $iurl; ?>"/>
    <meta property="og:site_name" content="<?php bloginfo('name'); ?>"/>
    <meta property="fb:app_id" content="<?php echo flexoFBManager::fb_id(); ?>"/>
    <?php /*<meta property="fb:admins" content="1423356739"/> */ ?>
			<?php
		}
	}
	
	public static function parse_locale($_lang){
		//$lang	=	"en_US";
		$lang = "bg_BG";
		
		return $lang;
	}
		
}//class
if(function_exists('add_action')):
	if (!is_admin()  ) {
		add_filter('the_content','FlexoSocialGallery::filter');
		add_action('init', 'FlexoSocialGallery::init');
		add_action('wp_head','FlexoSocialGallery::open_graph_check',1);
	}
endif; 
?>