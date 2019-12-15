<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	
	use Plg\Pro_critical\Helpers\Assets\Css\Style;
	use Plg\Pro_critical\Helpers\Assets\Css;
	
	class Script
	{
		static $Link = '/plugins/system/pro_critical/assets/js/front_CriticalCss.js';
		/**
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function add($CriticalCss){
			
			if( Css::$StopForCritical || Style::$StopForCritical  ) return ; #END IF
			
			$date = new \DateTime();
			$now = $date->getTimestamp()   ;
			
			$dom = new \GNZ11\Document\Dom();
			$Link = self::$Link . '?i=' . $now  ;
			# Добавить тег ссылка Js в тело документа перед закрывающемся тегом </body>
			$dom::writeDownTag( 'script' , null , ['src' => $Link , 'async'=> 1   ] );
			
			$scriptData = [] ;
			$scriptData['id'] = $CriticalCss['id'] ;
			$scriptData['cssLinkHistory'] =   Css::$LinkHistory ;
			$scriptData['StyleHostory'] =   Style::$StyleHostory ;
			# Добавить тег  Js в тело документа перед закрывающемся тегом </body>
			$dom::writeDownTag( 'script' , json_encode( $scriptData ) , ['type' => 'application/json','id'=>'CriticalCss'] );
			
		}
	}