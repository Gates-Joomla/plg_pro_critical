<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	use JFactory;
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
		
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = JFactory::getApplication();
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return helper
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
		
		
		public function getListJs(){
			$body = $this->app->getBody();
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( '//script' );
			$link  = [];
			$styleTag = [] ;
			$hashArrLink = [] ;
			$hashArr = [] ;
			foreach( $Nodes as $node )
			{
				$attr    = $dom::getAttrElement( $node , [ 'rel' ] );
				# Если ссылка на файл
				if( isset( $attr['src'] ) & !empty($attr['src']) )
				{
					$hash = md5( $attr[ 'src' ] ) ;
					$hashArrLink[] = $hash ;
					$hrefArr = explode( '?' , $attr[ 'src' ] );
					unset( $attr[ 'href' ] );
					$href = $hrefArr[ 0 ] ;
					
					# Разбор ссылки - поиск ошибок - исправление ссылки - определение локальная ссылка или нет
					$log   = $this->linkAnalysis( $href );
					$href = $log['file'];
					
					
					$Registry = new \Joomla\Registry\Registry;
					$Registry->set('load' , 1 );
					$Registry->set('hash' , $hash );
					
					$data = array_merge( $log , $attr );
					
					# Разобрать параметры ссылки
					$data['params_query'] = $this->parseRequestParameters( $hrefArr  );
					
					$Registry->loadArray($data);
					$link[$hash] = $Registry->toObject();
					$this->statistics['errors'] += ( count( $log['err'] )) ;
					
					
					
					unset( $link[$hash]->src );
					
				}#END IF
				
				
				# Удалить найденый узел
				$node->parentNode->removeChild( $node );
			}#end FOREACH
			
			$body = $dom->saveHTML();
			$this->app->setBody( $body );
			
			
			# Объеденить с данными из базы модели
			$this->jsFileData = self::getItemsByHash( $hashArrLink , 'js_file' , $link ) ;
			# Добавить в справочник новые найденные файлы
			self::addNewLink( $this->jsFileData , 'js_file' );
		}
		
		
	}