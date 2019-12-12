<?php
	/**
	 * @package     Css_file
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	use JFactory;
	use JLoader;
	use JModelLegacy;
	use Exception;
	use JDate;
	use Joomla\CMS\Uri\Uri;
	use Throwable;
	
	/**
	 * @since       version
	 * @package     Plg\Pro_critical\HelpersCss
	 *
	 */
	class Css extends Links
	{
		private $app;
		public static $instance;
		
		public $statistics = [];
		
		public $cssFileData;
		public $cssStyleData;
		/**
		 * Имя компонента для вызова модели
		 * @since 3.9
		 * @var string
		 */
		private static $component = 'pro_critical';
		private static $prefix = 'pro_critical' . 'Model';
		
		
//		private $Css_file_list;
		
		
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = JFactory::getApplication();
//			JLoader::register( 'Pro_criticalHelper' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/helpers/pro_critical.php' );
//			JModelLegacy::addIncludePath( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_' . self::$component . DS . 'models' , self::$prefix );
//			$this->Css_file_list = JModelLegacy::getInstance( 'Css_file_list' , self::$prefix );
//			$this->Css_style_list = JModelLegacy::getInstance( 'Css_style_list' , self::$prefix );
			
			# Установить поля в статистику
			$this->statistics = [ 'New_fiels' => [] , 'Load_fiels' => [] , 'minifyCount' => 0 ];
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Css
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
		 * Найти и извлечь все ссылки на CSS файлы и теги стили
		 * Добавить новые данные в справочники.
		 * Совместить найденые данные с настройками компонента
		 *
		 * @throws Exception
		 * @since 3.9
		 */
		public function getFileList ()
		{
			$app = JFactory::getApplication();
			$Links_assets = \Plg\Pro_critical\Helpers\Assets\Links_assets ::instance() ;
			
			# Загрузить все данные из справочника CSS FILE
//			$app->input->set( 'limit' , 0 ); // Снять лимит на количество записей DEF - 20
//			$Css_file_list = $this->Css_file_list->getItems();
			
			
			$body = $this->app->getBody();
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( '//link[@rel="stylesheet"]|//style' );
			$link  = [];
			$styleTag = [] ;
			$hashArrLink = [] ;
			$hashArr = [] ;
			foreach( $Nodes as $node )
			{
				switch( $node->tagName )
				{
					case 'link':
						
						$attr    = $dom::getAttrElement( $node , [ 'rel' ] );
						$hash = md5( $attr[ 'href' ] ) ;
						$hashArrLink[] = $hash ;
						
						
						
						
						
						$hrefArr = explode( '?' , $attr[ 'href' ] );
						
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
						
						break;
					case 'style' :
						$hash = md5( $node->nodeValue ) ;
						$hashArr[] = $hash ;
						
						$Registry = new \Joomla\Registry\Registry;
						$Registry->set( 'load' , 1 );
						$Registry->set( 'hash' , $hash );
						$Registry->set( 'content' , $node->nodeValue );
						
						$styleTag[ $hash ] = $Registry->toObject();
						break;
				}#END SWICH
				
				# Удалить найденый узел
				$node->parentNode->removeChild( $node );
				
			}#END FOREACH
			
			$body = $dom->saveHTML();
			$this->app->setBody( $body );
			
			
			
			
			# Объеденить с данными из базы модели
			$this->cssFileData = self::getItemsByHash( $hashArrLink , 'css_file' , $link ) ;
			
			
			
			# Добавить в справочник новые найденные файлы
			self::addNewLink( $this->cssFileData , 'css_file' );
			
			
			# Объеденить с данными из базы модели css_style
			$this->cssStyleData = self::getItemsByHash( $hashArr , 'css_style' , $styleTag ) ;
			
			
			
			# Добавить в справочник новые найденные css_style
			self::addNewLink( $this->cssStyleData , 'css_style' );
		
		}
		
		
		public static $cssFileDataDelayed ;
		
		
		/**
		 * Установить в HTML ссылки на Css файлы и стили
		 *
		 * @throws Exception
		 * @since version
		 */
		public function insertStylesIntoDocument(){
			
			
			
			$ParamsComponent = self::getParamsComponent();
			$dom_params = [] ;
			
			
			
			/*if( isset($ParamsComponent['css_link_load_method']) && $ParamsComponent['css_link_load_method'] )
			{
				$dom_params['formatOutput'] = true;
			}#END IF*/
			
			$dom = new \GNZ11\Document\Dom();
			
			# Подключить Helper для работы со сылками
			$Links_assets = \Plg\Pro_critical\Helpers\Assets\Links_assets ::instance() ;
			
			# массив для HTML элементов
			$tagsArr = [] ;
			
			
			foreach( $this->cssFileData as $url => $Link )
			{
				if( isset($Link->load)  &&  !$Link->load  ) continue ; #END IF
				
				
				# TODO LOAD:CSS - Add the ability to load CSS files as a Style tag.
				$Linkcopy = $Link ;
				unset($Linkcopy->id);
				$Linkcopy->rel="stylesheet";
				
				# Подготовить ссылку к загрузи - определить параметры ссылки
				$LinkData = \Plg\Pro_critical\Helpers\Assets\Css\Link::prepareLinkCssData( $Linkcopy );
				
				
				
				# установить ссылку вниз Tag Head
				$dom::writeBottomHeadTag('link' , null , $Linkcopy , $dom_params );
				
			}#END FOREACH
			
			# Установка тегов стилей в документ
			\Plg\Pro_critical\Helpers\Assets\Css\Style::setStyleTag( $this->cssStyleData );
			
			
		}
		
		
		
		
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	