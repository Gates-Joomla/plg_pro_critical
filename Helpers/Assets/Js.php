<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	use Joomla\CMS\Factory as JFactory ;
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
					
				}else{
					
					echo'<pre>';print_r( $attr );echo'</pre>'.__FILE__.' '.__LINE__;
					echo'<pre>';print_r( $node );echo'</pre>'.__FILE__.' '.__LINE__;
					die(__FILE__ .' '. __LINE__ );
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
		
		/**
		 * Установить в HTML ссылки на JS файлы
		 * @throws Exception
		 * @since 3.9
		 */
		public function insertJsLikIntoDocument(){
			$dom = new \GNZ11\Document\Dom();
			
			foreach( $this->jsFileData as $url => $Link )
			{
				if( isset($Link->load)  &&  !$Link->load  ) continue ; #END IF
				
				unset($Link->id) ;
				
				# Подготовить ссылку  Js к загрузи - определить параметры ссылки
				$LinkData = \Plg\Pro_critical\Helpers\Assets\Js\Link::prepareJsLinkData( $Link );
				
				# Добавить тег ссылка Js в тело документа перед закрывающемся тегом </body>
				$dom::writeDownTag('script' , null , $Link   );
			}#END FOREACH
			
		}#END FN
		
		
		
		
	}