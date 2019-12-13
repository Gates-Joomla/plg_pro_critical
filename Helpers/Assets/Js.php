<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	use Joomla\CMS\Factory as JFactory;
	use JLoader;
	use JModelLegacy;
	use Exception;
	use JDate;
	use Joomla\CMS\Uri\Uri;
	use Throwable;
	
	
	class Js extends Links
	{
		private $app;
		public static $instance;
		private $statistics;
		private $jsFileData;
		private $scriptTagData;
		
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			// parent::__construct ( $options );
			$this->app = \JFactory::getApplication();
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Js
		 * @throws \Exception
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
		 * Извлечь из страницы в скриты и ссылки на Js файлы
		 * добавить новые найденые в справочники
		 * @since 3.9
		 */
		public function getListJs ()
		{
			$body = $this->app->getBody();
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath       = new \DOMXPath( $dom );
			$Nodes       = $xpath->query( '//script' );
			$link        = [];
			$scriptTag   = [];
			$hashArrLink = [];
			$hashArr     = [];
			foreach( $Nodes as $node )
			{
				$attr = $dom::getAttrElement( $node , [ 'rel' ] );
				# Если ссылка на файл
				if( isset( $attr[ 'src' ] ) & !empty( $attr[ 'src' ] ) )
				{
					$hash          = md5( $attr[ 'src' ] );
					$hashArrLink[] = $hash;
					$hrefArr       = explode( '?' , $attr[ 'src' ] );
					unset( $attr[ 'href' ] );
					$href = $hrefArr[ 0 ];
					
					# Разбор ссылки - поиск ошибок - исправление ссылки - определение локальная ссылка или нет
					$log  = $this->linkAnalysis( $href );
					$href = $log[ 'file' ];
					
					
					$Registry = new \Joomla\Registry\Registry;
					$Registry->set( 'load' , 1 );
					$Registry->set( 'hash' , $hash );
					
					$data = array_merge( $log , $attr );
					
					# Разобрать параметры ссылки
					$data[ 'params_query' ] = $this->parseRequestParameters( $hrefArr );
					
					$Registry->loadArray( $data );
					$link[ $hash ]                = $Registry->toObject();
					$this->statistics[ 'errors' ] += ( count( $log[ 'err' ] ) );
					
					
					unset( $link[ $hash ]->src );
					
				}
				else
				{
					
					
					$hash      = md5( $node->nodeValue );
					$hashArr[] = $hash;
					
					$Registry = new \Joomla\Registry\Registry;
					$Registry->set( 'load' , 1 );
					$Registry->set( 'hash' , $hash );
					$Registry->set( 'content' , $node->nodeValue );
					
					
					$subFormDataJson = self::getSubFormLinksData( 'params_query' , $attr );
					$Registry->set( 'params_query' , $subFormDataJson );
					
					$scriptTag[ $hash ] = $Registry->toObject();
				}#END IF
				
				
				# Удалить найденый узел
				$node->parentNode->removeChild( $node );
			}#end FOREACH
			
			$body = $dom->saveHTML();
			$this->app->setBody( $body );
			
			
			# Объеденить с данными из базы модели
			$this->jsFileData = self::getItemsByHash( $hashArrLink , 'js_file' , $link );
			# Добавить в справочник новые найденные файлы
			self::addNewLink( $this->jsFileData , 'js_file' );
			
			
			# Объеденить с данными из базы модели js_style
			$this->scriptTagData = self::getItemsByHash( $hashArr , 'js_style' , $scriptTag );
			
			# Добавить в справочник новые найденные js_style
			self::addNewLink( $this->scriptTagData , 'js_style' );
		}
		
		/**
		 * Установить скрипты JS в тело документа
		 * @throws Exception
		 * @since 3.9
		 */
		public function insertJsSctiptIntoDocument ()
		{
			$dom = new \GNZ11\Document\Dom();
			foreach( $this->scriptTagData as $url => $Link )
			{
				if( isset( $Link->load ) && !$Link->load ) continue; #END IF
				unset( $Link->id );
				$Link = self::mergeSubFormLinksParams( 'params_query' , $Link );
				
				
				# Добавить тег Scrip Js в тело документа перед закрывающемся тегом </body>
				$dom::writeDownTag( 'script' , $Link->content , $Link );
			}
		}
		
		
		
		
		/**
		 * Установить в HTML ссылки на JS файлы
		 * @throws Exception
		 * @since 3.9
		 */
		public function insertJsLikIntoDocument ()
		{
			$dom = new \GNZ11\Document\Dom();
			
			foreach( $this->jsFileData as $url => $Link )
			{
				if( isset( $Link->load ) && !$Link->load ) continue; #END IF
				
				unset( $Link->id );
				
				# Подготовить ссылку  Js к загрузи - определить параметры ссылки
				$LinkData = \Plg\Pro_critical\Helpers\Assets\Js\Link::prepareJsLinkData( $Link );
				
				# Добавить тег ссылка Js в тело документа перед закрывающемся тегом </body>
				$dom::writeDownTag( 'script' , null , $Link );
			}#END FOREACH
			
		}#END FN
		
		
	}