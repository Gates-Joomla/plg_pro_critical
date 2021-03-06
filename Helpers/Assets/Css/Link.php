<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
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
		public static function prepareLinkCssData ( $Link ){
			
			
			# Предварительная обработка ссылок на ресурсы
			Links::prepareLinkData($Link);
			
			
			
			
			$dataLink = $Link ;
			return $dataLink ;
		}
	
	
	}