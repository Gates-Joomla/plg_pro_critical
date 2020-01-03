<?php
	namespace Plg\Pro_critical\Html\Tasks;
	
	use Plg\Pro_critical\Html\Libraries\LazyLoad;
	
	class To_template
	{
		public static function aplly ( $task )
		{
			
			$app = \JFactory::getApplication();
			$body = $app->getBody();
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( $task['selector'] );
			$template = $dom->createElement('template');
			foreach( $Nodes as $node )
			{
				$idTask = $task['task_id'] ;
				$template_clone = $template->cloneNode();
				$template_clone->setAttribute( 'id' , $idTask );
				$template_clone->setAttribute( 'class' , 'lazy' );
				$node->parentNode->replaceChild( $template_clone , $node );
				$template_clone->appendChild( $node );
			
//				echo'<pre>';print_r(  );echo'</pre>'.__FILE__.' '.__LINE__;
//				echo'<pre>';print_r( $node );echo'</pre>'.__FILE__.' '.__LINE__;
			}
			
			$body =    $dom->saveHTML() ;
			$app->setBody($body);
			
			$LazyLoad = LazyLoad::instance();
			
//			die(__FILE__ .' '. __LINE__ );
		}
	}