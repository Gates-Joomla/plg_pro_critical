<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	use Joomla\CMS\Uri\Uri;
	use Joomla\CMS\Factory;
	use Exception;
	
	/**
	 * Class Links - ОБЩИЙ Обработчик ссылок
	 * @since 3.9
	 *@package Plg\Pro_critical\Helpers\Assets
	 */
	class Links extends Assets
	{
		
		public static $Preload ;
		/**
		 * @var string
		 * @since 3.9
		 */
		public static $MediaVersion;
		
		public static $instance;
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			
			
			
			return $this;
		}
		/**
		 * @param   array  $options
		 *
		 * @return Links
		 * @throws Exception
		 * @since 3.9
		 */
		public static function instance ( $options = [] )
		{
			if( self::$instance === null )
			{
				self::$instance = new self( $options );
			}
			
			return self::$instance;
		}#END FN
		
		/**
		 * @param   string
		 * @since 3.9
		 */
		public static function setMediaVersion ()
		{
			$doc = \Joomla\CMS\Factory::getDocument();
			self::$MediaVersion = $doc->getMediaVersion() ;
		}
		
		/**
		 * @return string
		 */
		public static function getMediaVersion ()
		{
			if( !self::$MediaVersion )
			{
				self::setMediaVersion() ;
			}#END IF
			return self::$MediaVersion;
		}#END FN
		
		/**
		 * Парсинг параметры запроса
		 *
		 * @param   array  $hrefArr
		 *
		 * @return array or null
		 *
		 * @since 3.9
		 */
		protected static function parseRequestParameters ( array $hrefArr   )
		{
			$paramHrefArr = explode( '&' , $hrefArr[ 1 ] );
			$i            = 0;
			
			$data = [] ;
			foreach( $paramHrefArr as $item )
			{
				$paramArr                               = explode( '=' , $item );
				
				if( !$paramArr[ 0 ] ) continue; #END IF
				
				$nam                                    = $paramArr[ 0 ];
				$val                                    = $paramArr[ 1 ];
				$data[ 'params_query' . $i ][ 'name' ]  = $nam;
				$data[ 'params_query' . $i ][ 'value' ] = $val;
				
				$i++;
				
			}#END FOREACH
			
			if( count($data) ) return json_encode( $data ); #END IF
			
			return null ;
		}
		
		/**
		 * Предварительная обработка ссылок на ресурсы
		 * @param $Link
		 *
		 * @return mixed
		 * @since 3.9
		 */
		public static function prepareLinkData ( $Link ){
			$Link->href = $Link->file   ;
			# Переопределение
			if ( isset( $Link->override ) && !empty( $Link->override_file ) && $Link->override   )
				$Link->href = $Link->override_file ; #END IF
			
			# режим разработки отключен
			if ( !$Link->file_debug ){
				# Мин версия
				if ( isset($Link->minify) && $Link->minify && !empty( $Link->minify_file )  )
					$Link->href = $Link->minify_file ; #END IF
			}#END IF
			
			# Пропустить если отложенная загрузка
			if ( isset($Link->delayed_loading)  && $Link->delayed_loading) {
			
			} #END IF
			
			# Если с прелоадером
			if ($Link->preload) self::setPreload($Link);
			
			
			return $Link->href ;
		}
		
		/**
		 * Проверка на кроссдоменн
		 * @param $url
		 *
		 * @return bool
		 * @since 3.9
		 */
		public static function checkCrossorigin( $url ){
			$local = \JUri::root();
			$pUrl = parse_url($url) ;
			$pLocalUrl = parse_url( $local ) ;
			if( isset( $pUrl['host']  ) )
			{
				if( $pUrl['host'] != $pLocalUrl['host'] )
				{
					return true ;
				}#END IF
			}#END IF
			return false ;
		}
		
		/**
		 * Добавить в список предворительных загрузок ресурсов
		 *
		 * @param        $preload
		 * @param string $typeDefault
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 05.01.19
		 */
		public static function setPreload ( $preload , $typeDefault = 'preload'  )
		{
			if (isset($preload->src)) {
				$url   = $preload->src ;
			}elseif (isset($preload->url )){
				$url   = $preload->url ;
			}else{
				$url = $preload->href ;
			}
			
			$preconnect['rel'] = $typeDefault ;
			
			#Проверка на кроссдоменн
			$preconnect['crossorigin'] =    self::checkCrossorigin( $url   )   ;
			
			
			$pUrl = parse_url($url) ;
			
			if (isset($pUrl['path'])){
				
				$info = new \SplFileInfo( $pUrl['path']  );
				
			}else{
				$info = new \SplFileInfo( $url  );
				
			}
			
			// Получаем расшерение
			$Ext = $info->getExtension();
			
			
			
			
			switch ($Ext){
				case 'js':
					$preconnect['as'] = 'script' ;
					break ;
				case 'css':
					$preconnect['as'] = 'style' ;
					break ;
				case 'gif':
				case 'png':
				case 'jpg':
				case 'jpeg':
				case 'webp':
				case 'svg':
					
					$preconnect['as'] = 'image' ;
					break ;
				case 'ttf':
				case 'woff':
				case 'woff2':
					$preconnect['as'] = 'font' ;
					break ;
			}#END SWITCH
			
			
			self::$Preload[$url] =    $preconnect  ;
		}#END FN
		
		/**
		 * Проверка хоста ссылки локалный или внешний
		 * @param          $href
		 *
		 * @return bool - если локальный TRUE иначе FALSE
		 *
		 * @since 3.9
		 */
		protected function checkLocalHost ( $href    )
		{
			$protocol = parse_url( $href );
			# для ссылок вида //joomla-upd.ga/test_css/test_home.css
			# если домен действительно содержит точку
			# и он не root домен сайта (не имеет вхождений в Uri::root() )
			if( stristr( $protocol[ 'host' ] , '.' ) && !stristr( Uri::root() , $protocol[ 'host' ] ) )
			{
				return false ;
			}
			return true ;
		}
		
		/**
		 * Разбор ссылки - поиск ошибок - исправление ссылки - определение локальная ссылка или нет
		 * @param $href
		 *
		 * @return array - []
		 *
		 * @since 3.9
		 */
		public function linkAnalysis ( $href  )
		{
			
			$config = Factory::getConfig();
			$force_ssl = $config->get('force_ssl');
			$log = [
				'file' => null ,
				'no_external' => false ,
				'err' => [] ,
				'protocol' => [] ,
				
				'absolute_path' => false ,
				'err_href' => null ,
				'is_error' => false ,
			];
			
			$copyOrigHref = $href ;
			
			$href = trim( $href );
			
			if( preg_match( '/\s/' , $href ) )
			{
				$log[ 'err' ][] = 'В ссылке присутствую пробелы это може привести к ошибкам';
			}#END IF
			
			if( preg_match( "/[а-яё]+/iu" , $href ) )
			{
				$log[ 'err' ][] = 'В ссылке присутствую русские буквы.';
			}
			
			$protocol             = parse_url( $href );
			#Проверка хоста ссылки локалный или внешний
			$isLocalHost          = $this->checkLocalHost( $href );
			
			# Проверка протокола
			if( !isset( $protocol[ 'scheme' ] ) )
			{
				if( !$isLocalHost )
				{
					$log[ 'err' ][] = 'Отсутствует протокол (scheme) в адресе';
				}#END IF
			}
			else if( stristr( $protocol[ 'scheme' ] , 'http' ) )
			{
				# Если не ssl и внешний
				if( $protocol[ 'scheme' ] == 'http' )
				{
					if( !$isLocalHost )
					{
						# Если на сайте включено SSL
						if( $force_ssl == 2 )
						{
							$log[ 'err' ][] = 'Протокол ссылки без SSL! но этот сайт с SSL';
						}#END IF
						
					}
					else
					{
						# Если на сайте включено SSL
						if( $force_ssl == 2 )
						{
							$log[ 'err' ][] = 'Протокол ссылки без SSL! Протокол сайта с SSL. Это приведет к ошибкам при загрузки данного ресурса.';
						}#END IF
					}#END IF
				}
			}
			else
			{
				$log[ 'err' ][] = 'Тип протокола не определен';
			}#END IF
			
			# Проверить домен
			if( $isLocalHost  && isset( $protocol[ 'host' ] )   )
			{
				$protocolSite             = parse_url( Uri::root() );
				if( $protocolSite['host'] == $protocol['host'] )
				{
					$log[ 'err' ][] = 'Для локальной ссылки указан абсолютный путь';
					$log['absolute_path'] = true ;
				}#END IF
			}#END IF
			
			
			
			# Проверка path
			if( stristr( $protocol[ 'path' ] , '//' ) )
			{
				$log[ 'err' ][] = 'Путь содержит два слеша после домена';
				$copyPath       = preg_replace( '/^\/\//' , '/' , $protocol[ 'path' ] );
				$href           = str_replace( '/' . $copyPath , $copyPath , $href );
				
			}
			else
			{
				# если host - не содержит точку
				if( !stristr( $protocol[ 'host' ] , '.' ) && $isLocalHost && preg_match( '/^\/\//' , $href ) )
				{
					$log[ 'err' ][] = 'Ошибка в адресе локального файла. Два слеша в начале относительного пути';
					$href           = '/' . $protocol[ 'host' ] . $protocol[ 'path' ];
				}#END IF
			}
			
			
			
			$log [ 'file' ]     = $href;
			$log [ 'no_external' ] = $isLocalHost;
			$log [ 'protocol' ] = $protocol;
			
			
			
			if( count( $log[ 'err' ] ) )
			{
				$log [ 'err_href' ] = $copyOrigHref;
				$log [ 'is_error' ] = true;
				$log [ 'err_path_log' ] = implode("\n" , $log[ 'err' ] );
			}#END IF
			
			
			
			
			return $log ;
		}
		
	
	}