<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\Js;
	
	use Plg\Pro_critical\Helpers\Assets\Links;
	
	/**
	 * Class Link - Обработка ссылок на Css файлы
	 * @since 3.9
	 *@package Plg\Pro_critical\Helpers\Assets\Css
	 */
	class Link extends Links
	{
		
		
		
		
		/**
		 * Подготовить ссылку к загрузи
		 * @param $Link
		 *
		 * @return mixed
		 *
		 * @since version
		 */
		public static function prepareJsLinkData ( $Link  ){
			
			Links::prepareLinkData( $Link , 'js');

			return $Link ;
		}
	
	
	}