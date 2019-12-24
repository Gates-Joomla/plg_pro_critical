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
	use Joomla\CMS\Component\ComponentHelper;
	use Plg\Pro_critical\Helpers\Assets\Css\Script as CssScript;
	
	/**
	 * @since       version
	 * @package     Plg\Pro_critical\HelpersCss
	 *
	 */
	class Css extends Links
	{
		private $app;
		public static $instance;
		/**
		 * Используется для сбора статистики
		 * TODO - Пока не используется
		 * @var array
		 * @since 3.9
		 */
		public $statistics = [];
		/**
		 * Массив с файлами для установки
		 * @var array
		 * @since 3.9
		 */
		public $cssFileData;
		/**
		 * Массив с css стилями для установки
		 * @var array
		 * @since 3.9
		 */
		public $cssStyleData;
		/**
		 * Массив ID файлов которы установлены на страницы
		 * Исключаются файлы с отложеной загрузкой
		 * Будет пустым если хотябы один файл установлен без ID (т.е только что обнаружен)
		 * @since 3.9
		 * @var array
		 *
		 */
		public static $LinkHistory = [] ;
		/**
		 * индикатор - Не запускать создание критических стилей
		 * @var bool
		 * @since 3.9
		 */
		public static $StopForCritical = false ;
		/**
		 * @var bool Индикатор - Критические стили созданы
		 * @since 3.9
		 */
		public static $UseCritical = false ;
		/**
		 * @var array Стэк для сохранения ссылок и стилей при созданных Critical
		 */
		public static $StackStyle = [] ;
		/**
		 * Имя компонента для вызова модели
		 * @since 3.9
		 * @var string
		 */
		private static $component = 'pro_critical';
		private static $prefix = 'pro_critical' . 'Model';
		
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
		
		/**
		 * Установить в HTML ссылки на Css файлы и стили
		 *
		 * @throws Exception
		 * @since version
		 */
		public function insertStylesIntoDocument(){
			// $ParamsComponent = self::getParamsComponent();
			$dom_params = [] ;
			$dom = new \GNZ11\Document\Dom();
			
			$CriticalCss = \Plg\Pro_critical\Helpers\Assets\CriticalCss::instance();
			$CriticalArr = $CriticalCss->getCriticalCss() ;
			
//			echo'<pre>';print_r( $CriticalArr );echo'</pre>'.__FILE__.' '.__LINE__;
//			die(__FILE__ .' '. __LINE__ );
			# Если критические стили созданы
			if( $CriticalArr['critical_css_code'] )
			{
				# Установить индикатор о том что критические стили созданы
				self::$UseCritical = true ;
				# установить критические стили вниз Tag <head>
				$dom::writeBottomHeadTag( 'style' , $CriticalArr['critical_css_code']  );
				
			}#END IF
			
			# Установить ссылки на Css файлы в тело документа
 		    $this->setLinkTag();
			
			 
			
			# Очистить историю установленых файлов
			# если обнаружен файл которого нет в справочнике Css Стилей
			if(  self::$StopForCritical   ) self::$LinkHistory = [] ; #END IF
			
			# Установка тегов стилей в документ
			\Plg\Pro_critical\Helpers\Assets\Css\Style::setStyleTag( $this->cssStyleData );
			
			
		}
		
		/**
		 * Установить ссылки на Css файлы в тело  документа
		 *
		 * @throws Exception
		 * @since 3.9
		 */
		protected function setLinkTag ()
		{
			$dom_params = [] ;
			$dom = new \GNZ11\Document\Dom();
			foreach( $this->cssFileData as $url => $Link )
			{
				if( isset( $Link->load ) && !$Link->load ) continue; #END IF
				
				# TODO LOAD:CSS - Add the ability to load CSS files as a Style tag.
				$Linkcopy      = $Link;
				$Linkcopy->rel = "stylesheet";
				
				$LinkCopy_Id = null;
				# Если файл есть в справочнике Css Стилей
				if( isset( $Linkcopy->id ) )
				{
					# Копируем ID для добавления а историю если будут создаваться критические стили
					$LinkCopy_Id = $Linkcopy->id;
					unset( $Linkcopy->id );
				}
				else
				{
					# Установить индикатор - Не запускать создание критических стилей
					self::$StopForCritical = true;
				}#END IF
				
				
				# Подготовить ссылку к загрузи - определить параметры ссылки
				\Plg\Pro_critical\Helpers\Assets\Css\Link::prepareLinkCssData( $Linkcopy );
				
				# Если критичиские стили созданы и
				# в настройках файла установлино не загружать при созданных критичиских стилях
				if( !$Linkcopy->load_if_criticalis_set && self::$UseCritical ) continue ; #END IF
				
//				echo'<pre>';print_r( $Linkcopy->delayed_loading );echo'</pre>'.__FILE__.' '.__LINE__;
				
				# Проверяем на отложеную загрузку
				if( !$Linkcopy->delayed_loading )
				{
					self::$LinkHistory[] = $LinkCopy_Id;
					
					# Если критичиские стили не созданы
					if( !self::$UseCritical )
					{
						# установить ссылку вниз Tag Head
						$dom::writeBottomHeadTag( 'link' , null , $Linkcopy , $dom_params );
					}else{
						
						self::$StackStyle['link'][] = $Linkcopy ;
						
						// $dom::writeDownTag ( 'link' , null , $Linkcopy  ) ;
					}#END IF
					
				}#END IF
			}#END FOREACH
			
//			die(__FILE__ .' '. __LINE__ );
			
		}#END FN
		
		/**
		 * Установка CSS Стилей Если созданы критические стили
		 * @throws Exception
		 * @since 3.9
		 */
		public function insertCssAfterLoad(){
			if( !self::$UseCritical ) return ; #END IF
			$dom = new \GNZ11\Document\Dom();
			$params = ComponentHelper::getParams( 'com_pro_critical' , $strict = false );
			$css_loading_method = $params->get('css_loading_method' , 2 ) ;
			
			
			
			switch( $css_loading_method ){
				case 0:
					foreach( self::$StackStyle['link'] as $LinkCopy )
					{
						# Создать тэг перед </body>
						$dom::writeDownTag ( 'link' , null , $LinkCopy  ) ;
					}#END FOREACH
					break ;
				case 1 :
					# Если включен способ загрузки ссылки на CSS Загружает JS
					CssScript::addCssAfterLoadPage(self::$StackStyle);
					break ;
				default:
					# Способ прелоадер
					# Создаст <link rel="preloader" as="style" onload="this.onload=null;this.rel='stylesheet'" />
					# Также добавит ссылку в тег <noscript>
					foreach( self::$StackStyle['link'] as $LinkCopy )
					{
						$dom::writeDownNosciptTag( 'link' , null , $LinkCopy ) ;
						$LinkCopy->onload = "this.onload=null;this.rel='stylesheet'" ;
						\Plg\Pro_critical\Helpers\Assets\Links::setPreload($LinkCopy  ) ;
					}
					
				
			}
			return ;
			
			
			
			
			
			
//			echo'<pre>';print_r( self::$UseCritical );echo'</pre>'.__FILE__.' '.__LINE__;
//			die(__FILE__ .' '. __LINE__ );
		}
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	