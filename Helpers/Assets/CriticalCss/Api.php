<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	
	use Plg\Pro_critical\Helpers\Assets\Assets;
	use Plg\Pro_critical\Helpers\Assets\Links;
	use Joomla\CMS\Filesystem\File;
	
	
	
	class Api
	{
		/**
		 * @var \Joomla\CMS\Application\CMSApplication
		 * @since 3.9
		 */
		private $app;
		/**
		 * @var int - id - объекта критических стилей
		 * @since 3.9
		 */
		private static $Id ;
		/**
		 * Адрес сервера обработки
		 * use_critical_css
		 * @var string
		 * @since 3.9
		 */
		public static $urlServer ;
		/**
		 * @var string Путь к директории для сохранения AllCSS По умолчанию
		 * @since 3.9
		 */
		private static $urlServerDef = 'https://pro-critical.cf/tools/assets/' ;
		/**
		 * @var string Url Page для которой создаются критичиские стили
		 * @since 3.9
		 */
		private static $UrlPage ;
		/**
		 * Путь к директории для сохранения AllCSS
		 * @var string
		 * @since 3.9
		 */
		public static $allCssDir = JPATH_PLUGINS . '/system/pro_critical/cache';
		/**
		 * URL - путь к директории AllCSS
		 * @var string
		 * @since 3.9
		 */
		private static $allCssUrl = 'plugins/system/pro_critical/cache';
		/**
		 * Юзерагент по усолчанию
		 * @var string
		 * @since 3.9
		 */
		private static $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';
		
		/**
		 * Api constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		public function __construct ()
		{
			$this->app =  \JFactory::getApplication() ;
			$root = \JURI::root();
			# get params
			$params	= \JComponentHelper::getParams('com_pro_critical');
			# Адрес сервера обработки
			self::$urlServer = $params->get('url_server' , self::$urlServerDef ) ;
			# Ссылка на файл с AllCss
			self::$allCssUrl = $root . self::$allCssUrl ;
		}
		
		/**
		 * Точка входа Ajax
		 * @throws \Exception
		 * @since 3.9
		 */
		public function onAjaxApiCritical ()
		{
			# TODO Refactoring - для других задач
			$dataJson = $this->app->input->get('data' , null , 'RAW') ;
			$data = json_decode( $dataJson ) ;
			
			self::$Id = $data->id  ;
			# Url Page для которой создаются критичиские стили
			self::$UrlPage = $data->url  ;
			# Получть объекты файлов из списка ID
			$res = Assets::getAssetsById( $data->cssLinkHistory  , 'css_file' , 'id' );
			#Сортируем массив в порядке загрузки на странице
			$css_fileArr = self::sortArr($data->cssLinkHistory , $res );
			# Получть объекты стилей из списка ID
			$res = Assets::getAssetsById( $data->StyleHostory  , 'css_style' , 'id' );
			#Сортируем массив в порядке загрузки на странице
			$css_styleArr = self::sortArr( $data->StyleHostory , $res );
			# Получить ссылки на файлы
			$arrFiles = $this->getLinksFiles($css_fileArr) ;
			
			try
			{
				# читаем все файлы
				$Files = new Files() ;
				$Files->addContentFile( $arrFiles );
			}
			catch( \Exception $e )
			{
				echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );
			}
			
			# выбрать все стили
			$ALLCSS = $this->getAllCss ( $arrFiles , $css_styleArr ) ;
			
			# Сохранить все стили в файл
			$this->saveFile( $ALLCSS , self::$UrlPage );
			
			$this->post() ;
			
			echo'<pre>';print_r( $ALLCSS );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $css_styleArr );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
		}
		
		/**
		 * Подготовить запрос для создания Critical Css
		 * @since 3.9
		 */
		private function post(){
			$Arrdata = [
				'task' => 'getCtiticalCss' ,
				'cssUrl'  => self::$allCssUrl  ,
				'urlSite' =>  self::$UrlPage . '?pro_critical=1'  ,
				'userAgent' => self::$userAgent ,
			];
			$Arrdata['width'] = 1300 ;
			$Arrdata['height']= 900 ;
			
			try
			{
				$criticalData = \Plg\Pro_critical\Helpers\Assets\CriticalCss\Api\Curl::sendPost( $Arrdata );
			}
			catch( \Throwable $e )
			{
				# TODO Доработать если сервер не доступен
			}
			
			
			
			
			
			if( isset($criticalData->success) && $criticalData->success )
			{
				$this->save($criticalData);
			}#END IF
			
			
			# Url Страницы
			$file = self::$allCssDir .'/'. md5( self::$UrlPage ). '.css' ;
			# Удалить файл AllCSS
			File::delete($file);
			
			echo'<pre>';print_r( $criticalData );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
			
		}
		
		/**
		 * Сохранить полученные Critical CSS в DB
		 * @param $criticalData
		 * @since 3.9
		 */
		private function save($criticalData){
			
			$db = \JFactory::getDbo() ;
			$query = $db->getQuery( true ) ;
			// Поля для обновления
			$fields = array(
				$db->quoteName('critical_css_code') . ' = ' . $db->quote($criticalData->data[0]->criticalCss) ,
				$db->quoteName('checked_out') . ' = ' . $db->quote(0) ,
				$db->quoteName('photo_before') . ' = '  . $db->quote($criticalData->data[0]->screenshots->before) ,
				$db->quoteName('photo_afte') . ' = '  . $db->quote($criticalData->data[0]->screenshots->after) ,
			);
			// Условия обновления
			$conditions = array(
				$db->quoteName('id') . ' = '  . $db->quote(self::$Id)
			);
			$query->update($db->quoteName('#__pro_critical_css'))
				->set($fields)
				->where($conditions);
			//  echo $query->dump();
			$db->setQuery($query);
			$db->execute();
			
		}
		
		/**
		 * Сохрать Файл с AllCSS
		 * @since 3.9
		 */
		private function saveFile($ALLCSS , $url ){
			# Url Страницы
			$fN = '/'. md5( $url ). '.css' ;
			# Имя файла
			
			# Ссылка на файл
			self::$allCssUrl .= $fN ;
			
			$commetn = '/*****! URL-'.$url.' !*****/' . "\n\r";
			
			# Todo Дработать обработку ошибок во время создания файла
			try
			{
				// Code that may throw an Exception or Error.
				File::write( self::$allCssDir . $fN , $commetn . $ALLCSS );
			}
			catch( \Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
			}
			catch( \Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>';
				print_r( $e );
				echo '</pre>' . __FILE__ . ' ' . __LINE__;
			}
		}
		
		/**
		 * Собрать весь css вместе
		 * @param $fArr array cssLinkHistory
		 * @param $sArr array StyleHostory
		 *
		 * @return string|null
		 * @since 3.9
		 */
		private function getAllCss ( $fArr , $sArr ){
			$res = null ;
			foreach( $fArr as $item )
			{
				$res .= $item->content ; 
			}#END FOREACH
			foreach( $sArr as $item )
			{
				$res .= $item->content ;
			}#END FOREACH
			return $res ;
		}
		
		/**
		 * Подготовить ссылки на файлы
		 * @param $arrFiles
		 *
		 * @return array
		 * @since 3.9
		 */
		private function getLinksFiles( array $arrFiles){
			foreach( $arrFiles as $i => $File )
			{
				# Подготовить ссылку к загрузи - определить параметры ссылки
				$arrFiles[$i]  = Links::prepareLinkData( $File , 'css' , true );
			}#END FOREACH
			return $arrFiles ;
		}
		
		/**
		 * Сортируем массив в порядке загрузки на странице
		 * @param $arrKeys
		 * @param $arrData
		 *
		 * @return array
		 * @since 3.9
		 */
		public static function sortArr($arrKeys , $arrData ){
			$retArr = [] ;
			foreach( $arrKeys as $Key )
			{
				$retArr[] = $arrData[$Key] ;
			}#END FOREACH
			return $retArr ;
		}
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	