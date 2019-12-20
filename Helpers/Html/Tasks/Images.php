<?php
	namespace Plg\Pro_critical\Html\Tasks;
	
	use Plg\Pro_critical\Html\Libraries\LazyLoad;
	
	class Images
	{
		private static $DOT = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		/**
		 * @param $task
		 *
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function aplly ( $task )
		{
			$app = \JFactory::getApplication();
			$body = $app->getBody();
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( '//body//img' );
			
			echo'<pre>';print_r( $Nodes );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
			foreach( $Nodes as $node )
			{
				$attr = $dom::getAttrElement( $node , [] );
				$node->removeAttribute('src');
				$node->setAttribute("data-src", $attr['src'] );
				$node->setAttribute("class", 'lazy' );
				$node->setAttribute("src", self::$DOT );
			
			}#END FOREACH
			
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