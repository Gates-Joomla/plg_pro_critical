<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	
	use Plg\Pro_critical\Helpers\Assets\Css\Style;
	use Plg\Pro_critical\Helpers\Assets\Css;
	use Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	class Script
	{
		/**
		 * @var string - Ссылка на файл Js обработчика
		 * @since 3.9
		 */
		static $LinkJsFileHandler = '/plugins/system/pro_critical/assets/js/front_CriticalCss.js';
		
		/**
		 * Добавить JS обработчик для создания критичиских стилей
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function add($CriticalCss){
			
			if( Css::$StopForCritical || Style::$StopForCritical  ) return ; #END IF
			
			$dom = new \GNZ11\Document\Dom();
			
			$date = new \DateTime();
			$now = $date->getTimestamp()   ;
			
			$uri = \Joomla\CMS\Uri\Uri::getInstance();
			$link = $uri->toString(array('path', 'query', 'fragment'));
			$rLink = \Joomla\CMS\Router\Route::_('index.php'.$link , $xhtml = true, $tls = 0 , $absolute = true) ;
			
			
			/*echo'<pre>';print_r( $rLink );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $link );echo'</pre>'.__FILE__.' '.__LINE__;
			
			echo'<pre>';print_r( $uri );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );*/
			
			
			
			$Link = self::$LinkJsFileHandler . '?i=' . $now  ;
			# Добавить тег ссылка Js в тело документа перед закрывающемся тегом </body>
			$dom::writeDownTag( 'script' , null , ['src' => $Link , 'async'=> 1   ] );
			
			$scriptData = [] ;
			$scriptData['id'] = CriticalCss::$CurrentCriticalId; // $CriticalCss['id'] ;
			$scriptData['url'] = $rLink ;
			$scriptData['cssLinkHistory'] =   Css::$LinkHistory ;
			$scriptData['StyleHostory'] =   Style::$StyleHostory ;
			
			# Добавить тег  Js в тело документа перед закрывающемся тегом </body>
			$dom::writeDownTag( 'script' , json_encode( $scriptData ) , ['type' => 'application/json','id'=>'CriticalCss'] );
			
		}
	}