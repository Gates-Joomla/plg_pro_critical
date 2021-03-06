<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	
	use Joomla\CMS\Filesystem\File;
	
	class Files
	{
		
		private $Links ;
		/**
		 * @var string текущий путь к файлу
		 *             используется в обработке ошибок
		 * @since 3.9
		 */
		private static $cerentHref ;
		
		/**
		 * Files constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		public function __construct ()
		{
			$this->Links = \Plg\Pro_critical\Helpers\Assets\Links::instance();
		}
		
		/**
		 * читаем все файлы
		 * @param $arrFiles
		 *
		 * @return mixed
		 * @throws \Exception
		 * @since 3.9
		 */
		public function addContentFile ( $arrFiles )
		{
			foreach( $arrFiles as $i => $arrFile )
			{
				$arrFiles[$i]->content = $this->getFileContent ( $arrFile );
			}#END FOREACH
			
			return $arrFiles ;
		}
		
		/**
		 * Прочитать файл
		 *
		 * @param $file  - объект файла
		 *
		 * @return false|string
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getFileContent ( $file )
		{
			$uPath = $file->href ;
			# Если файл локальный
			if( $file->no_external )
			{
				
				# Если ссылка на файл не абсолютная
				if( strpos( $file->href  , \JURI::root() ) === false )
				{
					$uPath = \JURI::root( false , $file->href ) ;
					\JURI::reset() ;
				}#END IF
				
				self::$cerentHref = $uPath;
				# TODO Доработать логирование ошибок
				set_error_handler( [ $this , "warning_handler" ] , E_WARNING );
				$contents = file_get_contents( $uPath );
				restore_error_handler();
				
			}else{
				$contents = \Plg\Pro_critical\Helper_site::get_url($uPath);
			}#END IF
			return $contents ;
			
		}
		
		/**
		 * Обоаботка ошибок для file_get_contents
		 * @param $errno
		 * @param $errstr
		 * @since 3.9
		 */
		function warning_handler($errno, $errstr) {
			// do something
			echo'<pre>';print_r( self::$cerentHref );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $errno );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $errstr );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
		}
		
		/**
		 * Удалить все файлы AllCss Из директории кэша
		 * @return int - Количество удаленных файлов
		 * @since 3.9
		 */
		public static function clearCacheFiles(){
			# Путь к директории кэша
			$Dir = \Plg\Pro_critical\Helpers\Assets\CriticalCss\Api::$allCssDir ;
			$i_count = 0 ;
			foreach (glob($Dir."/*.css") as $AllCssFile) {
				# Удалить файл AllCSS
				File::delete($AllCssFile);
				$i_count++;
			}
			return $i_count ;
		}
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	