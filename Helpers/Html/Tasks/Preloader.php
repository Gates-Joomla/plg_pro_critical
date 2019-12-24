<?php
	namespace Plg\Pro_critical\Html\Tasks;
	
	use Plg\Pro_critical\Html\Libraries\LazyLoad;
	use Plg\Pro_critical\Helpers\Assets\Links;
	
	class Preloader
	{
		
		/**
		 * @param $task
		 *
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function aplly ( $task )
		{
			
			
			$preload = new \stdClass();
			$preload->src = $task['link'] ;
			
			switch($task['link_type']){
				case 1 : $type = 'prefetch' ; break ;
				case 2 : $type = 'preconnect' ; break ;
				case 3 : $type = 'dns-prefetch' ; break ;
				case 4 : $type = 'prerender' ; break ;
				default : $type = 'preload' ;
			}
			
			\Plg\Pro_critical\Helpers\Assets\Links::setPreload( $preload , null ,  $type  );
			
			 return ;
			
			$app = \JFactory::getApplication();
			$body = $app->getBody();
			
			
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( '//body//img' );
			
			$Links = Links::instance();
			
			$i = 0 ;
			foreach( $Nodes as $node )
			{
				$i++;
				
				$attr = $dom::getAttrElement( $node , [] );
				$srcRes = $Links->linkAnalysis ( $attr['src']  ) ;
				
				if( $i <= $task['skip_items'] )
				{
					$preload = new \stdClass();
					$preload->src = $attr['src'] ;
					\Plg\Pro_critical\Helpers\Assets\Links::setPreload( $preload );
					continue ;
				}#END IF
				
				
				
				$node->removeAttribute('src');
				$node->setAttribute("data-src", $srcRes['file'] );
				$node->setAttribute("class", 'lazy' );
				$node->setAttribute("src", self::$DOT );
			
			}#END FOREACH
//			die(__FILE__ .' '. __LINE__ );
			$body = $dom->saveHTML();
			$app->setBody( $body );
			
			$LazyLoad = LazyLoad::instance();
			
			
			/*echo '<pre>';
			print_r( $Nodes );
			echo '</pre>' . __FILE__ . ' ' . __LINE__;
			die( __FILE__ . ' ' . __LINE__ );*/
			
			//		die(__FILE__ .' '. __LINE__ );
		}
	}