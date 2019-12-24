<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	use Joomla\CMS\Uri\Uri;
	use Joomla\CMS\Factory;
	use Exception;
	
	
	
	/**
	 * Class Links - ОБЩИЙ Обработчик ссылок
	 * @since 3.9
	 * @package Plg\Pro_critical\Helpers\Assets
	 */
	class Links extends Assets
	{
		public static $instance;
		public static $Preload ;
		/**
		 * @var string Медиа версия для файлов по умлчанию
		 * @since 3.9
		 */
		public static $MediaVersion;
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		public function __construct ( $options = [] )
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
		 * Установка методов предварительной загрузки
		 * preload | prefetch
		 * @return bool
		 * @throws Exception
		 * @since 3.9
		 */
		public static function setPreconectLinks()
		{
			if( !count( self::$Preload ) ) return true;
			$dom = new \GNZ11\Document\Dom();
			foreach( self::$Preload as $href => $attr )
			{
				$attr[ 'href' ] = $href;
				
				# Ставим в начало <head>
//				$dom::writeTopHeadTag( 'link' , '' , $attr );
				
				# Ставим теги перед </head>
				$dom::writeBottomHeadTag( 'link' , '' , $attr );
				
				
				
			}#END FOREACH
			return true;
		}
		
		/**
		 * Получить медиаверсию
		 * @param   string
		 * @since 3.9
		 */
		public static function setMediaVersion ()
		{
			$doc = Factory::getDocument();
			self::$MediaVersion = $doc->getMediaVersion() ;
		}
		
		/**
		 * Getter Media Version
		 * @return string
		 * @since 3.9
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
		 * @return bool|false|float|string
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
		public static function prepareLinkData ( $Link , $type = 'css' , $onlyLink = false ){
			$href = $Link->file   ;
			# Переопределение
			if ( isset( $Link->override ) && !empty( $Link->override_file ) && $Link->override   )
				$href = $Link->override_file ; #END IF
			
			# режим разработки отключен
			if ( !$Link->file_debug ){
				# Мин версия
				if ( isset($Link->minify) && $Link->minify && !empty( $Link->minify_file )  )
					$href = $Link->minify_file ; #END IF
			}#END IF
			
			
			# TODO - Доделать delayed loading
			# Пропустить если отложенная загрузка
			if ( isset($Link->delayed_loading)  && $Link->delayed_loading) {
			
			} #END IF
			
			
			$MediaVersion = self::getMediaVersion()  ;
			
			# id Revision
			if( isset( $Link->ver_type ) && $Link->ver_type && !empty( $Link->revision_id ) )
			{
				$href .= '?i=' . $Link->revision_id;
			}else{
				#TODO Добавить проверку на внешний файл - не должно быть версии
				$href .= '?i=' . $MediaVersion ;
			}#END IF
			
			
			
			if ( isset($Link->params_query) && $Link->params_query ) {
				
				$i = null ;
				$queryStr = null ;
				$params_query = json_decode( $Link->params_query );
				foreach( $params_query as $query )
				{
					if( $query->value == $MediaVersion || $query->name == $MediaVersion ) continue ; #END IF
					
					if( isset($query->published) && !$query->published ) continue ;
					
					$queryStr .= !$i ?'':'&' ;
					$queryStr .= $query->name. ( !empty($query->value)?'='.$query->value:'' ) ;
					$i++;
				}#END FOREACH
				
				$href .= ( !empty($queryStr) ? '&' . $queryStr : null ) ;
			}
			
			switch($type){
				case 'js' :
					$Link->src = $href;
				break ;
				default :
					$Link->href	= $href ;
			}
			
			
			
			# Если с прелоадером
			if ($Link->preload) self::setPreload($Link , $type);
			
			return $Link ;
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
		 * @param   object  $preload
		 * @param   string  $typeDefault
		 * @param   string  $typeLink
		 *
		 * @since     3.8
		 * @author    Gartes
		 *
		 * @copyright 05.01.19
		 */
		public static function setPreload ( $preload , $typeLink = null , $typeDefault = 'preload'  )
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
			if( isset( $pUrl[ 'path' ] ) ){
				$pathUrl = $pUrl[ 'path' ];
			}else
			{
				$pathUrl  = $url  ;
			}
			$preconnect['as'] = self::getFileType( $pathUrl  );
			
			if( isset( $preload->onload  ))
			{
				$preconnect['onload'] = $preload->onload ;
			}#END IF
			
			
			self::$Preload[$url] =    $preconnect  ;
		}#END FN
		
		/**
		 * Получить тип файла
		 * @param   string  $pathUrl - путь к файлу
		 * @param          $url
		 * @param          $preconnect
		 *
		 * @return mixed
		 * @since 3.9 
		 */
		protected static function getFileType ( $pathUrl   )
		{
			$info = new \SplFileInfo( $pathUrl );
			//PHP Получаем расшерение файла
			$Ext = $info->getExtension();
			
			switch( $Ext )
			{
				case 'js':
					$FileType = 'script';
					break;
				case 'css':
					$FileType = 'style';
					break;
				case 'gif':
				case 'png':
				case 'jpg':
				case 'jpeg':
				case 'webp':
				case 'svg':
					$FileType = 'image';
					break;
				case 'ttf':
				case 'woff':
				case 'woff2':
					$FileType = 'font';
					break;
			}
			
			return $FileType;#END SWITCH
		}
		
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
				'err_code' => [] ,
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
				$log[ 'err_code' ][] = 1000 ;
			}#END IF
			
			if( preg_match( "/[а-яё]+/iu" , $href ) )
			{
				$log[ 'err' ][] = 'В ссылке присутствую русские буквы.';
				$log[ 'err_code' ][] = 1001 ;
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
					$log[ 'err_code' ][] = 1002 ;
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
							$log[ 'err_code' ][] = 1003 ;
						}#END IF
						
					}
					else
					{
						# Если на сайте включено SSL
						if( $force_ssl == 2 )
						{
							$log[ 'err' ][] = 'Протокол ссылки без SSL! Протокол сайта с SSL. Это приведет к ошибкам при загрузки данного ресурса.';
							$log[ 'err_code' ][] = 1004 ;
						}#END IF
					}#END IF
				}
			}
			else
			{
				$log[ 'err' ][] = 'Тип протокола не определен';
				$log[ 'err_code' ][] = 1005 ;
			}#END IF
			
			# Проверить домен
			if( $isLocalHost  && isset( $protocol[ 'host' ] )   )
			{
				$protocolSite             = parse_url( Uri::root() );
				if( $protocolSite['host'] == $protocol['host'] )
				{
					$log[ 'err' ][] = 'Для локальной ссылки указан абсолютный путь';
					$log[ 'err_code' ][] = 1006 ;
					$log['absolute_path'] = true ;
				}#END IF
			}#END IF
			
			# Проверка path
			if( stristr( $protocol[ 'path' ] , '//' ) )
			{
				$log[ 'err' ][] = 'Путь содержит два слеша после домена';
				$log[ 'err_code' ][] = 1007 ;
				$copyPath       = preg_replace( '/^\/\//' , '/' , $protocol[ 'path' ] );
				$href           = str_replace( '/' . $copyPath , $copyPath , $href );
				
			}
			else
			{
				# если host - не содержит точку
				if( !stristr( $protocol[ 'host' ] , '.' ) && $isLocalHost && preg_match( '/^\/\//' , $href ) )
				{
					$log[ 'err' ][]      = 'Ошибка в адресе локального файла. Два слеша в начале относительного пути';
					$log[ 'err_code' ][] = 1008;
					$href                = '/' . $protocol[ 'host' ] . $protocol[ 'path' ];
				}
				else if( $isLocalHost )
				{
					$pos = stripos($protocol['path'], '/' );
					if( $pos > 0 )
					{
						$log[ 'err' ][]      = 'В начале пути локального файла отсутствует ведущий слэш';
						$log[ 'err_code' ][] = 1009;
						$href = '/'.$protocol['path'];
					}#END IF
				}#END IF
			}#END IF
			
			#PHP Получаем расшерение файла
			$info = new \SplFileInfo( $protocol['path']  );
			$Ext = $info->getExtension();
			
			$log [ 'file_ext' ] = $Ext ;
			$log [ 'file_type' ] = self::getFileType ( $protocol['path']);
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