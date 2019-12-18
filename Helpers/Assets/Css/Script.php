<?php
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	use Plg\Pro_critical\Helpers\Assets\Css;
	
	class Script
	{
		
		private static $loaderScript = "document.addEventListener('DOMContentLoaded', function () {var script=document.createElement('script');script.src='/plugins/system/pro_critical/assets/js/front_CssAfterLoad.js';document.head.appendChild(script);});";
		
		/**
		 * Установить загрузку Css после загрузки страницы
		 * @param $StackStyle
		 *
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function addCssAfterLoadPage($StackStyle){
			$dom = new \GNZ11\Document\Dom();
			$outputFiles = [];
			
			foreach( $StackStyle['link'] as $item )
			{
				$outputFiles['links'][] = $item->href ;
			}#END FOREACH
			
			$outputFiles['style'] = css::$StackStyle['style'] ;
			
			# Добавить тег  Js в тело документа перед закрывающемся тегом </body>
			try
			{
				$dom::writeDownTag( 'script' , json_encode( $outputFiles ) , [ 'type' => 'application/json' , 'id' => 'CssAfterLoad' ] );
				$dom::writeDownTag( 'script' , self::$loaderScript , [] );
			}
			catch( \Exception $e )
			{
			}
			
			
			
			
		}
	}